<?php

namespace App\Services\Seo;

use App\Services\Frontsite\FrontsiteCache;
use App\Support\ContentCategoryTree;
use App\Support\FrontsiteUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class SitemapBuilder
{
    public const TYPES = ['pages', 'tours', 'tour-categories', 'destinations', 'countries', 'regions', 'services', 'service-categories', 'blogs', 'blog-categories', 'landings'];

    public const PAGE_SIZE = 10000;

    public function __construct(protected FrontsiteCache $cache) {}

    public function build(): array
    {
        return $this->cache->rememberFlexible(
            'sitemap:entries:v4:'.sha1(FrontsiteUrls::canonicalBaseUrl().':'.(int) config('public_url_mappings.enabled')),
            ['sitemap', 'settings'],
            (array) config('frontsite_cache.stale.sitemap', [
                $this->cache->ttl('sitemap'),
                $this->cache->ttl('sitemap') * 6,
            ]),
            fn (): array => $this->buildFresh(),
        );
    }

    public function index(): array
    {
        return collect($this->build())
            ->groupBy('type')
            ->flatMap(fn ($entries, string $type) => $entries->chunk(self::PAGE_SIZE)
                ->values()
                ->map(fn ($chunk, int $page) => [
                    'url' => FrontsiteUrls::canonicalUrl($page === 0
                        ? route('sitemap.type', ['type' => $type])
                        : route('sitemap.type.page', ['type' => $type, 'page' => $page + 1])),
                ]))
            ->values()
            ->all();
    }

    public function forType(string $type, int $page = 1): array
    {
        abort_unless(in_array($type, self::TYPES, true) && $page > 0, 404);

        $entries = collect($this->build())->where('type', $type)->values();
        abort_if($page > max(1, (int) ceil($entries->count() / self::PAGE_SIZE)), 404);

        return $entries->slice(($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE)->values()->all();
    }

    protected function buildFresh(): array
    {
        if ($this->noindex(SiteSetting::query()->value('seo_robots'))) {
            return [];
        }

        $landings = LandingPage::query()
            ->whereIn('page_key', ['home', 'about', 'contact', 'services', 'blog', 'domestic_tours', 'international_tours', 'group_tours'])
            ->get()
            ->keyBy('page_key');
        $customLandingEntries = LandingPage::query()
            ->select(['id', 'slug', 'updated_at', 'canonical_url', 'robots_directive'])
            ->whereNull('page_key')
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(function ($query): void {
                $query
                    ->whereNull('robots_directive')
                    ->orWhere('robots_directive', 'not like', '%noindex%');
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (LandingPage $landing) => $this->modelEntry(
                $landing,
                route('landing.show', ['slug' => $landing->slug]),
                'landings',
                'landing_page',
                'weekly',
                0.6,
                true,
            ));
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
            ->map(fn (ContentCategory $category) => $this->modelEntry($category, route('blog-categories.show', ['slug' => $category->slug]), 'blog-categories', 'blog_category', 'weekly', 0.6));

        $entries = collect([
            $this->entry(route('home'), $landings->get('home')?->updated_at, 'weekly', 1.0),
            $this->entry(route('about'), $landings->get('about')?->updated_at, 'monthly', 0.7),
            $this->entry(route('contact'), $landings->get('contact')?->updated_at, 'monthly', 0.7),
            $this->entry(route('services.index'), $landings->get('services')?->updated_at, 'weekly', 0.8),
            $this->entry(route('blog.index'), $landings->get('blog')?->updated_at, 'weekly', 0.8),
            $this->entry(route(TourScope::Domestic->routeName()), $landings->get('domestic_tours')?->updated_at, 'weekly', 0.9),
            $this->entry(route(TourScope::International->routeName()), $landings->get('international_tours')?->updated_at, 'weekly', 0.9),
            $this->entry(route(TourScope::Group->routeName()), $landings->get('group_tours')?->updated_at, 'weekly', 0.8),
        ])
            ->merge($customLandingEntries)
            ->merge(
                Tour::query()
                    ->select(['id', 'slug', 'updated_at', 'canonical_url', 'robots_directive'])
                    ->published()
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Tour $tour) => $this->modelEntry($tour, route('tours.show', $tour), 'tours', 'tour', 'weekly', 0.8, true))
            )
            ->merge(
                TourCategory::query()
                    ->select(['id', 'slug', 'updated_at', 'robots_directive'])
                    ->published()
                    ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (TourCategory $category) => $this->modelEntry($category, route('tour-categories.show', $category), 'tour-categories', 'tour_category', 'weekly', 0.7))
            )
            ->merge(
                Destination::query()
                    ->select(['id', 'slug', 'updated_at', 'robots_directive'])
                    ->published()
                    ->countryRoots()
                    ->where(function ($countryQuery): void {
                        $countryQuery
                            ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                            ->orWhereHas('childDestinations.tours', fn ($tourQuery) => $tourQuery->published())
                            ->orWhereHas('childDestinations.primaryTours', fn ($tourQuery) => $tourQuery->published())
                            ->orWhereHas('childDestinations', function ($destinationQuery): void {
                                $destinationQuery
                                    ->where('show_blogs_on_page', true)
                                    ->whereHas('blogPosts', fn ($blogQuery) => $blogQuery->published());
                            });
                    })
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Destination $country) => $this->modelEntry($country, route('countries.show', ['slug' => $country->slug]), 'countries', 'destination', 'weekly', 0.7))
            )
            ->merge(
                Destination::query()
                    ->select(['id', 'slug', 'updated_at', 'robots_directive'])
                    ->published()
                    ->regularDestinations()
                    ->where(function ($destinationQuery): void {
                        $destinationQuery
                            ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                            ->orWhereHas('primaryTours', fn ($tourQuery) => $tourQuery->published())
                            ->orWhere(function ($blogDestinationQuery): void {
                                $blogDestinationQuery
                                    ->where('show_blogs_on_page', true)
                                    ->whereHas('blogPosts', fn ($blogQuery) => $blogQuery->published());
                            });
                    })
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Destination $destination) => $this->modelEntry($destination, route('destinations.show', $destination), 'destinations', 'destination', 'weekly', 0.7))
            )
            ->merge(
                Region::query()
                    ->select(['id', 'slug', 'updated_at', 'robots_directive'])
                    ->published()
                    ->whereHas('tours', fn ($tourQuery) => $tourQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Region $region) => $this->modelEntry($region, route('regions.show', $region), 'regions', 'region', 'weekly', 0.7))
            )
            ->merge(
                ContentCategory::query()
                    ->forTaxonomy('service')
                    ->whereHas('services', fn ($serviceQuery) => $serviceQuery->published())
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (ContentCategory $category) => $this->modelEntry($category, route('service-categories.show', ['category' => $category->slug]), 'service-categories', 'service_category', 'weekly', 0.7))
            )
            ->merge(
                Service::query()
                    ->select(['id', 'slug', 'updated_at', 'canonical_url', 'robots_directive'])
                    ->published()
                    ->orderBy('title')
                    ->get()
                    ->map(fn (Service $service) => $this->modelEntry($service, route('services.show', $service), 'services', 'service', 'weekly', 0.7, true))
            )
            ->merge($blogCategoryEntries)
            ->merge(
                BlogPost::query()
                    ->select(['id', 'slug', 'content_category_id', 'updated_at', 'published_at', 'canonical_url', 'robots_directive'])
                    ->published()
                    ->with('category:id,slug')
                    ->latest('published_at')
                    ->get()
                    ->map(fn (BlogPost $post) => $this->modelEntry($post, FrontsiteUrls::blogPost($post), 'blogs', 'blog_post', 'monthly', 0.6, true))
            );

        $mappings = $this->publicMappings();
        $redirectPaths = $mappings->filter(fn (PublicUrlMapping $mapping) => $mapping->mode === PublicUrlMapping::MODE_REDIRECT && $mapping->source_path !== $mapping->target_path)
            ->pluck('source_path')->flip();
        $renderPaths = $mappings->where('mode', PublicUrlMapping::MODE_RENDER)->pluck('source_path')->flip();
        $targets = $entries->filter(fn (array $entry) => ($entry['_indexable'] ?? true) && isset($entry['_target']))->keyBy('_target');
        $preservedEntries = $mappings
            ->filter(fn (PublicUrlMapping $mapping) => $mapping->mode === PublicUrlMapping::MODE_RENDER && $mapping->status_code === 200)
            ->map(function (PublicUrlMapping $mapping) use ($targets): ?array {
                $entry = $targets->get($mapping->target_type.':'.$mapping->target_id);

                if (! $entry) {
                    return null;
                }

                $entry['url'] = FrontsiteUrls::canonicalUrl($mapping->source_path);
                $entry['_self_canonical'] = true;

                return $entry;
            })
            ->filter();

        return $entries
            ->filter(fn (array $entry) => ($entry['_indexable'] ?? true) && ($entry['_self_canonical'] ?? true))
            ->reject(fn (array $entry) => $renderPaths->has(FrontsiteUrls::canonicalPath($entry['url'])))
            ->merge($preservedEntries)
            ->reject(fn (array $entry) => $redirectPaths->has(FrontsiteUrls::canonicalPath($entry['url'])))
            ->keyBy('url')
            ->values()
            ->map(fn (array $entry) => array_diff_key($entry, array_flip(['_target', '_indexable', '_self_canonical'])))
            ->all();
    }

    protected function publicMappings(): Collection
    {
        if (! config('public_url_mappings.enabled') || ! Schema::hasTable('public_url_mappings')) {
            return collect();
        }

        return PublicUrlMapping::query()
            ->select(['id', 'source_hash', 'source_path', 'mode', 'target_type', 'target_id', 'target_path', 'status_code'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(function (PublicUrlMapping $mapping): bool {
                $path = (string) $mapping->source_path;

                return str_starts_with($path, '/')
                    && mb_strlen($path) <= 700
                    && ! str_contains($path, '\\')
                    && ! preg_match('~[\x00-\x20\x7f?#]~', $path)
                    && ! preg_match('#^/(?:api|admin|livewire|storage|build)#', $path)
                    && ! preg_match('#\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|txt|xml|woff2?|ttf)$#i', $path)
                    && $path === FrontsiteUrls::canonicalPath($path)
                    && hash_equals(hash('sha256', $path), (string) $mapping->source_hash);
            });
    }

    protected function modelEntry(Model $model, string $url, string $type, string $targetType, string $changeFrequency, float $priority, bool $usesCanonical = false): array
    {
        return $this->entry($url, $model->updated_at, $changeFrequency, $priority, $type) + [
            '_target' => $targetType.':'.$model->getKey(),
            '_indexable' => ! $this->noindex($model->getAttribute('robots_directive')),
            '_self_canonical' => ! $usesCanonical || blank($model->getAttribute('canonical_url'))
                || FrontsiteUrls::canonicalModelUrl($model, $url) === FrontsiteUrls::canonicalUrl($url),
        ];
    }

    protected function noindex(?string $directive): bool
    {
        return (bool) preg_match('/\b(?:noindex|none)\b/i', (string) $directive);
    }

    protected function entry(string $url, mixed $lastModified, string $changeFrequency, float $priority, string $type = 'pages'): array
    {
        return [
            'changefreq' => $changeFrequency,
            'lastmod' => $lastModified?->toAtomString(),
            'priority' => number_format($priority, 1, '.', ''),
            'url' => FrontsiteUrls::canonicalUrl($url),
            'type' => $type,
        ];
    }
}
