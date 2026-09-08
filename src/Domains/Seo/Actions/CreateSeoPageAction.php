<?php

namespace Src\Domains\Seo\Actions;

use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoSlugGenerator;
use Src\Domains\Seo\Support\SeoUrlBuilder;

class CreateSeoPageAction
{
    public function __construct(
        protected SeoSlugGenerator $slugGenerator,
        protected SeoUrlBuilder $urlBuilder,
    ) {}

    public function execute(array $payload): SeoPage
    {
        $primaryKeyword = trim((string) ($payload['primary_keyword'] ?? ''));
        $title = trim((string) ($payload['title'] ?? '')) ?: $primaryKeyword;
        $h1 = trim((string) ($payload['h1'] ?? '')) ?: $title;
        $slugSeed = trim((string) ($payload['slug'] ?? '')) ?: $title ?: $primaryKeyword;
        $slug = $this->slugGenerator->generate($slugSeed);

        return SeoPage::query()->create([
            'content_cluster_id' => $payload['content_cluster_id'] ?? null,
            'page_type' => $payload['page_type'],
            'title' => $title,
            'slug' => $slug,
            'canonical_url' => $this->urlBuilder->canonicalUrl($payload['page_type'] ?? null, $slug),
            'primary_keyword' => $primaryKeyword,
            'secondary_keywords' => array_values(array_unique(array_filter($payload['secondary_keywords'] ?? []))),
            'h1' => $h1,
            'excerpt' => $payload['excerpt'] ?? null,
            'content' => $payload['content'] ?? null,
            'meta_title' => $payload['meta_title'] ?? null,
            'meta_description' => $payload['meta_description'] ?? null,
            'og_title' => $payload['og_title'] ?? null,
            'og_description' => $payload['og_description'] ?? null,
            'og_image' => $payload['og_image'] ?? null,
            'schema' => $payload['schema'] ?? null,
            'faq_items' => $payload['faq_items'] ?? null,
            'generation_payload' => array_merge(
                ['source' => 'manual_admin_create'],
                $payload['generation_payload'] ?? [],
            ),
            'status' => $payload['status'] ?? SeoPageStatus::Draft,
        ]);
    }
}
