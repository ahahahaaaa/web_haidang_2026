<?php

namespace App\Services\Travel;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourAgencyPushSyncRun;
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Throwable;

class TourAgencyPushSyncRunService
{
    public function __construct(protected TourAgencyPushSyncService $syncService) {}

    /**
     * @param  array<int, array<string, mixed>>  $deletedDepartures
     */
    public function enqueue(Tour $tour, array $deletedDepartures = [], string $trigger = 'tour_saved', ?int $actorId = null): ?TourAgencyPushSyncRun
    {
        if (! (bool) config('tour_sync.push_enabled', true)) {
            return null;
        }

        $sourceState = $this->sourceState($tour);

        if (! $sourceState) {
            return null;
        }

        $run = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'created_by_user_id' => $actorId,
            'tour_title' => $tour->title,
            'source_tour_id' => $sourceState->source_tour_id,
            'tour_code' => $sourceState->tour_code,
            'trigger' => $trigger,
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
            'queue_name' => (string) config('tour_sync.push_queue', 'default'),
            'deleted_departures' => $deletedDepartures,
            'queued_at' => now(),
        ]);

        Log::info('tour_sync.master_data_dashboard.queued', [
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => 'queued',
            'run_id' => $run->getKey(),
            'cms_tour_id' => $tour->getKey(),
            'source_tour_id' => $sourceState->source_tour_id,
            'tour_code' => $sourceState->tour_code,
            'tour_title' => $tour->title,
            'trigger' => $trigger,
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
            'queue_name' => (string) config('tour_sync.push_queue', 'default'),
            'deleted_startdates_count' => count($deletedDepartures),
            'deleted_startdate_ids' => collect($deletedDepartures)
                ->pluck('startdate_id')
                ->filter()
                ->values()
                ->all(),
            'created_by_user_id' => $actorId,
            'queued_at' => optional($run->queued_at)->toIso8601String(),
        ]);

        return $run;
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(TourAgencyPushSyncRun $run): array
    {
        $run->refresh();
        $run->forceFill([
            'status' => TourAgencyPushSyncRun::STATUS_RUNNING,
            'attempts' => ((int) $run->attempts) + 1,
            'started_at' => now(),
            'finished_at' => null,
            'last_error' => null,
        ])->save();

        Log::info('tour_sync.master_data_dashboard.processing_started', $this->runLogContext($run, [
            'event' => 'processing_started',
            'status' => TourAgencyPushSyncRun::STATUS_RUNNING,
        ]));

        try {
            $summary = $run->tour_id
                ? $this->syncService->push(
                    (int) $run->tour_id,
                    $run->deleted_departures ?? [],
                    ['mapped_departures_only' => $run->trigger === 'manual_resync_existing'],
                )
                : ['skipped' => true, 'reason' => 'missing_tour'];
            $status = (bool) ($summary['skipped'] ?? false)
                ? TourAgencyPushSyncRun::STATUS_SKIPPED
                : TourAgencyPushSyncRun::STATUS_SUCCEEDED;

            $run->forceFill([
                'status' => $status,
                'summary' => $summary,
                'finished_at' => now(),
            ])->save();

            Log::info('tour_sync.master_data_dashboard.processing_finished', $this->runLogContext($run, [
                'event' => 'processing_finished',
                'status' => $status,
                'summary' => $summary,
            ]));

            return ['status' => $status, 'summary' => $summary];
        } catch (Throwable $exception) {
            report($exception);

            $run->forceFill([
                'status' => TourAgencyPushSyncRun::STATUS_FAILED,
                'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                'finished_at' => now(),
            ])->save();

            Log::error('tour_sync.master_data_dashboard.processing_failed', $this->runLogContext($run, [
                'event' => 'processing_failed',
                'status' => TourAgencyPushSyncRun::STATUS_FAILED,
                'error_message' => Str::limit($exception->getMessage(), 2000, ''),
                'exception_class' => $exception::class,
            ]));

            return [
                'status' => TourAgencyPushSyncRun::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ];
        }
    }

    protected function sourceState(Tour $tour): ?TourDepartureSyncState
    {
        return TourDepartureSyncState::query()
            ->where('tour_id', $tour->getKey())
            ->whereNotNull('source_tour_id')
            ->latest('last_synced_at')
            ->latest('updated_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function runLogContext(TourAgencyPushSyncRun $run, array $extra = []): array
    {
        $deletedDepartures = $run->deleted_departures ?? [];

        return array_merge([
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'run_id' => $run->getKey(),
            'cms_tour_id' => $run->tour_id,
            'source_tour_id' => $run->source_tour_id,
            'tour_code' => $run->tour_code,
            'tour_title' => $run->tour_title,
            'trigger' => $run->trigger,
            'queue_name' => $run->queue_name,
            'attempts' => (int) $run->attempts,
            'deleted_startdates_count' => count($deletedDepartures),
            'deleted_startdate_ids' => collect($deletedDepartures)
                ->pluck('startdate_id')
                ->filter()
                ->values()
                ->all(),
            'created_by_user_id' => $run->created_by_user_id,
            'queued_at' => optional($run->queued_at)->toIso8601String(),
            'started_at' => optional($run->started_at)->toIso8601String(),
            'finished_at' => optional($run->finished_at)->toIso8601String(),
        ], $extra);
    }
}
