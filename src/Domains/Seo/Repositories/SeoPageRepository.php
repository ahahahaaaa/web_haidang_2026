<?php

namespace Src\Domains\Seo\Repositories;

use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoPage;

class SeoPageRepository
{
    public function find(int $id): SeoPage { return SeoPage::query()->findOrFail($id); }
    public function create(array $attributes): SeoPage { return SeoPage::query()->create($attributes); }
    public function relatedTargetsFor(SeoPage $page, int $limit = 4) {
        return SeoPage::query()->whereKeyNot($page->getKey())->where('status', '!=', 'archived')->limit($limit)->get();
    }

    public function findPublishedBySlug(string $slug, string|array|null $expectedType = null): SeoPage
    {
        return SeoPage::query()
            ->with([
                'cluster',
                'outgoingLinks.targetPage.cluster',
            ])
            ->where('slug', $slug)
            ->where('status', SeoPageStatus::Published->value)
            ->when($expectedType !== null, function ($query) use ($expectedType) {
                if (is_array($expectedType)) {
                    $query->whereIn('page_type', $expectedType);

                    return;
                }

                $query->where('page_type', $expectedType);
            })
            ->where('page_type', '!=', SeoPageType::Homepage->value)
            ->firstOrFail();
    }

    public function publishedForSitemap()
    {
        return SeoPage::query()
            ->where('status', SeoPageStatus::Published->value)
            ->where('page_type', '!=', SeoPageType::Homepage->value)
            ->orderByDesc('published_at')
            ->get();
    }
}
