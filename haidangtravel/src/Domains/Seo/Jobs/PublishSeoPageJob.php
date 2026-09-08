<?php

namespace Src\Domains\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Seo\Repositories\SeoPageRepository;
use Src\Domains\Seo\Support\SeoPublisher;

class PublishSeoPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $pageId) { $this->onQueue(config('seo_ai.queue', 'seo')); }

    public function handle(SeoPageRepository $pages, SeoPublisher $publisher): void
    {
        $publisher->publish($pages->find($this->pageId));
    }
}
