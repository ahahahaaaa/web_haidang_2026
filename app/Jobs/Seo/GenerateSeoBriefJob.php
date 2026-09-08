<?php

namespace App\Jobs\Seo;

use App\Models\ContentCluster;
use App\Models\SeoPage;
use App\Services\Seo\SeoGenerationOrchestrator;
use App\Services\Seo\SeoSlugService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSeoBriefJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $clusterId)
    {
        $this->onQueue(config('seo_ai.queue', 'seo'));
    }

    public function handle(
        SeoGenerationOrchestrator $orchestrator,
        SeoSlugService $slugService,
    ): void {
        $cluster = ContentCluster::findOrFail($this->clusterId);

        $brief = $orchestrator->generateBrief($cluster);

        $page = SeoPage::create([
            'content_cluster_id' => $cluster->id,
            'page_type' => $cluster->target_page_type,
            'title' => $brief['title'] ?? $cluster->primary_keyword,
            'slug' => $slugService->generate($brief['slug_suggestion'] ?? $cluster->primary_keyword),
            'canonical_url' => rtrim(config('app.url'), '/') . '/' . $slugService->generate($brief['slug_suggestion'] ?? $cluster->primary_keyword),
            'primary_keyword' => $cluster->primary_keyword,
            'secondary_keywords' => $cluster->secondary_keywords,
            'h1' => $brief['h1'] ?? $cluster->primary_keyword,
            'faq_items' => $brief['faq'] ?? [],
            'generation_payload' => ['brief' => $brief],
            'status' => 'draft',
        ]);

        GenerateSeoDraftJob::dispatch($page->id);
    }
}
