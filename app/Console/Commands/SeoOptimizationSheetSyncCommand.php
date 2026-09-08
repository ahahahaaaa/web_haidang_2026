<?php

namespace App\Console\Commands;

use App\Models\SeoOptimizationOutbox;
use App\Services\SeoOptimization\OptimizationAutomationService;
use Illuminate\Console\Command;

class SeoOptimizationSheetSyncCommand extends Command
{
    protected $signature = 'seo-optimize:sheet-sync {--limit=50 : Tối đa 100 sự kiện mỗi lượt}';

    protected $description = 'Thử gửi lại kết quả tự động chưa có ACK của Google Sheet; không áp dụng lại nội dung';

    public function handle(OptimizationAutomationService $automation): int
    {
        $events = SeoOptimizationOutbox::query()->where('destination', '18_AUTOMATION_QUEUE')->where('status', 'pending')
            ->oldest()->limit(max(1, min(100, (int) $this->option('limit'))))->get();
        foreach ($events as $event) {
            $automation->deliver($event);
        }
        $pending = $events->filter(fn ($event) => $event->fresh()->status !== 'synced')->count();
        $this->info('Đã xử lý '.$events->count().' sự kiện; còn '.$pending.' sự kiện chưa đồng bộ trong lượt này.');

        return $pending > 0 ? self::FAILURE : self::SUCCESS;
    }
}
