<?php

namespace App\Services\Frontsite;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;

class TourSearchFilterService
{
    private const BUDGET_OPTIONS = ['under-5m', '5m-10m', '10m-20m', '20m-plus'];

    private const VIEW_DATA_CACHE_ATTRIBUTE = 'tour_search_filter_view_data_cache';

    public function apply(
        Builder|Relation $query,
        Request $request,
        array $fixedFilters = [],
        array $except = [],
    ): Builder|Relation {
        $filters = $this->filters($request);

        if (($fixedFilters['category'] ?? null) instanceof TourCategory) {
            $query->whereHas('categories', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($fixedFilters['category']->getKey()));
        }

        if (($fixedFilters['destination'] ?? null) instanceof Destination) {
            $query->whereHas('destinations', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($fixedFilters['destination']->getKey()));
        }

        if (($fixedFilters['country'] ?? null) instanceof Destination) {
            $this->applyCountryDestinationFilter($query, $fixedFilters['country']);
        }

        if (($fixedFilters['region'] ?? null) instanceof Region) {
            $query->whereHas('regions', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($fixedFilters['region']->getKey()));
        }

        if ($filters['q'] !== '' && ! in_array('q', $except, true)) {
            $this->applyTextSearch($query, $filters['q']);
        }

        if ($filters['category'] !== '' && ! in_array('category', $except, true) && ! isset($fixedFilters['category'])) {
            $query->whereHas('categories', fn (Builder $category) => $category->published()->where('slug', $filters['category']));
        }

        if ($filters['destination'] !== '' && ! in_array('destination', $except, true) && ! isset($fixedFilters['destination'])) {
            $query->whereHas('destinations', fn (Builder $destination) => $destination->published()->where('slug', $filters['destination']));
        }

        $scope = TourScope::tryFrom($filters['scope']);

        if ($scope && ! in_array('scope', $except, true)) {
            $query->forScope($scope);
        } elseif ($filters['scope'] === 'non_group' && ! in_array('scope', $except, true)) {
            $query->where('scope', '!=', TourScope::Group->value);
        }

        if ($filters['departure_location'] !== '' && ! in_array('departure_location', $except, true)) {
            $this->applyDepartureLocationFilter(
                $query,
                $filters['departure_location'],
                $scope,
                ! in_array('departure_date', $except, true) ? $filters['departure_date'] : '',
            );
        }

        if ($filters['transport'] !== '' && ! in_array('transport', $except, true)) {
            $query->where(function (Builder $transportQuery) use ($filters): void {
                $transportQuery
                    ->where('transport', $filters['transport'])
                    ->orWhereHas('departures', fn (Builder $departure) => $departure->upcomingPublic()->where('transport_label', $filters['transport']));
            });
        }

        if ($filters['departure_date'] !== ''
            && ! in_array('departure_date', $except, true)
            && ($filters['departure_location'] === '' || in_array('departure_location', $except, true))) {
            $query->whereHas(
                'departures',
                fn (Builder $departure) => $departure->upcomingPublic()->whereDate('departure_date', $filters['departure_date'])
            );
        }

        if ($filters['budget'] !== '' && ! in_array('budget', $except, true)) {
            $this->applyBudgetFilter($query, $filters['budget']);
        }

        return $query;
    }

    public function filters(Request $request): array
    {
        $scope = trim($request->string('scope')->toString());
        $departureDate = trim($request->string('departure_date')->toString());
        $budget = trim($request->string('budget')->toString());

        return [
            'q' => Str::limit(trim($request->string('q')->toString()), 160, ''),
            'scope' => $scope === 'non_group' ? 'non_group' : (TourScope::tryFrom($scope)?->value ?? ''),
            'departure_location' => $this->canonicalDepartureLocationKey($request->string('departure_location')->toString()),
            'destination' => $this->slugFilterValue($request->string('destination')->toString()),
            'departure_date' => $this->isValidDepartureDate($departureDate) ? $departureDate : '',
            'category' => $this->slugFilterValue($request->string('category')->toString()),
            'transport' => Str::limit(trim($request->string('transport')->toString()), 255, ''),
            'budget' => in_array($budget, self::BUDGET_OPTIONS, true) ? $budget : '',
        ];
    }

