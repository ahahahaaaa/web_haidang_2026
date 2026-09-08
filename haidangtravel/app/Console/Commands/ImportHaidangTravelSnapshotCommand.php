<?php

namespace App\Console\Commands;

use App\Services\Travel\HaidangTravelImportService;
use Illuminate\Console\Command;

class ImportHaidangTravelSnapshotCommand extends Command
{
    protected $signature = 'travel:import-haidang {--refresh : Refresh snapshot from haidangtravel.com before importing} {--path= : Custom snapshot path}';

    protected $description = 'Import the deterministic Haidang Travel launch dataset into the CMS.';

    public function handle(HaidangTravelImportService $service): int
    {
        $path = $this->option('path') ?: null;
        $snapshot = $service->loadSnapshot($path);

        if ($this->option('refresh')) {
            $snapshot = $service->fetchSnapshotFromSource();
            $storedPath = $service->storeSnapshot($snapshot, $path);
            $this->info('Snapshot refreshed: '.$storedPath);
        }

        $service->import($snapshot, $path);

        $this->info('Haidang Travel dataset imported successfully.');

        return self::SUCCESS;
    }
}
