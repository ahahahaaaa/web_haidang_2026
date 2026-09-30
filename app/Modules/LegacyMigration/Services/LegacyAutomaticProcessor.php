<?php

namespace App\Modules\LegacyMigration\Services;

use App\Models\User;
use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class LegacyAutomaticProcessor
{
    public function __construct(
        private LegacyAutomaticTargetResolver $resolver,
        private LegacyTargetRegistry $targets,
        private LegacyContentCaster $caster,
        private LegacyRunCounter $counter,
    ) {}

    public function process(
        int $urlId,
        int $actorId,
        string $sourceType,
        string $strategy,
        string $mappingMode,
        string $mergePolicy,
        bool $importMedia,
        bool $reuseExistingTarget = false,
    ): void {
        $actor = User::query()->findOrFail($actorId);
        $url = LegacyStagedUrl::query()->with('run')->findOrFail($urlId);

        if ($url->run->status === 'receiving') {
            throw new InvalidArgumentException('Phiên chưa finalize; chưa được phép xử lý tự động.');
        }

        if ($url->status === 'casted' && ! $importMedia) {
            return;
        }

        $url->forceFill(['status' => 'processing', 'error_text' => null])->save();
        $target = $reuseExistingTarget
            ? $this->resolver->resolveExisting($url, $sourceType)
            : $this->resolver->resolve($url, $sourceType, $strategy, $actor);
        $targetType = $this->resolver->targetType($sourceType);
        $targetPath = $this->targets->path($targetType, $target);
        $alreadyCasted = $url->casted_at !== null;
        $shouldCast = $reuseExistingTarget || ! $alreadyCasted || $importMedia;

        $url->forceFill([
            'mapping_mode' => $mappingMode,
            'merge_policy' => $mergePolicy,
            'timestamp_policy' => 'source',
            'target_type' => $targetType,
            'target_id' => (string) $target->getKey(),
            'target_route' => $targetType,
            'target_path' => $targetPath,
            'redirect_code' => $reuseExistingTarget ? $url->redirect_code : 301,
            'status' => $shouldCast ? 'mapped' : 'processing',
            'mapped_by' => $reuseExistingTarget ? ($url->mapped_by ?: $actor->id) : $actor->id,
            'mapped_at' => $url->mapped_at ?: now(),
            'error_text' => null,
        ])->save();

        if ($shouldCast) {
            $url = $this->caster->cast($url, $actor, importMedia: $importMedia);
        }

        $url->forceFill(['status' => 'casted', 'error_text' => null])->save();
        $this->counter->refreshAfterCast($url->run);
    }

    public function markFailed(int $urlId, int $actorId, Throwable $exception): void
    {
        $url = LegacyStagedUrl::query()->with('run')->find($urlId);

        if (! $url) {
            return;
        }

        $message = $this->failureMessage($exception);

        if ($url->status === 'failed' && hash_equals((string) $url->error_text, $message)) {
            $this->counter->refresh($url->run);

            return;
        }

        $url->forceFill(['status' => 'failed', 'error_text' => $message])->save();
        LegacyCastAudit::query()->create([
            'run_id' => $url->run_id,
            'staged_url_id' => $url->id,
            'target_type' => $url->target_type,
            'target_id' => $url->target_id,
            'action' => 'auto_process',
            'merge_policy' => $url->merge_policy,
            'timestamp_policy' => $url->timestamp_policy,
            'actor_id' => User::query()->whereKey($actorId)->value('id'),
            'status' => 'failed',
            'error_text' => $message,
            'after_json' => $exception instanceof LegacyImageDownloadFailure ? $exception->context() : null,
        ]);
        $this->counter->refresh($url->run);
    }

    private function failureMessage(Throwable $exception): string
    {
        $message = $exception instanceof ValidationException
            ? collect($exception->errors())->flatten()->filter(fn (mixed $item): bool => is_string($item))->implode(' ')
            : $exception->getMessage();
        $message = trim((string) $message);

        return mb_substr($message !== '' ? $message : 'Xử lý migrate thất bại không có thông báo chi tiết.', 0, 2000);
    }
}
