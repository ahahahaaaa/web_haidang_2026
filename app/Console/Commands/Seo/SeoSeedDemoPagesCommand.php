<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use Src\Domains\Seo\Actions\SeedSeoDemoPagesAction;

class SeoSeedDemoPagesCommand extends Command
{
    protected $signature = 'seo:seed-demo-pages';

    protected $description = 'Create or refresh 10 demo SEO pages for the SEO AI admin flow';

    public function handle(SeedSeoDemoPagesAction $seedDemoPages): int
    {
        $pages = $seedDemoPages->execute();

        $this->info('Demo SEO pages ready: '.count($pages));

        foreach ($pages as $page) {
            $this->line(sprintf(
                '- [%s] %s (%s)',
                $page->page_type->value,
                $page->title,
                $page->slug,
            ));
        }

        return self::SUCCESS;
    }
}
