<?php

namespace App\Console\Commands;

use App\Services\Travel\HaidangTravelHomepageSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncHaidangTravelHomepageCommand extends Command
{
    protected $signature = 'travel:sync-homepage-source {--html= : Đường dẫn HTML local để debug parser mà không gọi ra site nguồn}';

    protected $description = 'Đồng bộ slider bannertop và chủ đề tour trên homepage từ haidangtravel.com về CMS local.';

    public function handle(HaidangTravelHomepageSyncService $service): int
    {
        try {
            $summary = $service->sync($this->option('html') ?: null);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã đồng bộ homepage source thành công.');
        $this->line('HTML lưu tại: '.$summary['html_path']);
        $this->line('Slider bannertop parse được: '.$summary['hero_slides_parsed'].' slide');
        $this->line('Slider local đã sync: '.$summary['hero_items_synced'].' item');
        $this->line('Carousel chủ đề parse được: '.$summary['categories_parsed'].' mục');
        $this->line('Tour category đã sync: '.$summary['categories_synced'].' mục');
        $this->line('Tour được recategorize: '.$summary['tours_recategorized'].' bản ghi');

        return self::SUCCESS;
    }
}
