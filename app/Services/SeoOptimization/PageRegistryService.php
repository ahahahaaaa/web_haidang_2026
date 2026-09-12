<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Support\FrontsiteUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
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

    public const PATCH_FIELDS = [
        'title', 'name', 'slug', 'excerpt', 'content', 'description', 'meta_title', 'meta_description',
        'cover_alt', 'faq_items', 'body', 'hero_title', 'hero_excerpt', 'intro_title', 'intro_excerpt',
        ContentWriteContractService::BLOCK_CHANGES,
    ];

    public const STRING_PATCH_FIELDS = [
        'title', 'name', 'slug', 'excerpt', 'content', 'description', 'meta_title', 'meta_description',
        'cover_alt', 'body', 'hero_title', 'hero_excerpt', 'intro_title', 'intro_excerpt',
    ];

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

    private bool $cacheDependencyFingerprints = false;

    private array $dependencyFingerprintMaps = [];

    private array $dependencyTableColumns = [];

    public function __construct(private ContentWriteContractService $contracts) {}

    public function sync(): array
    {
        $this->cacheDependencyFingerprints = true;
        $this->dependencyFingerprintMaps = [];
        $this->dependencyTableColumns = [];
        $counts = ['total' => 0, 'created' => 0, 'updated' => 0, 'excluded' => 0, 'by_type' => []];
        $seen = [];

        try {
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
                $page->fill($this->attributes($candidate));
                $page->save();
                $seen[] = $page->getKey();
                $counts['total']++;
                $counts[$created ? 'created' : 'updated']++;
                $counts['by_type'][$page->page_type] = ($counts['by_type'][$page->page_type] ?? 0) + 1;
                $counts['excluded'] += $page->classification === 'DRAFT_OR_PRIVATE' ? 1 : 0;
            }

            $this->scopeQuery()->whereNotIn('id', $seen)->update([
                'classification' => 'UNRESOLVED',
                'capabilities' => json_encode([
                    'read' => false,
                    'contract_version' => ContentWriteContractService::VERSION,
                    'writable_fields' => [],
                    'field_contracts' => [],
                ], JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

            return $counts;
        } finally {
            $this->cacheDependencyFingerprints = false;
            $this->dependencyFingerprintMaps = [];
            $this->dependencyTableColumns = [];
        }
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
            return [...$page->getAttributes(), 'classification' => 'UNRESOLVED', 'capabilities' => [
                'read' => false,
                'contract_version' => ContentWriteContractService::VERSION,
                'writable_fields' => [],
                'field_contracts' => [],
            ]];
        }

        $candidate = $system
            ? $this->systemCandidate((string) array_search($system, self::SYSTEM_PAGES, true), $system, $source)
            : $this->modelCandidate($source);

        return $this->attributes($candidate);
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

        return $this->contracts->writableFields($page, $source);
    }

    public function sourceFields(SeoOptimizationPage $page): array
    {
        $source = $this->source($page);

        if ($source === null || $this->classification($source, (string) $page->page_type) === 'DRAFT_OR_PRIVATE') {
            return [];
        }

        return $this->contracts->sourceFields($page, $source);
    }

    public function fieldContracts(SeoOptimizationPage $page): array
    {
        $source = $this->source($page);

        return $source ? $this->contracts->fieldContracts($page, $source) : [];
    }

    public function contentUnits(SeoOptimizationPage $page): array
    {
        $source = $this->source($page);

        return $source ? $this->contracts->contentUnits($page, $source) : [];
    }

    public function url(SeoOptimizationPage $page): string
    {
        $this->assertSite($page);

        return FrontsiteUrls::canonicalUrl((string) $page->path);
    }

    public function adminEditUrl(SeoOptimizationPage $page): ?string
    {
        $source = $this->source($page);

        return match (true) {
            $source instanceof Tour => route('admin.tours.edit', $source),
            $source instanceof TourCategory => route('admin.tours.categories.edit', $source),
            $source instanceof Destination => route('admin.tours.destinations.edit', $source),
            $source instanceof Region => route('admin.tours.regions.edit', $source),
            $source instanceof Service => route('admin.services.edit', $source),
            $source instanceof ContentCategory && $source->taxonomy === 'service' => route('admin.services.categories.edit', $source),
            $source instanceof ContentCategory && $source->taxonomy === 'blog' => route('admin.blogs.categories.edit', $source),
            $source instanceof BlogPost => route('admin.blogs.edit', $source),
            $source instanceof LandingPage => route('admin.landing-pages.edit', $source),
            default => null,
        };
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

    private function attributes(array $candidate): array
    {
        $source = $candidate['source'];
        $dependencies = $this->dependencies($candidate);
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
            'capabilities' => [
                'read' => $classification !== 'DRAFT_OR_PRIVATE',
                'contract_version' => ContentWriteContractService::VERSION,
                'writable_fields' => $classification !== 'DRAFT_OR_PRIVATE' ? $this->contracts->writableFields($page, $source) : [],
                'field_contracts' => $classification !== 'DRAFT_OR_PRIVATE' ? $this->contracts->fieldContracts($page, $source) : [],
            ],
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

    private function dependencies(array $candidate): array
    {
        $source = $candidate['source'];
        $related = [];

        if ($source instanceof Tour) {
            $related = [
                'primary_category' => $this->recordFingerprint('tour_categories', $source->getAttribute('tour_category_id')),
                'primary_destination' => $this->recordFingerprint('destinations', $source->getAttribute('destination_id')),
                'primary_region' => $this->recordFingerprint('regions', $source->getAttribute('region_id')),
                'departures' => $this->fingerprint('tour_departures', ['tour_id' => $source->getKey()]),
                'departure_sync' => $this->fingerprint('tour_departure_sync_states', ['tour_id' => $source->getKey()]),
                'categories' => $this->fingerprint('tour_category_tour', ['tour_id' => $source->getKey()]),
                'destinations' => $this->fingerprint('destination_tour', ['tour_id' => $source->getKey()]),
                'regions' => $this->fingerprint('region_tour', ['tour_id' => $source->getKey()]),
                'reviews' => $this->fingerprint('travel_reviews', [
                    'reviewable_type' => $source->getMorphClass(),
                    'reviewable_id' => $source->getKey(),
                ]),
                'review_batches' => $this->fingerprint('tour_review_batches', ['tour_id' => $source->getKey()]),
            ];
        } elseif ($source instanceof Service) {
            $related['category'] = $this->recordFingerprint('content_categories', $source->getAttribute('content_category_id'));
        } elseif ($source instanceof BlogPost) {
            $related = [
                'category' => $this->recordFingerprint('content_categories', $source->getAttribute('content_category_id')),
                'country' => $this->recordFingerprint('destinations', $source->getAttribute('country_destination_id')),
                'destination' => $this->recordFingerprint('destinations', $source->getAttribute('destination_id')),
            ];
        } elseif ($source instanceof Destination) {
            $related = [
                'country' => $this->recordFingerprint('destinations', $source->getAttribute('country_id')),
                'region' => $this->recordFingerprint('regions', $source->getAttribute('region_id')),
                'direct_tours' => $this->fingerprint('tours', ['destination_id' => $source->getKey()]),
                'tour_links' => $this->fingerprint('destination_tour', ['destination_id' => $source->getKey()]),
                'blog_posts' => $this->fingerprint('blog_posts', ['destination_id' => $source->getKey()]),
            ];
        } elseif ($source instanceof Region) {
            $related = [
                'destinations' => $this->fingerprint('destinations', ['region_id' => $source->getKey()]),
                'direct_tours' => $this->fingerprint('tours', ['region_id' => $source->getKey()]),
                'tour_links' => $this->fingerprint('region_tour', ['region_id' => $source->getKey()]),
            ];
        } elseif ($source instanceof TourCategory) {
            $related = [
                'direct_tours' => $this->fingerprint('tours', ['tour_category_id' => $source->getKey()]),
                'tour_links' => $this->fingerprint('tour_category_tour', ['tour_category_id' => $source->getKey()]),
            ];
        } elseif ($source instanceof ContentCategory) {
            $related[$source->taxonomy === 'service' ? 'services' : 'blog_posts'] = $this->fingerprint(
                $source->taxonomy === 'service' ? 'services' : 'blog_posts',
                ['content_category_id' => $source->getKey()],
            );
        }

        $system = match ($candidate['page_type']) {
            'home' => $this->tableFingerprints(['landing_pages', 'tours', 'tour_categories', 'destinations', 'regions', 'services', 'blog_posts', 'content_categories', 'sliders', 'slider_items', 'voucher_campaign', 'voucher_campaigns']),
            'tour_scope' => $this->tableFingerprints(['tours', 'tour_categories', 'destinations', 'regions', 'tour_departures', 'tour_category_tour', 'destination_tour', 'region_tour']),
            'service_index' => $this->tableFingerprints(['services', 'content_categories']),
            'blog_index' => $this->tableFingerprints(['blog_posts', 'content_categories']),
            default => [],
        };

        return [
            'strategy' => 'owner-scoped-public-cms-v3',
            'content_contract' => ContentWriteContractService::VERSION,
            'canonical_base' => FrontsiteUrls::canonicalBaseUrl(),
            'site_settings' => $this->fingerprint('site_settings'),
            'owner_media' => $source === null ? null : $this->fingerprint('media', [
                'model_type' => $source->getMorphClass(),
                'model_id' => $source->getKey(),
            ]),
            'related' => array_filter($related, fn (?string $value): bool => $value !== null),
            'system' => $system,
        ];
    }

    private function tableFingerprints(array $tables): array
    {
        $fingerprints = [];

        foreach (array_unique($tables) as $table) {
            if ($fingerprint = $this->fingerprint($table)) {
                $fingerprints[$table] = $fingerprint;
            }
        }

        return $fingerprints;
    }

    private function recordFingerprint(string $table, mixed $id): ?string
    {
        return filled($id) ? $this->fingerprint($table, ['id' => $id]) : null;
    }

    private function fingerprint(string $table, array $scope = []): ?string
    {
        $columns = $this->dependencyColumns($table);
        if ($columns === null) {
            return null;
        }
        if (array_diff(array_keys($scope), $columns) !== []) {
            return null;
        }

        if ($this->cacheDependencyFingerprints) {
            $map = $this->groupedFingerprints($table, array_keys($scope), $columns);

            return $map[$this->fingerprintKey(array_values($scope))] ?? hash('sha256', '');
        }

        $query = DB::table($table)->select($columns);
        foreach ($scope as $column => $value) {
            $query->where($column, $value);
        }
        $this->orderFingerprintQuery($query, $columns);
        $context = hash_init('sha256');
        foreach ($query->cursor() as $row) {
            hash_update($context, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }

        return hash_final($context);
    }

    private function groupedFingerprints(string $table, array $groupColumns, array $columns): array
    {
        $cacheKey = $table.'|'.implode(',', $groupColumns);
        if (isset($this->dependencyFingerprintMaps[$cacheKey])) {
            return $this->dependencyFingerprintMaps[$cacheKey];
        }

        $query = DB::table($table)->select($columns);
        $this->orderFingerprintQuery($query, $columns);
        $contexts = [];
        foreach ($query->cursor() as $row) {
            $key = $this->fingerprintKey(array_map(fn (string $column): mixed => $row->{$column}, $groupColumns));
            $contexts[$key] ??= hash_init('sha256');
            hash_update($contexts[$key], json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }

        $fingerprints = [];
        foreach ($contexts as $key => $context) {
            $fingerprints[$key] = hash_final($context);
        }

        return $this->dependencyFingerprintMaps[$cacheKey] = $fingerprints;
    }

    private function fingerprintKey(array $values): string
    {
        return json_encode(array_map(fn (mixed $value): string => (string) $value, $values), JSON_THROW_ON_ERROR);
    }

    private function dependencyColumns(string $table): ?array
    {
        if (array_key_exists($table, $this->dependencyTableColumns)) {
            return $this->dependencyTableColumns[$table];
        }

        return $this->dependencyTableColumns[$table] = Schema::hasTable($table)
            ? Schema::getColumnListing($table)
            : null;
    }

    private function orderFingerprintQuery(QueryBuilder $query, array $columns): void
    {
        foreach (in_array('id', $columns, true) ? ['id'] : $columns as $column) {
            $query->orderBy($column);
        }
    }
}
