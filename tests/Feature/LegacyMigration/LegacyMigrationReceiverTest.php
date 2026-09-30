<?php

namespace Tests\Feature\LegacyMigration;

use App\Modules\LegacyMigration\Models\LegacyMigrationChunk;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyMigrationReceiverTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'receiver-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('legacy_migration.enabled', true);
        config()->set('legacy_migration.token_hash', hash('sha256', $this->token));
        config()->set('legacy_migration.allowed_source_systems', ['haidangtravel_legacy']);
        config()->set('legacy_migration.disk', 'legacy-test');
        Storage::fake('legacy-test');
    }

    public function test_receiver_rejects_disabled_or_invalid_credentials(): void
    {
        config()->set('legacy_migration.enabled', false);

        $this->postJson('/api/v1/legacy-migrations/runs', [])->assertForbidden();

        config()->set('legacy_migration.enabled', true);

        $this->postJson('/api/v1/legacy-migrations/runs', [], ['Authorization' => 'Bearer wrong'])
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    public function test_receiver_stages_payload_idempotently_and_finalizes_for_manual_mapping(): void
    {
        $runId = (string) Str::uuid();
        $this->startRun($runId)->assertCreated();
        $payload = $this->chunkPayload($runId);
        $headers = $this->headers($payload['chunk']['idempotency_key']);

        $this->putJson("/api/v1/legacy-migrations/runs/{$runId}/chunks/1", $payload, $headers)
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted');

        $this->putJson("/api/v1/legacy-migrations/runs/{$runId}/chunks/1", $payload, $headers)
            ->assertOk();

        $this->assertSame(1, LegacyMigrationChunk::query()->count());
        $this->assertSame(1, LegacyStagedObject::query()->count());
        $this->assertSame(1, LegacyStagedUrl::query()->count());
        $chunk = LegacyMigrationChunk::query()->firstOrFail();
        Storage::disk('legacy-test')->assertExists($chunk->payload_path);

        $this->postJson("/api/v1/legacy-migrations/runs/{$runId}/finalize", [], $this->headers())
            ->assertStatus(202)
            ->assertJsonPath('status', 'ready_for_mapping')
            ->assertJsonPath('warnings', []);

        $run = LegacyMigrationRun::query()->firstOrFail();
        $this->assertSame('ready_for_mapping', $run->status);
        $this->assertSame(1, $run->received_chunks);
        $this->assertSame(1, $run->staged_urls);
        $this->assertNull($run->error_text);
        $this->assertFalse($run->summary_json['finalized_with_warnings']);
    }

    public function test_finalize_accepts_duplicate_normalized_urls_and_persists_a_run_warning(): void
    {
        Log::spy();
        $runId = (string) Str::uuid();
        $this->startRun($runId, totalUrls: 2)->assertCreated();
        $payload = $this->chunkPayload($runId);
        $duplicate = $payload['urls'][0];
        $duplicate['raw_url'] = 'https://haidangtravel.com/tour-da-lat-cu?utm_source=duplicate';
        $duplicate['raw_path'] = '/tour-da-lat-cu?utm_source=duplicate';
        $payload['urls'][] = $duplicate;
        $payload['payload_hash'] = $this->hashPayload($payload);

        $this->putJson(
            "/api/v1/legacy-migrations/runs/{$runId}/chunks/1",
            $payload,
            $this->headers($payload['chunk']['idempotency_key']),
        )->assertStatus(202);

        $this->assertSame(1, LegacyStagedUrl::query()->count());
        $this->postJson("/api/v1/legacy-migrations/runs/{$runId}/finalize", [], $this->headers())
            ->assertStatus(202)
            ->assertJsonPath('status', 'ready_for_mapping')
            ->assertJsonPath('warnings.0.code', 'url_count_mismatch')
            ->assertJsonPath('warnings.0.expected_urls', 2)
            ->assertJsonPath('warnings.0.received_url_rows', 2)
            ->assertJsonPath('warnings.0.staged_urls', 1)
            ->assertJsonPath('warnings.0.deduplicated_url_rows', 1);

        $run = LegacyMigrationRun::query()->firstOrFail();
        $this->assertSame('ready_for_mapping', $run->status);
        $this->assertNotNull($run->finalized_at);
        $this->assertStringContainsString('1 dòng URL trùng sau chuẩn hóa', $run->error_text);
        $this->assertTrue($run->summary_json['finalized_with_warnings']);
        $this->postJson("/api/v1/legacy-migrations/runs/{$runId}/finalize", [], $this->headers())
            ->assertOk()
            ->assertJsonPath('message', 'Phiên đã được finalize trước đó.')
            ->assertJsonPath('warnings.0.code', 'url_count_mismatch');
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'Phiên migrate legacy được finalize với staging không đầy đủ.'
                && $context['source_run_id'] === $runId
                && $context['expected_urls'] === 2
                && $context['staged_urls'] === 1,
        );
    }

    public function test_finalize_accepts_missing_chunk_but_rejects_late_chunks_afterward(): void
    {
        $runId = (string) Str::uuid();
        $this->startRun($runId, totalChunks: 2)->assertCreated();
        $payload = $this->chunkPayload($runId);
        $this->putJson(
            "/api/v1/legacy-migrations/runs/{$runId}/chunks/1",
            $payload,
            $this->headers($payload['chunk']['idempotency_key']),
        )->assertStatus(202);

        $this->postJson("/api/v1/legacy-migrations/runs/{$runId}/finalize", [], $this->headers())
            ->assertStatus(202)
            ->assertJsonPath('status', 'ready_for_mapping')
            ->assertJsonPath('warnings.0.code', 'chunk_sequence_mismatch')
            ->assertJsonPath('warnings.0.missing_sequence_count', 1)
            ->assertJsonPath('warnings.0.missing_sequences.0', 2);

        $latePayload = $this->chunkPayload($runId);
        $latePayload['chunk']['sequence'] = 2;
        $latePayload['chunk']['uuid'] = (string) Str::uuid();
        $latePayload['chunk']['idempotency_key'] = $runId.':chunk:2';
        $latePayload['payload_hash'] = $this->hashPayload($latePayload);
        $this->putJson(
            "/api/v1/legacy-migrations/runs/{$runId}/chunks/2",
            $latePayload,
            $this->headers($latePayload['chunk']['idempotency_key']),
        )->assertConflict()->assertJsonPath('message', 'Phiên đã finalize nên không thể nhận thêm chunk.');
    }

    public function test_same_idempotency_key_with_a_different_hash_is_rejected(): void
    {
        $runId = (string) Str::uuid();
        $this->startRun($runId)->assertCreated();
        $payload = $this->chunkPayload($runId);
        $headers = $this->headers($payload['chunk']['idempotency_key']);
        $this->putJson("/api/v1/legacy-migrations/runs/{$runId}/chunks/1", $payload, $headers)->assertStatus(202);

        $payload['urls'][0]['metrics']['clicks'] = 999;
        $payload['payload_hash'] = $this->hashPayload($payload);

        $this->putJson("/api/v1/legacy-migrations/runs/{$runId}/chunks/1", $payload, $headers)
            ->assertConflict()
            ->assertJsonPath('message', 'Khóa idempotency đã tồn tại nhưng checksum không khớp.');
    }

    public function test_tampered_object_checksum_is_rejected(): void
    {
        $runId = (string) Str::uuid();
        $this->startRun($runId)->assertCreated();
        $payload = $this->chunkPayload($runId);
        $payload['objects'][0]['attributes']['title'] = 'Nội dung bị thay đổi';
        $payload['payload_hash'] = $this->hashPayload($payload);

        $this->putJson(
            "/api/v1/legacy-migrations/runs/{$runId}/chunks/1",
            $payload,
            $this->headers($payload['chunk']['idempotency_key']),
        )->assertUnprocessable()->assertJsonValidationErrors('objects.0.checksum');
    }

    public function test_checksum_uses_raw_decoded_payload_before_laravel_trims_or_converts_empty_strings(): void
    {
        $runId = (string) Str::uuid();
        $this->startRun($runId)->assertCreated();
        $payload = $this->chunkPayload($runId);
        $payload['objects'][0]['attributes']['empty_text'] = '';
        $payload['objects'][0]['attributes']['spaced_text'] = '  giữ nguyên khoảng trắng  ';
        $payload['objects'][0]['checksum'] = $this->hash($payload['objects'][0]);
        $payload['payload_hash'] = $this->hashPayload($payload);

        $this->putJson(
            "/api/v1/legacy-migrations/runs/{$runId}/chunks/1",
            $payload,
            $this->headers($payload['chunk']['idempotency_key']),
        )->assertStatus(202)->assertJsonPath('payload_hash', $payload['payload_hash']);

        $attributes = LegacyStagedObject::query()->firstOrFail()->payload_json['attributes'];
        $this->assertSame('', $attributes['empty_text']);
        $this->assertSame('  giữ nguyên khoảng trắng  ', $attributes['spaced_text']);
    }

    private function startRun(string $runId, int $totalUrls = 1, int $totalChunks = 1)
    {
        return $this->postJson('/api/v1/legacy-migrations/runs', [
            'schema_version' => 'haidang-legacy-content.v1',
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => $runId,
            'source_filename' => 'traffic.xlsx',
            'total_urls' => $totalUrls,
            'total_chunks' => $totalChunks,
            'created_at' => '2026-09-15T09:00:00+07:00',
        ], $this->headers());
    }

    private function chunkPayload(string $runId): array
    {
        $object = [
            'key' => 'tour:125',
            'type' => 'tour',
            'legacy_id' => 125,
            'partial' => false,
            'attributes' => [
                'id' => 125,
                'title' => 'Tour Đà Lạt nguồn',
                'description' => '<p>Nội dung từ website cũ.</p>',
                'status' => 1,
                'created_at' => '2020-01-02 03:04:05',
                'updated_at' => '2021-02-03 04:05:06',
            ],
            'relationships' => [],
            'media' => [],
        ];
        $object['checksum'] = $this->hash($object);
        $payload = [
            'schema_version' => 'haidang-legacy-content.v1',
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => $runId,
            'chunk' => [
                'uuid' => (string) Str::uuid(),
                'sequence' => 1,
                'idempotency_key' => $runId.':chunk:1',
            ],
            'urls' => [[
                'raw_url' => 'https://haidangtravel.com/tour-da-lat-cu',
                'raw_path' => '/tour-da-lat-cu',
                'normalized_path' => '/tour-da-lat-cu',
                'resolved_path' => '/tour-da-lat-cu',
                'route_kind' => 'tour_detail',
                'root' => 'tour:125',
                'metrics' => ['clicks' => 12, 'impressions' => 120, 'ctr' => 0.1, 'position' => 3.2],
            ]],
            'objects' => [$object],
        ];
        $payload['payload_hash'] = $this->hash($payload);

        return $payload;
    }

    private function hashPayload(array $payload): string
    {
        unset($payload['payload_hash']);

        return $this->hash($payload);
    }

    private function hash(array $value): string
    {
        unset($value['checksum']);

        return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function headers(?string $idempotencyKey = null): array
    {
        return array_filter([
            'Authorization' => 'Bearer '.$this->token,
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }
}
