<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use Src\Domains\Seo\Jobs\ValidateSeoPageJob;

class SeoRunQaCommand extends Command
{
    protected $signature = 'seo:qa {pageId}';
    protected $description = 'Run SEO QA against a page';

    public function handle(): int
    {
        ValidateSeoPageJob::dispatch((int) $this->argument('pageId'))->onQueue(config('seo_ai.queue', 'seo'));
        $this->info('QA dispatched.');
        return self::SUCCESS;
    }
}
