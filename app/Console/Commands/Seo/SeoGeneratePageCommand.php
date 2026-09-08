<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use Src\Domains\Seo\Jobs\GenerateSeoDraftJob;

class SeoGeneratePageCommand extends Command
{
    protected $signature = 'seo:generate-page {pageId}';
    protected $description = 'Generate or regenerate an SEO page draft';

    public function handle(): int
    {
        GenerateSeoDraftJob::dispatch((int) $this->argument('pageId'))->onQueue(config('seo_ai.queue', 'seo'));
        $this->info('Generation dispatched.');
        return self::SUCCESS;
    }
}
