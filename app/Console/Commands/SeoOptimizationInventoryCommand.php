<?php

namespace App\Console\Commands;

use App\Services\SeoOptimization\PageRegistryService;
use Illuminate\Console\Command;

class SeoOptimizationInventoryCommand extends Command
{
    protected $signature = 'seo-optimize:inventory';

    protected $description = 'Đối soát URL travel vào registry SEO, không thay đổi nội dung public';

    public function handle(PageRegistryService $registry): int
    {
        $this->line(json_encode($registry->sync(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
