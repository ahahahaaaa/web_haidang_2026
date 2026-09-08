<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use Src\Domains\Seo\Jobs\PublishSeoPageJob;

class SeoPublishCommand extends Command
{
    protected $signature = 'seo:publish {pageId}';
    protected $description = 'Publish an approved SEO page';

    public function handle(): int
    {
        PublishSeoPageJob::dispatch((int) $this->argument('pageId'))->onQueue(config('seo_ai.queue', 'seo'));
        $this->info('Publish dispatched.');
        return self::SUCCESS;
    }
}
