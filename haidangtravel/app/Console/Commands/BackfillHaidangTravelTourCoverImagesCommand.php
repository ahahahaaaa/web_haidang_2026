<?php

namespace App\Console\Commands;

use App\Services\Travel\HaidangTravelTourCoverBackfillService;
use Illuminate\Console\Command;

class BackfillHaidangTravelTourCoverImagesCommand extends Command
{
    protected $signature = 'travel:backfill-tour-cover-images
        {--refresh : Crawl fresh snapshot data from haidangtravel.com before backfilling}
    ';

    protected $description = 'Backfill missing Haidang Travel tour cover images from snapshot/crawled demo data.';

    public function handle(HaidangTravelTourCoverBackfillService $service): int
    {
        $summary = $service->backfill((bool) $this->option('refresh'));

        if (filled($summary['snapshot_path'] ?? null)) {
            $this->info('Snapshot refreshed: '.$summary['snapshot_path']);
        }

        $this->line('Tours inspected: '.$summary['inspected']);
        $this->line('Tours already had image: '.$summary['already_had']);
        $this->info('Tours updated: '.$summary['updated']);
        $this->warn('Tours still missing image: '.$summary['still_missing']);

        return self::SUCCESS;
    }
}
