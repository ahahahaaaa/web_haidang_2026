<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use Src\Domains\Seo\Actions\DispatchClusterGenerationAction;
use Src\Domains\Seo\Repositories\SeoClusterRepository;

class SeoSyncClustersCommand extends Command
{
    protected $signature = 'seo:sync-clusters';
    protected $description = 'Dispatch generation jobs for approved content clusters';

    public function handle(SeoClusterRepository $clusters, DispatchClusterGenerationAction $dispatchGeneration): int
    {
        $count = 0;
        foreach ($clusters->approvedForSync()->get() as $cluster) {
            $dispatchGeneration->execute($cluster);
            $count++;
        }
        $this->info("Dispatched {$count} cluster(s).");
        return self::SUCCESS;
    }
}
