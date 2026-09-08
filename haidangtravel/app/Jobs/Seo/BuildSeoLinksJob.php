<?php

namespace App\Jobs\Seo;

use App\Models\SeoLink;
use App\Models\SeoPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildSeoLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $pageId)
    {
        $this->onQueue(config('seo_ai.queue', 'seo'));
    }

    public function handle(): void
    {
        $page = SeoPage::findOrFail($this->pageId);

        $targets = SeoPage::query()
            ->whereKeyNot($page->id)
            ->where('status', '!=', 'archived')
            ->limit(4)
            ->get();

        foreach ($targets as $target) {
            SeoLink::firstOrCreate([
                'source_page_id' => $page->id,
                'target_page_id' => $target->id,
                'anchor_text' => $target->primary_keyword,
            ], [
                'link_type' => 'related',
                'priority' => 50,
                'status' => 'suggested',
            ]);
        }

        ValidateSeoPageJob::dispatch($page->id);
    }
}
