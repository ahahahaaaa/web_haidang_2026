<?php

namespace Src\Domains\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Seo\Repositories\SeoLinkRepository;
use Src\Domains\Seo\Repositories\SeoPageRepository;

class BuildSeoLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $pageId) { $this->onQueue(config('seo_ai.queue', 'seo')); }

    public function handle(SeoPageRepository $pages, SeoLinkRepository $links): void
    {
        $page = $pages->find($this->pageId);
        foreach ($pages->relatedTargetsFor($page, 4) as $target) {
            $links->suggest($page, $target, $target->primary_keyword);
        }
        ValidateSeoPageJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo'));
    }
}
