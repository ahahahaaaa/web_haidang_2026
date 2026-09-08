<?php

namespace App\Jobs\Seo;

use App\Models\SeoPage;
use App\Services\Seo\SeoGenerationOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSeoDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $pageId)
    {
        $this->onQueue(config('seo_ai.queue', 'seo'));
    }

    public function handle(SeoGenerationOrchestrator $orchestrator): void
    {
        $page = SeoPage::findOrFail($this->pageId);

        $result = $orchestrator->generateDraft($page);

        $page->content = $result['content'];
        $page->meta_title = $result['meta']['meta_title'] ?? null;
        $page->meta_description = $result['meta']['meta_description'] ?? null;
        $page->og_title = $result['meta']['og_title'] ?? null;
        $page->og_description = $result['meta']['og_description'] ?? null;
        $page->schema = $result['schema'];
        $page->generation_payload = array_merge($page->generation_payload ?? [], [
            'draft_response' => $result['raw_response'],
        ]);
        $page->status = 'generated';
        $page->save();

        BuildSeoLinksJob::dispatch($page->id);
    }
}
