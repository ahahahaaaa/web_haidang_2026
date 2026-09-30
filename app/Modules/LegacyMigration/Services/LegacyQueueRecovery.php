<?php

namespace App\Modules\LegacyMigration\Services;

use App\Models\User;
use App\Modules\LegacyMigration\Jobs\ProcessLegacyMigrationUrl;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use Carbon\CarbonInterface;
use Illuminate\Bus\UniqueLock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use JsonException;
use Throwable;

class LegacyQueueRecovery
{
    public function __construct(
        private LegacyAutomaticTargetResolver $resolver,
        private LegacyTargetRegistry $targets,
    ) {}

    public function summary(LegacyMigrationRun $run, string $sourceType): array
    {
        $counts = $this->urlQuery($run, $sourceType)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        try {
            $this->queueConfiguration();
            $reason = null;
        } catch (InvalidArgumentException $exception) {
            $reason = $exception->getMessage();
        }

        return [
            'connection' => (string) config('queue.default'),
            'queue' => (string) config('legacy_migration.queue', 'default'),
            'queued' => (int) ($counts['queued'] ?? 0),
            'processing' => (int) ($counts['processing'] ?? 0),
            'stale' => $this->urlQuery($run, $sourceType)->where('updated_at', '<=', $this->cutoff())->count(),
            'stale_seconds' => $this->staleSeconds(),
            'unavailable_reason' => $reason,
        ];
    }

    public function staleSeconds(): int
    {
        $connection = (string) config('queue.default');

        return max(
            300,
            (int) config('legacy_migration.recovery.stale_after_seconds', 300),
            (int) config("queue.connections.{$connection}.retry_after", 90) + ProcessLegacyMigrationUrl::OVERLAP_LOCK_SECONDS,
        );
    }

    public function cutoff(): CarbonInterface
    {
        return now()->subSeconds($this->staleSeconds());
    }

    public function recover(LegacyMigrationRun $run, User $actor, string $sourceType, bool $importMedia, ?int $urlId = null): array
    {
        abort_unless($actor->hasRole('super_admin'), 403);

        if ($run->status === 'receiving') {
            throw new InvalidArgumentException('Phiên chưa finalize; chưa thể khôi phục hàng đợi.');
        }

        $configuration = $this->queueConfiguration();
        $knownJobs = $this->knownQueuedUrls($configuration);
        $result = ['recovered' => 0, 'existing_jobs' => 0, 'active' => 0, 'invalid' => 0, 'errors' => []];

        $this->urlQuery($run, $sourceType)
            ->where('updated_at', '<=', $this->cutoff())
            ->when($urlId !== null, fn (Builder $query) => $query->whereKey($urlId))
            ->chunkById(100, function ($urls) use ($run, $actor, $sourceType, $importMedia, $configuration, $knownJobs, &$result): void {
                foreach ($urls as $url) {
                    if (isset($knownJobs[$url->id])) {
                        $result['existing_jobs']++;

                        continue;
                    }

                    $job = new ProcessLegacyMigrationUrl(
                        $url->id,
                        $actor->id,
                        $sourceType,
                        'reuse_existing',
                        $url->mapping_mode ?: 'cast_and_redirect',
                        $url->merge_policy ?: 'overwrite',
                        $importMedia && $sourceType === 'blog',
                        reuseExistingTarget: true,
                    );
                    $lock = Cache::lock($job->executionLockKey(), ProcessLegacyMigrationUrl::OVERLAP_LOCK_SECONDS);

                    if (! $lock->get()) {
                        $result['active']++;

                        continue;
                    }

                    $unique = new UniqueLock(Cache::store());
                    $acquired = false;

                    try {
                        $outcome = DB::transaction(function () use ($run, $url, $actor, $sourceType, $configuration, $job, $unique, &$acquired): string {
                            $url = LegacyStagedUrl::query()->with('run')->where('run_id', $run->id)->lockForUpdate()->findOrFail($url->id);

                            if (! in_array($url->status, ['queued', 'processing'], true) || $url->updated_at->gt($this->cutoff())) {
                                return 'active';
                            }

                            if ($this->queueContainsUrl($configuration, $url->id)) {
                                return 'existing_jobs';
                            }

                            if (! in_array($job->mappingMode, ['cast_preserve_url', 'cast_and_redirect', 'cast_only'], true)
                                || ! in_array($job->mergePolicy, ['fill_blanks', 'overwrite'], true)) {
                                throw new InvalidArgumentException('Mapping hoặc chính sách field cũ không hỗ trợ khôi phục tự động.');
                            }

                            $target = $this->resolver->resolveExisting($url, $sourceType);
                            $targetType = $this->resolver->targetType($sourceType);
                            $before = $url->only(['status', 'error_text', 'target_type', 'target_id', 'target_path', 'mapped_at', 'casted_at']);
                            $unique->release($job);
                            $acquired = $unique->acquire($job);

                            if (! $acquired) {
                                return 'active';
                            }

                            $url->forceFill([
                                'status' => 'queued',
                                'error_text' => null,
                                'target_type' => $targetType,
                                'target_id' => (string) $target->getKey(),
                                'target_path' => $url->target_path ?: $this->targets->path($targetType, $target),
                            ])->save();
                            $job->onConnection($configuration['connection'])->beforeCommit();
                            $queueId = Queue::connection($configuration['connection'])->push($job, '', $job->queue);

                            if (! $queueId) {
                                throw new InvalidArgumentException('Không thể tạo lại job; trạng thái URL được giữ nguyên.');
                            }

                            LegacyCastAudit::query()->create([
                                'run_id' => $run->id,
                                'staged_url_id' => $url->id,
                                'target_type' => $targetType,
                                'target_id' => (string) $target->getKey(),
                                'action' => 'recover_queue',
                                'merge_policy' => $job->mergePolicy,
                                'timestamp_policy' => 'source',
                                'before_json' => $before,
                                'after_json' => [
                                    'queue_job_id' => $queueId,
                                    'queue_connection' => $configuration['connection'],
                                    'queue' => $job->queue,
                                    'source_type' => $sourceType,
                                    'mapping_mode' => $job->mappingMode,
                                    'import_media' => $job->importMedia,
                                    'reuse_existing_target' => true,
                                ],
                                'actor_id' => $actor->id,
                                'status' => 'completed',
                            ]);

                            return 'recovered';
                        });
                        $result[$outcome]++;
                    } catch (Throwable $exception) {
                        if ($acquired) {
                            $unique->release($job);
                        }

                        if (! $exception instanceof InvalidArgumentException) {
                            throw $exception;
                        }

                        $result['invalid']++;
                        $result['errors'] = array_slice(array_unique([...$result['errors'], $exception->getMessage()]), 0, 3);
                        LegacyCastAudit::query()->create([
                            'run_id' => $run->id,
                            'staged_url_id' => $url->id,
                            'target_type' => $url->target_type,
                            'target_id' => $url->target_id,
                            'action' => 'recover_queue',
                            'actor_id' => $actor->id,
                            'status' => 'failed',
                            'error_text' => $exception->getMessage(),
                        ]);
                    } finally {
                        $lock->release();
                    }
                }
            });

        return $result;
    }

