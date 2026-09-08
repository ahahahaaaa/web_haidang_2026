<?php

namespace Src\Domains\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Repositories\SeoPageRepository;
use Src\Domains\Seo\Support\SeoQaValidator;
use Src\Domains\Seo\Support\SeoSchemaFactory;

class ValidateSeoPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $pageId) { $this->onQueue(config('seo_ai.queue', 'seo')); }

    public function handle(SeoPageRepository $pages, SeoQaValidator $validator, SeoSchemaFactory $schemaFactory): void
    {
        $page = $pages->find($this->pageId)
            ->load(['outgoingLinks.targetPage.cluster'])
            ->loadCount('outgoingLinks');
        $page->schema = $schemaFactory->forPage($page);
        $report = $validator->validate($page, (int) $page->outgoing_links_count);
        $page->qa_report = $report;
        $page->status = $report['status'] === 'pass' ? SeoPageStatus::PendingReview : SeoPageStatus::QaFailed;
        $page->save();
    }
}