    public function viewData(Request $request): array
    {
        $filters = $this->filters($request);
        $cacheKey = implode('|', $filters);
        $requestCache = $request->attributes->get(self::VIEW_DATA_CACHE_ATTRIBUTE, []);

        if (is_array($requestCache) && array_key_exists($cacheKey, $requestCache)) {
            return $requestCache[$cacheKey];
        }

        $scope = TourScope::tryFrom($filters['scope']);
        $excludeGroup = $filters['scope'] === 'non_group';
        $productTabs = $this->productTabs($request);

        $viewData = [
            'action' => route('tours.search'),
            'departure_dates' => $this->departureDateOptions($scope, $excludeGroup),
            'departure_locations' => $this->departureLocationOptions($scope, $excludeGroup),
            'destinations' => $this->destinationOptions($scope, $excludeGroup),
            'mobile_shortcuts' => $this->mobileShortcuts($productTabs),
            'popular_links' => $this->popularLinks($scope, $excludeGroup),
            'product_tabs' => $productTabs,
            'scope_options' => collect(TourScope::cases())
                ->map(fn (TourScope $option) => ['value' => $option->value, 'label' => $option->label()])
                ->prepend(['value' => 'non_group', 'label' => 'Không phải tour đoàn'])
                ->prepend(['value' => '', 'label' => 'Tất cả'])
                ->values()
                ->all(),
            'selected' => $filters,
        ];

        $requestCache = is_array($requestCache) ? $requestCache : [];
        $requestCache[$cacheKey] = $viewData;
        $request->attributes->set(self::VIEW_DATA_CACHE_ATTRIBUTE, $requestCache);

        return $viewData;
    }

    private function applyTextSearch(Builder|Relation $query, string $searchQuery): void
    {
        $likeQuery = '%'.$searchQuery.'%';
        $slugQuery = Str::slug($searchQuery);
        $slugLikeQuery = $slugQuery !== '' ? '%'.$slugQuery.'%' : null;

        $query->where(function (Builder $searchBuilder) use ($likeQuery, $slugLikeQuery): void {
            $searchBuilder
                ->where('title', 'like', $likeQuery)
                ->when($slugLikeQuery, fn (Builder $titleQuery, string $slugLikeQuery) => $titleQuery->orWhere('slug', 'like', $slugLikeQuery))
                ->orWhereHas('primaryCategory', fn (Builder $category) => $this->applyNameOrSlugSearch($category, $likeQuery, $slugLikeQuery))
                ->orWhereHas('categories', fn (Builder $category) => $this->applyNameOrSlugSearch($category, $likeQuery, $slugLikeQuery))
                ->orWhereHas('destination', fn (Builder $destination) => $this->applyNameOrSlugSearch($destination, $likeQuery, $slugLikeQuery))
                ->orWhereHas('destinations', fn (Builder $destination) => $this->applyNameOrSlugSearch($destination, $likeQuery, $slugLikeQuery))
                ->orWhereHas('region', fn (Builder $region) => $this->applyNameOrSlugSearch($region, $likeQuery, $slugLikeQuery));
        });
    }

    private function applyNameOrSlugSearch(Builder $query, string $likeQuery, ?string $slugLikeQuery): void
    {
        $query
            ->where('name', 'like', $likeQuery)
            ->when($slugLikeQuery, fn (Builder $nameQuery, string $slugLikeQuery) => $nameQuery->orWhere('slug', 'like', $slugLikeQuery));
    }

    private function applyCountryDestinationFilter(Builder|Relation $query, Destination $country): void
    {
        $query->where(function (Builder $tourQuery) use ($country): void {
            $tourQuery
                ->whereHas('destination', function (Builder $destinationQuery) use ($country): void {
                    $destinationQuery
                        ->whereKey($country->getKey())
                        ->orWhere('country_id', $country->getKey());
                })
                ->orWhereHas('destinations', function (Builder $destinationQuery) use ($country): void {
                    $destinationQuery
                        ->whereKey($country->getKey())
                        ->orWhere('country_id', $country->getKey());
                });
        });
    }

