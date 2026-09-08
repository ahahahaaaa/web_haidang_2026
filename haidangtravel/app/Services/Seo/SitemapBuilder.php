<?php

namespace App\Services\Seo;

use App\Services\Frontsite\FrontsiteCache;
use App\Support\ContentCategoryTree;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class SitemapBuilder
{
    public function __construct(protected FrontsiteCache $cache)
    {
    }

    public function build(): array
    {
        return $this->cache->rememberFlexible(
            'sitemap:entries',
            ['sitemap'],
            (array) config('frontsite_cache.stale.sitemap', [
                $this->cache->ttl('sitemap'),
                $this->cache->ttl('sitemap') * 6,
            ]),
            fn (): array => $this->buildFresh(),
        );
    }

    protected function buildFresh(): array
    {
        $landings = LandingPage::query()
            ->whereIn('page_key', ['home', 'about', 'contact', 'services', 'blog', 'domestic_tours', 'international_tours', 'group_tours'])
            ->get()
            ->keyBy('page_key');
        $blogCategoryTree = ContentCategoryTree::applyBranchCounts(
            ContentCategory::query()
                ->forTaxonomy('blog')
                ->withCount(['blogPosts as blog_posts_count' => fn ($blogQuery) => $blogQuery->published()])
                ->ordered()
                ->get()
        );
        $flattenCategoryTree = function ($nodes) use (&$flattenCategoryTree) {
            return collect($nodes)
                ->flatMap(fn (ContentCategory $category) => collect([$category])->concat(
                    $flattenCategoryTree($category->relationLoaded('children') ? $category->getRelation('children') : collect())
                ))
                ->values();
        };
        $blogCategoryEntries = $flattenCategoryTree($blogCategoryTree)
            ->filter(fn (ContentCategory $category) => (int) ($category->branch_blog_posts_count ?? 0) > 0)
            ->map(fn (ContentCategory $category) => $this->entry(route('blog-categories.show', ['slug' => $category->slug]), $category->updated_at, 'weekly', 0.6));

        return collect([
            $this->entry(route('home'), $landings->get('home')?->updated_at, 'weekly', 1.0),
            $this->entry(route('about'), $landings->get('about')?->updated_at, 'monthly', 0.7),
            $this->entry(route('contact'), $landings->get('contact')?->updated_at, 'monthly', 0.7),
            $this->entry(route('services.index'), $landings->get('services')?->updated_at, 'weekly', 0.8),
            $this->entry(route('blog.index'), $landings->get('blog')?->updated_at, 'weekly', 0.8),
            $this->entry(route(TourScope::Domestic->routeName()), $landings->get('domestic_tours')?->updated_at, 'weekly', 0.9),
            $this->entry(route(TourScope::International->routeName()), $landings->get('international_tours')?->updated_at, 'weekly', 0.9),
            $this->entry(route(TourScope::Group->routeName()), $landings->get('group_tours')?->updated_at, 'weekly', 0.8),
        ])
            ->merge(
                Tour::query()
                    ->published()
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Tour $tour) => $this->entry(route('tours.show', $tour), $tour->updated_at, 'weekly', 0.8))
            )
            ->merge(
                TourCategory::query()
                    ->published()
                    ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (TourCategory $category) => $this->entry(route('tour-categories.show', $category), $category->updated_at, 'weekly', 0.7))
            )
            ->merge(
                Destination::query()
                    ->published()
                    ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Destination $destination) => $this->entry(route('destinations.show', $destination), $destination->updated_at, 'weekly', 0.7))
            )
            ->merge(
                Region::query()
                    ->published()
                    ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Region $region) => $this->entry(route('regions.show', $region), $region->updated_at, 'weekly', 0.7))
            )
            ->merge(
                ContentCategory::query()
                    ->forTaxonomy('service')
                    ->whereHas('services', fn ($serviceQuery) => $serviceQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (ContentCategory $category) => $this->entry(route('service-categories.show', ['category' => $category->slug]), $category->updated_at, 'weekly', 0.7))
            )
            ->merge(
                Service::query()
                    ->published()
                    ->orderBy('title')
                    ->get()
                    ->map(fn (Service $service) => $this->entry(route('services.show', $service), $service->updated_at, 'weekly', 0.7))
            )
            ->merge($blogCategoryEntries)
            ->merge(
                BlogPost::query()
                    ->published()
                    ->latest('published_at')
                    ->get()
                    ->map(fn (BlogPost $post) => $this->entry(route('blog.show', $post), $post->updated_at ?? $post->published_at, 'monthly', 0.6))
            )
            ->unique('url')
            ->values()
            ->all();
    }

    protected function entry(string $url, mixed $lastModified, string $changeFrequency, float $priority): array
    {
        return [
            'changefreq' => $changeFrequency,
            'lastmod' => $lastModified?->toAtomString(),
            'priority' => number_format($priority, 1, '.', ''),
            'url' => $url,
        ];
    }
}
