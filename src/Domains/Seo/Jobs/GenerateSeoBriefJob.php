<?php

namespace Src\Domains\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Seo\Enums\SeoClusterStatus;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Repositories\SeoClusterRepository;
use Src\Domains\Seo\Repositories\SeoPageRepository;
use Src\Domains\Seo\Support\SeoGenerationCoordinator;
use Src\Domains\Seo\Support\SeoSlugGenerator;
use Src\Domains\Seo\Support\SeoUrlBuilder;

class GenerateSeoBriefJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $clusterId) { $this->onQueue(config('seo_ai.queue', 'seo')); }

    public function handle(SeoClusterRepository $clusters, SeoPageRepository $pages, SeoGenerationCoordinator $coordinator, SeoSlugGenerator $slugGenerator, SeoUrlBuilder $urlBuilder): void
    {
        $cluster = $clusters->find($this->clusterId);
        $cluster->status = SeoClusterStatus::Generating;
        $cluster->save();

        $brief = $coordinator->generateBrief($cluster);
        $slug = $slugGenerator->generate($brief->slugSuggestion ?: $cluster->primary_keyword);

        $page = $pages->create([
            'content_cluster_id' => $cluster->getKey(),
            'page_type' => $cluster->target_page_type,
            'title' => $brief->title ?: $cluster->primary_keyword,
            'slug' => $slug,
            'canonical_url' => $urlBuilder->canonicalUrl($cluster->target_page_type, $slug),
            'primary_keyword' => $cluster->primary_keyword,
            'secondary_keywords' => $cluster->secondary_keywords,
            'h1' => $brief->h1 ?: $cluster->primary_keyword,
            'faq_items' => $brief->faq,
            'generation_payload' => ['brief' => ['outline' => $brief->outline, 'trust_signals' => $brief->trustSignals, 'cta' => $brief->cta]],
            'status' => SeoPageStatus::Draft,
        ]);

        $cluster->status = SeoClusterStatus::Completed;
        $cluster->save();

        GenerateSeoDraftJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo'));
    }
}
