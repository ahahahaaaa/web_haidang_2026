<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Support\FrontsiteUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class PageRegistryService
{
    public const PAGE_TYPES = ['home', 'about', 'contact', 'tour_scope', 'tour', 'tour_category', 'destination', 'country', 'region', 'service_index', 'service_category', 'service', 'blog_index', 'blog_category', 'blog_post', 'landing'];

    private const OWNERS = [
        'landing_page' => LandingPage::class,
        'tour' => Tour::class,
        'tour_category' => TourCategory::class,
        'destination' => Destination::class,
        'region' => Region::class,
        'service' => Service::class,
        'content_category' => ContentCategory::class,
        'blog_post' => BlogPost::class,
    ];

    private const SYSTEM_PAGES = [
        'home' => ['home', 'home', 'Trang chủ'],
        'about' => ['about', 'about', 'Về chúng tôi'],
        'contact' => ['contact', 'contact', 'Liên hệ'],
        'services' => ['service_index', 'services.index', 'Dịch vụ'],
        'blog' => ['blog_index', 'blog.index', 'Blog du lịch'],
        'domestic_tours' => ['tour_scope', 'tours.domestic', 'Tour trong nước'],
        'international_tours' => ['tour_scope', 'tours.international', 'Tour nước ngoài'],
        'group_tours' => ['tour_scope', 'tours.group', 'Tour đoàn'],
    ];

    private const SOURCE_FIELDS = ['title', 'name', 'slug', 'excerpt', 'content', 'body', 'hero_title', 'hero_excerpt', 'intro_title', 'intro_excerpt', 'meta_title', 'meta_description', 'og_title', 'og_description', 'canonical_url', 'robots_directive', 'cover_alt', 'faq_items', 'geo_config', 'editor_mode'];

    public function sync(): array
    {
        $dependencies = $this->dependencies();
        $counts = ['total' => 0, 'created' => 0, 'updated' => 0, 'excluded' => 0, 'by_type' => []];
        $seen = [];

        foreach ($this->candidates() as $candidate) {
            $owner = $candidate['source'];
            $identity = $this->scopeQuery();

            if ($candidate['system']) {
                $identity->where('route_name', $candidate['route_name']);
            } else {
                $identity->where('owner_type', $candidate['owner_type'])->where('owner_id', (string) $owner->getKey());
            }

            $page = $identity->first() ?? new SeoOptimizationPage;
            $created = ! $page->exists;
            $page->fill($this->attributes($candidate, $dependencies));
            $page->save();
            $seen[] = $page->getKey();
            $counts['total']++;
            $counts[$created ? 'created' : 'updated']++;
            $counts['by_type'][$page->page_type] = ($counts['by_type'][$page->page_type] ?? 0) + 1;
            $counts['excluded'] += $page->classification === 'DRAFT_OR_PRIVATE' ? 1 : 0;
        }

        $this->scopeQuery()->whereNotIn('id', $seen)->update([
            'classification' => 'UNRESOLVED',
            'capabilities' => json_encode(['read' => false, 'writable_fields' => []], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return $counts;
    }

    public function source(SeoOptimizationPage $page): ?Model
    {
        $this->assertSite($page);
        $class = self::OWNERS[$page->owner_type] ?? null;

        return $class && $page->owner_id !== null ? $class::query()->find($page->owner_id) : null;
    }

    public function descriptor(SeoOptimizationPage $page): array
    {
        $source = $this->source($page);
        $system = collect(self::SYSTEM_PAGES)->first(fn (array $definition): bool => $definition[1] === $page->route_name);

        if ($source === null && $system === null) {
            return [...$page->getAttributes(), 'classification' => 'UNRESOLVED', 'capabilities' => ['read' => false, 'writable_fields' => []]];
        }

        $candidate = $system
            ? $this->systemCandidate((string) array_search($system, self::SYSTEM_PAGES, true), $system, $source)
            : $this->modelCandidate($source);

        return $this->attributes($candidate, $this->dependencies());
    }

    public function refresh(SeoOptimizationPage $page): SeoOptimizationPage
    {
        $page->fill($this->descriptor($page))->save();

        return $page->refresh();
    }

    public function currentVersion(SeoOptimizationPage $page): string
    {
        return (string) ($this->descriptor($page)['source_version'] ?? '');
    }

    public function writableFields(SeoOptimizationPage $page): array
    {
        $source = $this->source($page);

        if ($source === null || $this->classification($source, (string) $page->page_type) === 'DRAFT_OR_PRIVATE') {
            return [];
        }

        $fields = array_values(array_intersect(
            ['title', 'name', 'excerpt', 'content', 'meta_title', 'meta_description', 'cover_alt'],
            $source->getFillable(),
        ));

        if ($source instanceof LandingPage) {
            $fields = ['title', 'meta_title', 'meta_description'];

            if ($source->isHtmlMode() || in_array($source->page_key, ['about', 'contact', 'services', 'blog', 'domestic_tours', 'international_tours', 'group_tours'], true)) {
                $fields[] = 'body';
            }

            if ($source->isSystemPage()) {
                $fields = [...$fields, 'hero_title', 'hero_excerpt', 'intro_title', 'intro_excerpt'];
            }
        }

        return $fields;
    }

    public function sourceFields(SeoOptimizationPage $page): array
    {
        $source = $this->source($page);

        if ($source === null || $this->classification($source, (string) $page->page_type) === 'DRAFT_OR_PRIVATE') {
            return [];
        }

        return collect(self::SOURCE_FIELDS)
            ->filter(fn (string $field): bool => array_key_exists($field, $source->getAttributes()))
            ->mapWithKeys(fn (string $field): array => [$field => $source->getAttribute($field)])
            ->all();
    }

    public function url(SeoOptimizationPage $page): string
    {
        $this->assertSite($page);

        return FrontsiteUrls::canonicalUrl((string) $page->path);
    }

    private function scopeQuery(): Builder
    {
        return SeoOptimizationPage::query()->where('site_id', $this->siteId())->where('locale', config('seo_optimization.locale', 'vi'));
    }

    private function siteId(): string
    {
        return (string) config('seo_optimization.site_id', 'haidangtravel');
    }

    private function assertSite(SeoOptimizationPage $page): void
    {
        if ((string) $page->site_id !== $this->siteId() || (string) $page->locale !== (string) config('seo_optimization.locale', 'vi')) {
            throw new \DomainException('Trang không thuộc site hoặc ngôn ngữ đã cấu hình.');
        }
    }

    private function candidates(): \Generator
    {
        $landings = LandingPage::query()->whereIn('page_key', array_keys(self::SYSTEM_PAGES))->get()->keyBy('page_key');

        foreach (self::SYSTEM_PAGES as $key => $definition) {
            yield $this->systemCandidate($key, $definition, $landings->get($key));
        }

        foreach (self::OWNERS as $alias => $class) {
            $query = $class::query();

            if ($alias === 'landing_page') {
                $query->whereNull('page_key')->whereNotNull('slug')->where('slug', '!=', '');
            } elseif ($alias === 'content_category') {
                $query->whereIn('taxonomy', ['blog', 'service']);
            } elseif ($alias === 'blog_post') {
                $query->with('category');
            }

            foreach ($query->lazyById(200) as $model) {
                yield $this->modelCandidate($model);
            }
        }
    }

    private function systemCandidate(string $key, array $definition, ?Model $source): array
    {
        return [
            'source' => $source,
            'system' => true,
            'page_type' => $definition[0],
            'route_name' => $definition[1],
            'path' => route($definition[1], [], false),
            'title' => $source?->title ?: $definition[2],
            'owner_type' => $source ? 'landing_page' : 'system',
            'owner_id' => $source ? (string) $source->getKey() : $key,
        ];
    }

    private function modelCandidate(Model $source): array
    {
        [$type, $routeName, $parameters] = match (true) {
            $source instanceof Tour => ['tour', 'tours.show', ['tour' => $source]],
            $source instanceof TourCategory => ['tour_category', 'tour-categories.show', ['category' => $source]],
            $source instanceof Destination && $source->is_country_root => ['country', 'countries.show', ['slug' => $source->slug]],
            $source instanceof Destination => ['destination', 'destinations.show', ['destination' => $source]],
            $source instanceof Region => ['region', 'regions.show', ['region' => $source]],
            $source instanceof Service => ['service', 'services.show', ['service' => $source]],
            $source instanceof ContentCategory && $source->taxonomy === 'service' => ['service_category', 'service-categories.show', ['category' => $source->slug]],
            $source instanceof ContentCategory => ['blog_category', 'blog-categories.show', ['slug' => $source->slug]],
            $source instanceof BlogPost => ['blog_post', 'blog.show', FrontsiteUrls::blogPostRouteParameters($source)],
            $source instanceof LandingPage => ['landing', 'landing.show', ['slug' => $source->slug]],
            default => throw new \DomainException('Loại nội dung chưa được hỗ trợ.'),
        };

        return [
            'source' => $source,
            'system' => false,
            'page_type' => $type,
            'route_name' => $routeName,
            'path' => route($routeName, $parameters, false),
            'title' => $source->title ?: $source->name,
            'owner_type' => array_search($source::class, self::OWNERS, true),
            'owner_id' => (string) $source->getKey(),
        ];
    }

    private function attributes(array $candidate, array $dependencies): array
    {
        $source = $candidate['source'];
        $sourceHash = hash('sha256', json_encode([$candidate['path'], $source?->getRawOriginal()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $page = new SeoOptimizationPage([
            'site_id' => $this->siteId(),
            'locale' => config('seo_optimization.locale', 'vi'),
            'page_type' => $candidate['page_type'],
            'owner_type' => $candidate['owner_type'],
            'owner_id' => $candidate['owner_id'],
        ]);
        $classification = $source ? $this->classification($source, $candidate['page_type']) : 'INDEXABLE';

        return [
            'site_id' => $this->siteId(),
            'locale' => config('seo_optimization.locale', 'vi'),
            'page_type' => $candidate['page_type'],
            'owner_type' => $candidate['owner_type'],
            'owner_id' => $candidate['owner_id'],
            'route_name' => $candidate['route_name'],
            'path' => $candidate['path'],
            'title' => $candidate['title'],
            'classification' => $classification,
            'source_hash' => $sourceHash,
            'source_version' => hash('sha256', json_encode([$sourceHash, $dependencies], JSON_THROW_ON_ERROR)),
            'capabilities' => ['read' => $classification !== 'DRAFT_OR_PRIVATE', 'writable_fields' => $this->writableFields($page)],
            'dependencies' => $dependencies,
            'last_seen_at' => now(),
        ];
    }

    private function classification(Model $source, string $pageType): string
    {
        if ($source instanceof LandingPage && ! $source->isSystemPage() && ! $source->is_active) {
            return 'DRAFT_OR_PRIVATE';
        }

        if (array_key_exists('status', $source->getAttributes()) && $source->status !== 'published') {
            return 'DRAFT_OR_PRIVATE';
        }

        if ($source->published_at && $source->published_at->isFuture()) {
            return 'DRAFT_OR_PRIVATE';
        }

        if (str_contains(mb_strtolower((string) $source->robots_directive), 'noindex')) {
            return 'NOINDEX_INTENTIONAL';
        }

        if ($pageType === 'service_category' && ! $source->services()->published()->exists()) {
            return 'EXPECTED_INDEXABLE_ERROR';
        }

        return 'INDEXABLE';
    }

    private function dependencies(): array
    {
        $tables = [
            'landing_pages', 'tours', 'tour_categories', 'destinations', 'regions', 'services', 'blog_posts', 'content_categories',
            'tour_departures', 'tour_departure_sync_states', 'tour_category_tour', 'destination_tour', 'region_tour',
            'travel_reviews', 'site_settings', 'menus', 'menu_items', 'sliders', 'slider_items', 'voucher_campaigns', 'media',
        ];
        $fingerprints = [];
        $references = '';

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $context = hash_init('sha256');
            $columns = Schema::getColumnListing($table);
            $query = DB::table($table);

            foreach (in_array('id', $columns, true) ? ['id'] : $columns as $column) {
                $query->orderBy($column);
            }

            foreach ($query->cursor() as $row) {
                if ($table === 'media') {
                    $properties = json_decode($row->custom_properties ?? '{}', true);
                    if ($row->collection_name === 'library' && ! empty($properties['seo_optimization_staged'])
                        && ! str_contains($references, '/'.$row->id.'/') && ! str_contains($references, (string) $row->file_name)) {
                        continue;
                    }
                } else {
                    $references .= json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                }
                hash_update($context, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            }

            $fingerprints[$table] = hash_final($context);
        }

        return [
            'strategy' => 'conservative-public-cms-v1',
            'public_day' => now(config('app.timezone'))->toDateString(),
            'canonical_base' => FrontsiteUrls::canonicalBaseUrl(),
            'tables' => $fingerprints,
        ];
    }
}
