<?php

namespace App\Jobs\Seo;

use App\Models\SeoPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class PublishSeoPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $pageId)
    {
        $this->onQueue(config('seo_ai.queue', 'seo'));
    }

    public function handle(): void
    {
        $page = SeoPage::findOrFail($this->pageId);

        if (($page->qa_report['status'] ?? null) !== 'pass') {
            throw new RuntimeException('Cannot publish page that failed SEO QA.');
        }

        if (!in_array($page->status, ['approved', 'pending_review'], true)) {
            throw new RuntimeException('Page is not ready for publish.');
        }

        $page->status = 'published';
        $page->published_at = now();
        $page->save();
    }
}
