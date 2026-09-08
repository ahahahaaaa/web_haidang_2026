<?php

namespace App\Jobs\Travel;

use App\Services\Travel\TourAgencyPushSyncService;
use App\Services\Travel\TourAgencyPushSyncRunService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Src\Domains\Cms\Models\TourAgencyPushSyncRun;
use Throwable;

class PushTourToAgencyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<int, array<string, mixed>>  $deletedDepartures
     */
    public function __construct(
        public int $tourId,
        public array $deletedDepartures = [],
        public ?int $runId = null,
    ) {
        $this->onQueue((string) config('tour_sync.push_queue', 'default'));
    }

    public function handle(TourAgencyPushSyncService $syncService, TourAgencyPushSyncRunService $runService): void
    {
        if ($this->runId) {
            $run = TourAgencyPushSyncRun::query()->find($this->runId);

            if ($run) {
                $runService->execute($run);

                return;
            }

            Log::notice('tour_sync.master_data_dashboard.run_missing', [
                'source' => 'haidangtravel_cms',
                'target' => 'api_master_data_dashboard',
                'event' => 'run_missing',
                'run_id' => $this->runId,
                'cms_tour_id' => $this->tourId,
                'queue_name' => (string) config('tour_sync.push_queue', 'default'),
            ]);

            return;
        }

        try {
            $syncService->push($this->tourId, $this->deletedDepartures);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