    private function urlQuery(LegacyMigrationRun $run, string $sourceType): Builder
    {
        return LegacyStagedUrl::query()
            ->where('run_id', $run->id)
            ->whereIn('status', ['queued', 'processing'])
            ->whereIn('root_object_key', LegacyStagedObject::query()->select('object_key')
                ->where('run_id', $run->id)->where('object_type', $sourceType));
    }

    private function queueConfiguration(): array
    {
        $connection = (string) config('queue.default');
        $configuration = (array) config("queue.connections.{$connection}", []);

        if (($configuration['driver'] ?? null) !== 'database') {
            throw new InvalidArgumentException('Khôi phục an toàn hiện chỉ hỗ trợ queue database; chưa thể xác minh job trên connection này.');
        }

        if (DB::connection($configuration['connection'] ?? null)->getName() !== DB::connection()->getName()) {
            throw new InvalidArgumentException('Queue dùng database connection riêng; chưa thể khôi phục nguyên tử cùng staging.');
        }

        $table = (string) ($configuration['table'] ?? 'jobs');

        if (! Schema::hasTable($table)) {
            throw new InvalidArgumentException('Không tìm thấy bảng queue; không thể xác minh URL bị kẹt.');
        }

        return ['connection' => $connection, 'table' => $table];
    }

    private function knownQueuedUrls(array $configuration): array
    {
        $ids = [];
        DB::table($configuration['table'])->where('payload', 'like', '%ProcessLegacyMigrationUrl%')
            ->select(['id', 'payload'])->chunkById(100, function ($jobs) use (&$ids): void {
                foreach ($jobs as $job) {
                    $id = $this->payloadUrlId($job->payload);

                    if ($id !== null) {
                        $ids[$id] = true;
                    }
                }
            });

        return $ids;
    }

    private function queueContainsUrl(array $configuration, int $urlId): bool
    {
        $jobs = DB::table($configuration['table'])
            ->where('payload', 'like', '%ProcessLegacyMigrationUrl%')
            ->where('payload', 'like', '%urlId%;i:'.$urlId.';%')
            ->lockForUpdate()->get(['payload']);

        foreach ($jobs as $job) {
            if ($this->payloadUrlId($job->payload) === $urlId) {
                return true;
            }
        }

        return false;
    }

    private function payloadUrlId(string $payload): ?int
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Payload job migration không hợp lệ; dừng khôi phục để tránh tạo job trùng.');
        }

        if (data_get($decoded, 'data.commandName') !== ProcessLegacyMigrationUrl::class
            && data_get($decoded, 'displayName') !== ProcessLegacyMigrationUrl::class) {
            return null;
        }

        $command = data_get($decoded, 'data.command');

        if (! is_string($command) || ! preg_match('/s:5:"urlId";i:([1-9][0-9]*);/', $command, $matches)) {
            throw new InvalidArgumentException('Không xác minh được URL trong job migration; dừng khôi phục để tránh tạo job trùng.');
        }

        return (int) $matches[1];
    }
}
