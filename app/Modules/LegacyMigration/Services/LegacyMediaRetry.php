<?php

namespace App\Modules\LegacyMigration\Services;

use App\Models\User;
use App\Modules\LegacyMigration\Jobs\ProcessLegacyMigrationUrl;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use Illuminate\Bus\UniqueLock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LegacyMediaRetry
{
    public function __construct(private LegacyAutomaticTargetResolver $resolver) {}

    public function query(LegacyMigrationRun $run): Builder
    {
        return LegacyStagedUrl::query()
            ->where('run_id', $run->id)
            ->where('status', 'casted')
            ->where('target_type', 'blog_post')
            ->whereNotNull('target_id')->where('target_id', '<>', '')
            ->whereIn('mapping_mode', ['cast_preserve_url', 'cast_and_redirect', 'cast_only'])
            ->whereHas('latestMediaAudit', fn (Builder $audit) => $audit->where('status', 'warning'))
            ->whereExists(fn (QueryBuilder $root) => $root->selectRaw('1')
                ->from((new LegacyStagedObject)->getTable().' as media_retry_root')
                ->whereColumn('media_retry_root.run_id', 'legacy_staged_urls.run_id')
                ->whereColumn('media_retry_root.object_key', 'legacy_staged_urls.root_object_key')
                ->where('media_retry_root.object_type', 'blog')->where('media_retry_root.is_partial', false));
    }

    /** @param array<int, int|string> $urlIds
     * @return array{queued: int, skipped: int}
     */
    public function queue(LegacyMigrationRun $run, User $actor, array $urlIds): array
    {
        abort_unless($actor->hasRole('super_admin'), 403);
        $connection = $this->queueConnection();
        $result = ['queued' => 0, 'skipped' => 0];
        $ids = collect($urlIds)->map(fn (mixed $id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique();

        foreach ($ids as $id) {
            $job = new ProcessLegacyMigrationUrl($id, $actor->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);
            $execution = Cache::lock($job->executionLockKey(), ProcessLegacyMigrationUrl::OVERLAP_LOCK_SECONDS);
            if (! $execution->get()) {
                $result['skipped']++;

                continue;
            }
            $unique = Cache::lock(UniqueLock::getKey($job), $job->uniqueFor);
            if (! $unique->get()) {
                $execution->release();
                $result['skipped']++;

                continue;
            }
            $queued = false;
            try {
                $queued = DB::transaction(function () use ($run, $actor, $id, $job, $connection): bool {
                    if ($run->fresh()->status === 'receiving') {
                        throw new InvalidArgumentException('Phiên vẫn đang nhận dữ liệu; chưa thể tải lại ảnh.');
                    }
                    $url = $this->query($run)->with('run')->lockForUpdate()->find($id);
                    if (! $url) {
                        return false;
                    }
                    try {
                        $this->resolver->resolveExisting($url, 'blog');
                    } catch (InvalidArgumentException) {
                        return false;
                    }

                    $url->forceFill(['status' => 'queued', 'merge_policy' => 'overwrite', 'error_text' => null])->save();
                    $job->mappingMode = $url->mapping_mode;
                    $job->onConnection($connection)->beforeCommit();
                    $queueId = Queue::connection($connection)->push($job, '', $job->queue);
                    if (! $queueId) {
                        throw new InvalidArgumentException('Không thể tạo job tải lại ảnh; trạng thái URL được giữ nguyên.');
                    }
                    LegacyCastAudit::query()->create([
                        'run_id' => $run->id,
                        'staged_url_id' => $url->id,
                        'target_type' => $url->target_type,
                        'target_id' => $url->target_id,
                        'action' => 'retry_media',
                        'merge_policy' => 'overwrite',
                        'timestamp_policy' => 'source',
                        'actor_id' => $actor->id,
                        'status' => 'completed',
                        'after_json' => ['queue_job_id' => $queueId, 'reuse_existing_target' => true, 'import_media' => true],
                    ]);

                    return true;
                }, 5);
            } finally {
                if (! $queued) {
                    $unique->release();
                }
                $execution->release();
            }
            $result[$queued ? 'queued' : 'skipped']++;
        }

        return $result;
    }

    public function unavailableReason(): ?string
    {
        try {
            $this->queueConnection();

            return null;
        } catch (InvalidArgumentException $exception) {
            return $exception->getMessage();
        }
    }

    private function queueConnection(): string
    {
        $connection = (string) config('queue.default');
        $configuration = (array) config("queue.connections.{$connection}", []);
        if (($configuration['driver'] ?? null) !== 'database'
            || DB::connection($configuration['connection'] ?? null)->getName() !== DB::connection()->getName()) {
            throw new InvalidArgumentException('Tải lại ảnh an toàn cần queue database dùng cùng database connection với staging.');
        }
        if (! Schema::hasTable((string) ($configuration['table'] ?? 'jobs'))) {
            throw new InvalidArgumentException('Không tìm thấy bảng queue; chưa thể tải lại ảnh.');
        }

        return $connection;
    }
}
