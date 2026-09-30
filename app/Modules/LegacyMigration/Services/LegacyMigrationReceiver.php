<?php

namespace App\Modules\LegacyMigration\Services;

use App\Modules\LegacyMigration\Exceptions\LegacyMigrationConflict;
use App\Modules\LegacyMigration\Models\LegacyMigrationChunk;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use UnexpectedValueException;

class LegacyMigrationReceiver
{
    public function __construct(
        private LegacyPayloadHasher $hasher,
        private LegacyTimestampNormalizer $timestamps,
        private LegacyPath $paths,
        private LegacyRunCounter $counter,
    ) {}

    /** @return array{0: LegacyMigrationRun, 1: bool} */
    public function createRun(array $payload): array
    {
        try {
            $sourceCreatedAt = $this->timestamps->nullable($payload['created_at'] ?? null);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['created_at' => [$exception->getMessage()]]);
        }

        return DB::transaction(function () use ($payload, $sourceCreatedAt): array {
            $existing = LegacyMigrationRun::query()
                ->where('source_system', $payload['source_system'])
                ->where('source_run_id', $payload['source_run_id'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ((int) $existing->expected_chunks !== (int) $payload['total_chunks']
                    || (int) $existing->expected_urls !== (int) $payload['total_urls']
                    || $existing->schema_version !== $payload['schema_version']
                ) {
                    throw new LegacyMigrationConflict('Phiên đã tồn tại nhưng metadata không khớp.');
                }

                return [$existing, false];
            }

            $run = LegacyMigrationRun::query()->create([
                'uuid' => (string) Str::uuid(),
                'source_system' => $payload['source_system'],
                'source_run_id' => $payload['source_run_id'],
                'schema_version' => $payload['schema_version'],
                'source_filename' => $payload['source_filename'] ?? null,
                'status' => 'receiving',
                'expected_chunks' => $payload['total_chunks'],
                'expected_urls' => $payload['total_urls'],
                'source_created_at' => $sourceCreatedAt,
                'started_at' => now(),
            ]);

            return [$run, true];
        });
    }

    /** @return array{0: LegacyMigrationChunk, 1: bool} */
    public function receiveChunk(
        string $sourceRunUuid,
        int $sequence,
        string $headerIdempotencyKey,
        string $rawBody,
    ): array {
        try {
            $payload = $this->hasher->decode($rawBody);
        } catch (JsonException|UnexpectedValueException $exception) {
            throw ValidationException::withMessages([
                'payload' => [$exception->getMessage()],
            ]);
        }

        $this->validateChunkEnvelope($sourceRunUuid, $sequence, $headerIdempotencyKey, $payload);
        $computedHash = $this->hasher->payload($payload);

        if (! hash_equals(mb_strtolower((string) $payload['payload_hash']), $computedHash)) {
            throw ValidationException::withMessages([
                'payload_hash' => ['Checksum của payload không khớp.'],
            ]);
        }

        foreach ($payload['objects'] as $index => $object) {
            if (! hash_equals(mb_strtolower((string) $object['checksum']), $this->hasher->object($object))) {
                throw ValidationException::withMessages([
                    "objects.{$index}.checksum" => ['Checksum của object không khớp.'],
                ]);
            }

            $identifiers = collect([$object['legacy_id'] ?? null, $object['legacy_key'] ?? null])
                ->filter(fn (mixed $value): bool => is_int($value) || is_string($value))
                ->map(fn (int|string $value): string => (string) $object['type'].':'.$value)
                ->all();

            if ($identifiers === [] || ! in_array((string) $object['key'], $identifiers, true)) {
                throw ValidationException::withMessages([
                    "objects.{$index}.key" => ['Object key không khớp type và legacy_id/legacy_key.'],
                ]);
            }
        }

        foreach ($payload['urls'] as $index => $url) {
            $rawPath = (string) $url['raw_path'];

            if (! str_starts_with($rawPath, '/') || str_contains($rawPath, "\0")) {
                throw ValidationException::withMessages([
                    "urls.{$index}.raw_path" => ['Đường dẫn gốc không hợp lệ.'],
                ]);
            }

            try {
                $normalizedPath = $this->paths->normalize((string) $url['normalized_path']);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    "urls.{$index}.normalized_path" => [$exception->getMessage()],
                ]);
            }