    private function applyDepartureLocationFilter(
        Builder|Relation $query,
        string $locationKey,
        ?TourScope $scope,
        string $departureDate = '',
    ): void {
        $option = collect($this->departureLocationOptions($scope))->firstWhere('value', $locationKey);
        $rawValues = collect(data_get($option, 'raw_values', []))->filter()->values()->all();

        if ($rawValues === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $locationQuery) use ($departureDate, $rawValues): void {
            if ($departureDate !== '') {
                $locationQuery->whereHas('departures', function (Builder $departure) use ($departureDate, $rawValues): void {
                    $departure
                        ->upcomingPublic()
                        ->whereDate('departure_date', $departureDate)
                        ->where(function (Builder $departureLocation) use ($rawValues): void {
                            $departureLocation
                                ->whereIn('departure_location', $rawValues)
                                ->orWhere(function (Builder $defaultLocation) use ($rawValues): void {
                                    $defaultLocation
                                        ->where(function (Builder $emptyLocation): void {
                                            $emptyLocation
                                                ->whereNull('departure_location')
                                                ->orWhere('departure_location', '');
                                        })
                                        ->whereHas('tour', fn (Builder $tour) => $tour->whereIn('departure_location', $rawValues));
                                });
                        });
                });

                return;
            }

            $locationQuery
                ->whereIn('departure_location', $rawValues)
                ->orWhereHas('departures', fn (Builder $departure) => $departure->upcomingPublic()->whereIn('departure_location', $rawValues));
        });
    }

    private function applyBudgetFilter(Builder|Relation $query, string $budget): void
    {
        [$minBudget, $maxBudget] = $this->budgetRange($budget);

        if ($minBudget === null) {
            return;
        }

        $query->where(function (Builder $priceQuery) use ($maxBudget, $minBudget): void {
            $priceQuery
                ->where(function (Builder $tourPrice) use ($maxBudget, $minBudget): void {
                    $this->applyBudgetAmountConstraint($tourPrice, ['sale_price', 'base_price'], $minBudget, $maxBudget);
                })
                ->orWhereHas('departures', function (Builder $departure) use ($maxBudget, $minBudget): void {
                    $departure
                        ->upcomingPublic()
                        ->where(function (Builder $departurePrice) use ($maxBudget, $minBudget): void {
                            $this->applyBudgetAmountConstraint($departurePrice, ['sale_price', 'base_price'], $minBudget, $maxBudget);
                        });
                });
        });
    }

    private function applyBudgetAmountConstraint(Builder $query, array $columns, int $min, ?int $max): void
    {
        $query->where(function (Builder $amountQuery) use ($columns, $max, $min): void {
            foreach (array_values($columns) as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $amountQuery->{$method}(function (Builder $columnQuery) use ($column, $max, $min): void {
                    $columnQuery->whereNotNull($column)->where($column, '>=', $min);

                    if ($max !== null) {
                        $columnQuery->where($column, '<=', $max);
                    }
                });
            }
        });
    }

    private function budgetRange(string $value): array
    {
        return match ($value) {
            'under-5m' => [0, 5000000],
            '5m-10m' => [5000000, 10000000],
            '10m-20m' => [10000000, 20000000],
            '20m-plus' => [20000000, null],
            default => [null, null],
        };
    }

    private function destinationOptions(?TourScope $scope, bool $excludeGroup = false): array
    {
        return Destination::query()
            ->published()
            ->whereHas('tours', function (Builder $tourQuery) use ($scope, $excludeGroup): void {
                $tourQuery
                    ->published()
                    ->when($scope, fn (Builder $scopedQuery, TourScope $scope) => $scopedQuery->forScope($scope))
                    ->when($excludeGroup, fn (Builder $scopedQuery) => $scopedQuery->where('scope', '!=', TourScope::Group->value));
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_country_root'])
            ->map(fn (Destination $destination) => [
                'value' => $destination->slug,
                'label' => $destination->name.($destination->is_country_root ? ' (quốc gia)' : ''),
            ])
            ->all();
    }

    private function departureDateOptions(?TourScope $scope, bool $excludeGroup = false): array
    {
        return TourDeparture::query()
            ->upcomingPublic()
            ->whereNotNull('departure_date')
            ->whereDate('departure_date', '>', now(config('app.timezone'))->toDateString())
            ->whereHas('tour', function (Builder $tourQuery) use ($scope, $excludeGroup): void {
                $tourQuery
                    ->published()
                    ->when($scope, fn (Builder $scopedQuery, TourScope $scope) => $scopedQuery->forScope($scope))
                    ->when($excludeGroup, fn (Builder $scopedQuery) => $scopedQuery->where('scope', '!=', TourScope::Group->value));
            })
            ->orderBy('departure_date')
            ->distinct()
            ->pluck('departure_date')
            ->map(fn ($date) => (string) $date)
            ->values()
            ->all();
    }

    private function departureLocationOptions(?TourScope $scope, bool $excludeGroup = false): array
    {
        $tourLocations = Tour::query()
            ->published()
            ->when($scope, fn (Builder $query, TourScope $scope) => $query->forScope($scope))
            ->when($excludeGroup, fn (Builder $query) => $query->where('scope', '!=', TourScope::Group->value))
            ->whereNotNull('departure_location')
            ->where('departure_location', '!=', '')
            ->pluck('departure_location');

        $departureLocations = TourDeparture::query()
            ->upcomingPublic()
            ->whereNotNull('departure_location')
            ->where('departure_location', '!=', '')
            ->whereHas('tour', function (Builder $tourQuery) use ($scope, $excludeGroup): void {
                $tourQuery
                    ->published()
                    ->when($scope, fn (Builder $scopedQuery, TourScope $scope) => $scopedQuery->forScope($scope))
                    ->when($excludeGroup, fn (Builder $scopedQuery) => $scopedQuery->where('scope', '!=', TourScope::Group->value));
            })
            ->pluck('departure_location');

        return $tourLocations
            ->merge($departureLocations)
            ->map(fn ($rawValue) => [
                'value' => $this->canonicalDepartureLocationKey((string) $rawValue),
                'raw' => Str::squish((string) $rawValue),
            ])
            ->filter(fn (array $item) => $item['value'] !== '' && $item['raw'] !== '')
            ->groupBy('value')
            ->map(function (Collection $items, string $value): array {
                $rawValues = $items->pluck('raw')->unique()->values();

                return [
                    'value' => $value,
                    'label' => $this->departureLocationLabel($value, (string) $rawValues->first()),
                    'raw_values' => $rawValues->all(),
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function popularLinks(?TourScope $scope, bool $excludeGroup = false): array
    {
        $categories = TourCategory::query()
            ->published()
            ->where('is_featured', true)
            ->whereHas('tours', function (Builder $tourQuery) use ($scope, $excludeGroup): void {
                $tourQuery
                    ->published()
                    ->when($scope, fn (Builder $scopedQuery, TourScope $scope) => $scopedQuery->forScope($scope))
                    ->when($excludeGroup, fn (Builder $scopedQuery) => $scopedQuery->where('scope', '!=', TourScope::Group->value));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(7)
            ->get(['id', 'name', 'slug'])
            ->map(fn (TourCategory $category) => [
                'label' => $category->name,
                'url' => route('tour-categories.show', $category),
            ]);

        if ($categories->count() >= 7) {
            return $categories->all();
        }

        $destinations = Destination::query()
            ->published()
            ->where('is_featured', true)
            ->whereHas('tours', function (Builder $tourQuery) use ($scope, $excludeGroup): void {
                $tourQuery
                    ->published()
                    ->when($scope, fn (Builder $scopedQuery, TourScope $scope) => $scopedQuery->forScope($scope))
                    ->when($excludeGroup, fn (Builder $scopedQuery) => $scopedQuery->where('scope', '!=', TourScope::Group->value));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(7 - $categories->count())
            ->get(['id', 'name', 'slug'])
            ->map(fn (Destination $destination) => [
                'label' => $destination->name,
                'url' => route('destinations.show', $destination),
            ]);

        return $categories->concat($destinations)->values()->all();
    }

    private function productTabs(Request $request): array
    {
        $flightCategory = ContentCategory::query()
            ->forTaxonomy('service')
            ->where('slug', 've-may-bay')
            ->whereHas('services', fn (Builder $serviceQuery) => $serviceQuery->published())
            ->first(['id', 'slug']);
        $currentServiceCategorySlug = $this->currentServiceCategorySlug($request);
        $isFlightActive = $flightCategory
            && $currentServiceCategorySlug === $flightCategory->slug;
        $isAdditionalServiceActive = ! $isFlightActive
            && $request->routeIs('services.index', 'services.show', 'service-categories.show');
        $tabs = collect([[
            'key' => 'tour',
            'label' => 'Tour trọn gói',
            'icon' => 'fa-solid fa-route',
            'url' => route('tours.search'),
            'is_active' => $request->routeIs(
                'home',
                'tours.*',
                'tour-categories.show',
                'destinations.show',
                'regions.show',
                'countries.show',
                'tour-reviews.public.show',
            ),
        ]]);

        if ($flightCategory) {
            $tabs->push([
                'key' => 'flight',
                'label' => 'Vé máy bay',
                'icon' => 'fa-solid fa-plane-departure',
                'url' => route('service-categories.show', ['category' => $flightCategory->slug]),
                'is_active' => $isFlightActive,
            ]);
        }

        $tabs->push([
            'key' => 'services',
            'label' => 'Dịch vụ cộng thêm',
            'icon' => 'fa-solid fa-grip',
            'url' => route('services.index'),
            'is_active' => $isAdditionalServiceActive,
        ]);

        return $tabs->all();
    }

    private function currentServiceCategorySlug(Request $request): ?string
    {
        $category = $request->route('category');

        if ($category instanceof ContentCategory && $category->taxonomy === 'service') {
            return $category->slug;
        }

        $service = $request->route('service');

        if (! $service instanceof Service) {
            return null;
        }

        $service->loadMissing('category');

        return $service->category?->taxonomy === 'service'
            ? $service->category->slug
            : null;
    }

    private function mobileShortcuts(array $productTabs): array
    {
        $scopeIcons = [
            TourScope::Domestic->value => 'fa-solid fa-map-location-dot',
            TourScope::International->value => 'fa-solid fa-earth-asia',
            TourScope::Group->value => 'fa-solid fa-people-group',
        ];

        return collect($productTabs)
            ->map(fn (array $tab) => [
                'icon' => $tab['icon'],
                'is_active' => $tab['is_active'],
                'key' => $tab['key'],
                'label' => $tab['label'],
                'url' => $tab['url'],
            ])
            ->concat(collect(TourScope::cases())->map(fn (TourScope $scope) => [
                'icon' => $scopeIcons[$scope->value],
                'label' => $scope->label(),
                'url' => route($scope->routeName()),
            ]))
            ->take(6)
            ->values()
            ->all();
    }

    private function canonicalDepartureLocationKey(string $value): string
    {
        $slug = Str::slug(Str::squish($value));

        if ($slug === '') {
            return '';
        }

        if (preg_match('/^(hcm\d*|tp-hcm|tp-ho-chi-minh|ho-chi-minh|ho-chi-minh-city)$/', $slug) === 1
            || str_contains($slug, 'tan-son-nhat')) {
            return 'ho-chi-minh';
        }

        if (preg_match('/^(hn\d*|tp-ha-noi|ha-noi)$/', $slug) === 1 || str_contains($slug, 'noi-bai')) {
            return 'ha-noi';
        }

        return $slug;
    }

    private function departureLocationLabel(string $value, string $fallback): string
    {
        return match ($value) {
            'ho-chi-minh' => 'TP. Hồ Chí Minh',
            'ha-noi' => 'Hà Nội',
            default => Str::squish($fallback),
        };
    }

    private function isValidDepartureDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) && $value > now(config('app.timezone'))->toDateString();
    }

    private function slugFilterValue(string $value): string
    {
        $value = trim($value);

        return preg_match('/^[A-Za-z0-9-]+$/', $value) === 1 ? $value : '';
    }
}
