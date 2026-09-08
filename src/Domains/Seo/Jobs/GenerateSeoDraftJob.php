<?php

namespace Src\Domains\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Repositories\SeoPageRepository;
use Src\Domains\Seo\Support\SeoGenerationCoordinator;
use Src\Domains\Seo\Support\SeoMetaFactory;
use Src\Domains\Seo\Support\SeoSchemaFactory;

class GenerateSeoDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $pageId) { $this->onQueue(config('seo_ai.queue', 'seo')); }

    public function handle(SeoPageRepository $pages, SeoGenerationCoordinator $coordinator, SeoMetaFactory $metaFactory, SeoSchemaFactory $schemaFactory): void
    {
        $page = $pages->find($this->pageId);
        $draft = $coordinator->generateDraft($page, $metaFactory->forPage($page), $schemaFactory->forPage($page));

        $page->content = $draft->content;
        $page->meta_title = $draft->meta['meta_title'] ?? null;
        $page->meta_description = $draft->meta['meta_description'] ?? null;
        $page->og_title = $draft->meta['og_title'] ?? null;
        $page->og_description = $draft->meta['og_description'] ?? null;
        $page->schema = $draft->schema;
        $page->generation_payload = array_merge($page->generation_payload ?? [], ['draft_response' => $draft->rawResponse]);
        $page->status = SeoPageStatus::Generated;
        $page->save();

        BuildSeoLinksJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo'));
    }
}
