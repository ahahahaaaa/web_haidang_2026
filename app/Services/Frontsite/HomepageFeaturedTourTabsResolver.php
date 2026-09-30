<?php

namespace App\Services\Frontsite;

use App\Support\TravelHomePageConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class HomepageFeaturedTourTabsResolver
{
    public function queryForScope(TourScope $scope, int $limit, ?TourCategory $featuredTourCategory = null): Collection
    {
        return $this->featuredTourQuery($featuredTourCategory)
            ->forScope($scope)
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function resolve(
        array $config,
        int $limit,
        ?TourCategory $featuredTourCategory,
        Collection $allTours,
        Collection $scopeTours,
    ): Collection {
        $allConfig = is_array($config['all'] ?? null) ? $config['all'] : [];
        $allTab = [
            'description' => trim((string) ($allConfig['description'] ?? '')) ?: 'Tổng hợp các tour trọn gói và tour du lịch đoàn đang được quan tâm để bạn dễ so sánh hành trình, lịch đi và mức giá.',
            'id' => 'all',
            'items' => $allTours,
            'label' => trim((string) ($allConfig['label'] ?? '')) ?: 'Tất cả',
            'title' => trim((string) ($allConfig['title'] ?? '')) ?: 'Tour hot trong tháng',
            'url' => route('tours.search'),
        ];

        $configuredTabs = collect($config['filters'] ?? [])
            ->filter(fn (mixed $filter) => is_array($filter))
            ->map(fn (array $filter, int $index) => $this->resolveFilter(
                $filter,
                $index,
                $limit,
                $featuredTourCategory,
                $scopeTours,
                $allTab['title'],
                $allTab['description'],
            ))
            ->filter()
            ->unique('id')
            ->values();

        return collect([$allTab])->concat($configuredTabs)->values();
    }

    protected function resolveFilter(
        array $filter,
        int $index,
        int $limit,
        ?TourCategory $featuredTourCategory,
        Collection $scopeTours,
        string $globalTitle,
        string $globalDescription,
    ): ?array {
        $sourceType = (string) ($filter['source_type'] ?? '');
        $sourceValue = trim((string) ($filter['source_value'] ?? ''));
        $filterId = Str::slug((string) ($filter['uuid'] ?? '')) ?: 'filter-'.($index + 1);

        if ($sourceType === TravelHomePageConfig::FEATURED_TOUR_FILTER_SCOPE) {
            $scope = TourScope::tryFrom($sourceValue);

            if (! $scope) {
                return null;
            }

            return $this->payload(
                $filterId,
                $scope->label(),
                $scopeTours->get($scope->value, collect()),
                route($scope->routeName()),
                $globalTitle,
                $globalDescription,
            );
        }

        $source = match ($sourceType) {
            TravelHomePageConfig::FEATURED_TOUR_FILTER_DESTINATION => Destination::query()
                ->published()
                ->regularDestinations()
                ->where('slug', $sourceValue)
                ->first(),
            TravelHomePageConfig::FEATURED_TOUR_FILTER_TOPIC => TourCategory::query()
                ->published()
                ->where('slug', $sourceValue)
                ->first(),
            TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION => Region::query()
                ->published()
                ->where('slug', $sourceValue)
                ->first(),
            default => null,
        };

        if (! $source) {
            return null;
        }

        $sourceName = trim((string) data_get($source, 'name'));

        return $this->payload(
            $filterId,
            $sourceName,
            $this->queryByFilter($sourceType, $sourceValue, $limit, $featuredTourCategory),
            match ($sourceType) {
                TravelHomePageConfig::FEATURED_TOUR_FILTER_DESTINATION => route('destinations.show', $source),
                TravelHomePageConfig::FEATURED_TOUR_FILTER_TOPIC => route('tour-categories.show', $source),
                default => route('regions.show', $source),
            },
            $globalTitle,
            $globalDescription,
        );
    }

    protected function featuredTourQuery(?TourCategory $featuredTourCategory = null): Builder
    {
        return Tour::query()
            ->published()
            ->with([
                'media',
                'departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date'),
                'destination.media',
                'primaryCategory.media',
                'region.media',
            ])
            ->when(
                $featuredTourCategory,
                fn (Builder $query) => $query->whereHas('categories', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($featuredTourCategory->getKey())),
                fn (Builder $query) => $query->where('is_featured', true),
            );
    }

    protected function queryByFilter(
        string $sourceType,
        string $sourceValue,
        int $limit,
        ?TourCategory $featuredTourCategory = null,
    ): Collection {
        $query = $this->featuredTourQuery($featuredTourCategory);

        match ($sourceType) {
            TravelHomePageConfig::FEATURED_TOUR_FILTER_DESTINATION => $query->whereHas(
                'destinations',
                fn (Builder $destinationQuery) => $destinationQuery->where('slug', $sourceValue),
            ),
            TravelHomePageConfig::FEATURED_TOUR_FILTER_TOPIC => $query->whereHas(
                'categories',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $sourceValue),
            ),
            TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION => $query->whereHas(
                'regions',
                fn (Builder $regionQuery) => $regionQuery->where('slug', $sourceValue),
            ),
            default => null,
        };

        return $query
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    protected function payload(
        string $id,
        string $sourceName,
        Collection $items,
        string $url,
        string $globalTitle,
        string $globalDescription,
    ): array {
        return [
            'description' => $globalDescription,
            'id' => $id,
            'items' => $items,
            'label' => $sourceName,
            'title' => $globalTitle,
            'url' => $url,
        ];
    }
}