            if ($normalizedPath !== $url['normalized_path']) {
                throw ValidationException::withMessages([
                    "urls.{$index}.normalized_path" => ['Đường dẫn chưa được chuẩn hóa.'],
                ]);
            }

            if (filled($url['resolved_path'] ?? null)) {
                try {
                    $this->paths->normalize((string) $url['resolved_path']);
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        "urls.{$index}.resolved_path" => [$exception->getMessage()],
                    ]);
                }
            }
        }

        $disk = (string) config('legacy_migration.disk');
        $storedPath = null;

        try {
            return DB::transaction(function () use ($sourceRunUuid, $sequence, $headerIdempotencyKey, $payload, $rawBody, $computedHash, $disk, &$storedPath): array {
                $run = LegacyMigrationRun::query()
                    ->where('source_system', $payload['source_system'])
                    ->where('source_run_id', $sourceRunUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($run->status !== 'receiving') {
                    throw new LegacyMigrationConflict('Phiên đã finalize nên không thể nhận thêm chunk.');
                }

                if ($sequence > $run->expected_chunks) {
                    throw ValidationException::withMessages([
                        'chunk.sequence' => ['Sequence vượt quá tổng số chunk của phiên.'],
                    ]);
                }

                $existing = LegacyMigrationChunk::query()
                    ->where(function ($query) use ($run, $sequence, $headerIdempotencyKey): void {
                        $query->where('idempotency_key', $headerIdempotencyKey)
                            ->orWhere(function ($sequenceQuery) use ($run, $sequence): void {
                                $sequenceQuery->where('run_id', $run->id)->where('sequence', $sequence);
                            });
                    })
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if ((int) $existing->run_id !== (int) $run->id
                        || (int) $existing->sequence !== $sequence
                        || $existing->idempotency_key !== $headerIdempotencyKey
                        || ! hash_equals($existing->payload_hash, $computedHash)
                    ) {
                        throw new LegacyMigrationConflict('Khóa idempotency đã tồn tại nhưng checksum không khớp.');
                    }

                    $existing->increment('attempts');

                    return [$existing->refresh(), false];
                }

                $storedPath = 'legacy-migrations/'.$run->uuid.'/chunks/'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT).'.json';

                if (! Storage::disk($disk)->put($storedPath, $rawBody)) {
                    throw new RuntimeException('Không thể lưu payload vào private storage.');
                }

                $chunk = LegacyMigrationChunk::query()->create([
                    'run_id' => $run->id,
                    'sequence' => $sequence,
                    'source_chunk_uuid' => $payload['chunk']['uuid'],
                    'idempotency_key' => $headerIdempotencyKey,
                    'payload_hash' => $computedHash,
                    'payload_disk' => $disk,
                    'payload_path' => $storedPath,
                    'payload_bytes' => strlen($rawBody),
                    'url_count' => count($payload['urls']),
                    'object_count' => count($payload['objects']),
                    'status' => 'accepted',
                    'received_at' => now(),
                ]);

                foreach ($payload['objects'] as $object) {
                    $this->stageObject($run, $chunk, $object);
                }

                foreach ($payload['urls'] as $url) {
                    $this->stageUrl($run, $chunk, $url);
                }

                $this->counter->refresh($run);

                return [$chunk, true];
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $exception;
        }
    }

    /** @return array{0: LegacyMigrationRun, 1: bool} */
    public function finalize(string $sourceRunUuid): array
    {
        [$run, $changed] = DB::transaction(function () use ($sourceRunUuid): array {
            $run = LegacyMigrationRun::query()->where('source_run_id', $sourceRunUuid)->lockForUpdate()->firstOrFail();

            if ($run->status !== 'receiving') {
                return [$this->counter->refresh($run), false];
            }

            $receivedSequences = $run->chunks()->orderBy('sequence')->pluck('sequence')->map(fn ($value): int => (int) $value)->all();
            $expectedSequences = range(1, $run->expected_chunks);
            $missingSequences = array_values(array_diff($expectedSequences, $receivedSequences));
            $unexpectedSequences = array_values(array_diff($receivedSequences, $expectedSequences));
            $receivedUrlRows = (int) $run->chunks()->sum('url_count');
            $stagedUrlCount = $run->stagedUrls()->count();
            $warnings = [];

            if ($receivedSequences !== $expectedSequences) {
                $warnings[] = [
                    'code' => 'chunk_sequence_mismatch',
                    'message' => sprintf(
                        'Đã finalize dù sequence chunk không khớp: nhận %d/%d chunk; thiếu %d, ngoài dự kiến %d.',
                        count($receivedSequences),
                        $run->expected_chunks,
                        count($missingSequences),
                        count($unexpectedSequences),
                    ),
                    'expected_chunks' => $run->expected_chunks,
                    'received_chunks' => count($receivedSequences),
                    'missing_sequence_count' => count($missingSequences),
                    'missing_sequences' => array_slice($missingSequences, 0, 100),
                    'unexpected_sequence_count' => count($unexpectedSequences),
                    'unexpected_sequences' => array_slice($unexpectedSequences, 0, 100),
                ];
            }

            if ($stagedUrlCount !== $run->expected_urls) {
                $deduplicatedUrlRows = max(0, $receivedUrlRows - $stagedUrlCount);
                $reason = match (true) {
                    $receivedUrlRows === $run->expected_urls && $deduplicatedUrlRows > 0 => sprintf(
                        '%d dòng URL trùng sau chuẩn hóa đã được gộp vào URL staging hiện có.',
                        $deduplicatedUrlRows,
                    ),
                    $receivedUrlRows < $run->expected_urls => sprintf(
                        'Các chunk chỉ chứa %d/%d dòng URL đã khai báo.',
                        $receivedUrlRows,
                        $run->expected_urls,
                    ),
                    default => 'Tổng dòng URL nhận được, tổng khai báo và số URL staging duy nhất không đồng nhất.',
                };
                $warnings[] = [
                    'code' => 'url_count_mismatch',
                    'message' => sprintf(
                        'Đã finalize với %d URL staging trên %d URL khai báo. %s Chỉ dữ liệu đã staging được xử lý.',
                        $stagedUrlCount,
                        $run->expected_urls,
                        $reason,
                    ),
                    'expected_urls' => $run->expected_urls,
                    'received_url_rows' => $receivedUrlRows,
                    'staged_urls' => $stagedUrlCount,
                    'deduplicated_url_rows' => $deduplicatedUrlRows,
                    'url_delta' => $stagedUrlCount - $run->expected_urls,
                ];
            }

            $run->forceFill([
                'status' => 'ready_for_mapping',
                'staged_urls' => $stagedUrlCount,
                'finalized_at' => now(),
                'error_text' => $warnings !== []
                    ? implode(' ', array_column($warnings, 'message'))
                    : null,
                'summary_json' => [
                    'objects' => $run->stagedObjects()->count(),
                    'object_conflicts' => $run->stagedObjects()->where('status', 'needs_review')->count(),
                    'urls_needing_review' => $run->stagedUrls()->where('status', 'needs_review')->count(),
                    'finalized_with_warnings' => $warnings !== [],
                    'finalize_warnings' => $warnings,
                ],
            ])->save();

            return [$this->counter->refresh($run), true];
        });

        $warnings = (array) data_get($run->summary_json, 'finalize_warnings', []);
        if ($changed && $warnings !== []) {
            Log::warning('Phiên migrate legacy được finalize với staging không đầy đủ.', [
                'run_id' => $run->id,
                'source_system' => $run->source_system,
                'source_run_id' => $run->source_run_id,
                'expected_chunks' => $run->expected_chunks,
                'received_chunks' => $run->received_chunks,
                'expected_urls' => $run->expected_urls,
                'staged_urls' => $run->staged_urls,
                'warnings' => $warnings,
            ]);
        }

        return [$run, $changed];
    }

    private function validateChunkEnvelope(string $sourceRunUuid, int $sequence, string $headerIdempotencyKey, array $payload): void
    {
        $errors = [];

        if ($sourceRunUuid !== $payload['source_run_id']) {
            $errors['source_run_id'][] = 'source_run_id không khớp URL.';
        }

        if ($sequence !== (int) $payload['chunk']['sequence']) {
            $errors['chunk.sequence'][] = 'Sequence không khớp URL.';
        }

        if ($headerIdempotencyKey === '' || ! hash_equals((string) $payload['chunk']['idempotency_key'], $headerIdempotencyKey)) {
            $errors['chunk.idempotency_key'][] = 'Header Idempotency-Key không khớp payload.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function stageObject(LegacyMigrationRun $run, LegacyMigrationChunk $chunk, array $object): void
    {
        $existing = LegacyStagedObject::query()
            ->where('run_id', $run->id)
            ->where('object_key', $object['key'])
            ->lockForUpdate()
            ->first();
        $incomingPartial = (bool) ($object['partial'] ?? false);

        if ($existing && ! $existing->is_partial && $incomingPartial) {
            return;
        }

        $attributes = (array) ($object['attributes'] ?? []);
        $status = $existing && ! $incomingPartial && ! $existing->is_partial && $existing->checksum !== $object['checksum']
            ? 'needs_review'
            : 'pending';
        $values = [
            'chunk_id' => $chunk->id,
            'object_key' => $object['key'],
            'object_type' => $object['type'],
            'legacy_id' => isset($object['legacy_id']) ? (string) $object['legacy_id'] : null,
            'legacy_key' => isset($object['legacy_key']) ? (string) $object['legacy_key'] : null,
            'is_partial' => $incomingPartial,
            'checksum' => mb_strtolower((string) $object['checksum']),
            'payload_json' => $object,
            'source_created_at' => $this->safeTimestamp($attributes['created_at'] ?? null),
            'source_updated_at' => $this->safeTimestamp($attributes['updated_at'] ?? null),
            'status' => $status,
            'error_text' => $status === 'needs_review' ? 'Nhận nhiều object đầy đủ cùng key nhưng checksum khác nhau.' : null,
        ];

        if ($existing) {
            $existing->fill($values)->save();
        } else {
            LegacyStagedObject::query()->create(['run_id' => $run->id, ...$values]);
        }
    }

    private function stageUrl(LegacyMigrationRun $run, LegacyMigrationChunk $chunk, array $url): void
    {
        $normalizedPath = $this->paths->normalize((string) $url['normalized_path']);
        $metrics = (array) ($url['metrics'] ?? []);
        $existing = LegacyStagedUrl::query()
            ->where('run_id', $run->id)
            ->where('path_hash', hash('sha256', $normalizedPath))
            ->lockForUpdate()
            ->first();
        $rootChanged = $existing
            && filled($existing->root_object_key)
            && filled($url['root'] ?? null)
            && $existing->root_object_key !== $url['root'];
        $values = [
            'chunk_id' => $chunk->id,
            'raw_url' => $url['raw_url'] ?? null,
            'raw_path' => (string) $url['raw_path'],
            'normalized_path' => $normalizedPath,
            'path_hash' => hash('sha256', $normalizedPath),
            'resolved_path' => filled($url['resolved_path'] ?? null) ? $this->paths->normalize((string) $url['resolved_path']) : null,
            'route_kind' => $url['route_kind'],
            'root_object_key' => $url['root'] ?? null,
            'payload_json' => $url,
            'clicks' => max(0, (int) Arr::get($metrics, 'clicks', 0)),
            'impressions' => max(0, (int) Arr::get($metrics, 'impressions', 0)),
            'ctr' => Arr::get($metrics, 'ctr'),
            'position' => Arr::get($metrics, 'position'),
            'status' => $rootChanged ? 'needs_review' : 'pending',
            'error_text' => $rootChanged ? 'URL trùng nhưng root object khác nhau.' : null,
        ];

        if ($existing) {
            $existing->fill($values)->save();
        } else {
            LegacyStagedUrl::query()->create(['run_id' => $run->id, ...$values]);
        }
    }

    private function safeTimestamp(mixed $value): mixed
    {
        try {
            return $this->timestamps->nullable($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
