<?php

namespace App\Modules\LegacyMigration\Jobs;

use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Services\LegacyAutomaticProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Throwable;

class ProcessLegacyMigrationUrl implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 80;

    public int $uniqueFor = 7200;

    public const OVERLAP_LOCK_SECONDS = 100;

    public bool $reuseExistingTarget = false;

    public function __construct(
        public int $urlId,
        public int $actorId,
        public string $sourceType,
        public string $strategy,
        public string $mappingMode,
        public string $mergePolicy,
        public bool $importMedia,
        bool $reuseExistingTarget = false,
    ) {
        $this->reuseExistingTarget = $reuseExistingTarget;
        $this->onQueue((string) config('legacy_migration.queue', 'default'));
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function uniqueId(): string
    {
        return 'legacy-migration-url:'.$this->urlId;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))
                ->releaseAfter(10)
                ->expireAfter(self::OVERLAP_LOCK_SECONDS),
        ];
    }

    public function executionLockKey(): string
    {
        return (new WithoutOverlapping($this->uniqueId()))->getLockKey($this);
    }

    public function handle(LegacyAutomaticProcessor $processor): void
    {
        try {
            $processor->process(
                $this->urlId,
                $this->actorId,
                $this->sourceType,
                $this->strategy,
                $this->mappingMode,
                $this->mergePolicy,
                $this->importMedia,
                $this->reuseExistingTarget,
            );
        } catch (LegacyImageDownloadFailure $exception) {
            $processor->markFailed($this->urlId, $this->actorId, $exception);

            if ($exception->retryable) {
                throw $exception;
            }
        } catch (InvalidArgumentException $exception) {
            $processor->markFailed($this->urlId, $this->actorId, $exception);
        } catch (Throwable $exception) {
            $processor->markFailed($this->urlId, $this->actorId, $exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            app(LegacyAutomaticProcessor::class)->markFailed($this->urlId, $this->actorId, $exception);
        }
    }
}
