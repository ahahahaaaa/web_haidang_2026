<?php

namespace App\Services\Frontsite;

use App\Support\TravelHomePageConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;

class FeaturedTourPopularSearchResolver
{
    /**
     * @param  array<int, array<string, mixed>>  $regionFilters
     * @param  array<int, array<string, mixed>>  $manualLinks
     * @return array<int, array<string, string>>
     */
    public function resolve(array $regionFilters, array $manualLinks, string $scope = ''): array
    {
        $filters = collect($regionFilters)
            ->filter(fn (mixed $filter) => is_array($filter)
                && filled($filter['uuid'] ?? null)
                && filled($filter['source_slug'] ?? null))
            ->unique('uuid')
            ->values();

        $links = $filters->isEmpty() ? collect() : $this->destinationLinks($filters, $scope);

        foreach ($manualLinks as $manualLink) {
            if (! is_array($manualLink)
                || blank($manualLink['label'] ?? null)
                || ! TravelHomePageConfig::isSafeFeaturedTourPopularSearchUrl($manualLink['url'] ?? null)) {
                continue;
            }

            $manual = [
                'label' => trim((string) $manualLink['label']),
                'url' => trim((string) $manualLink['url']),
                'filter_uuid' => trim((string) ($manualLink['filter_uuid'] ?? '')),
            ];
            $duplicateIndex = $links->search(fn (array $link): bool => $link['filter_uuid'] === $manual['filter_uuid']
                && mb_strtolower($link['label']) === mb_strtolower($manual['label']));

            if ($duplicateIndex === false) {
                $links->push($manual);
            } else {
                $links->put($duplicateIndex, $manual);
            }
        }

        return $links->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filters
     * @return Collection<int, array<string, string>>
     */
    protected function destinationLinks(Collection $filters, string $scope): Collection
    {
        $tourScope = TourScope::tryFrom($scope);
        $destinationsByRegion = Destination::query()
            ->published()
            ->whereHas('region', fn (Builder $region) => $region->published()->whereIn('slug', $filters->pluck('source_slug')->all()))
            ->whereHas('tours', function (Builder $tour) use ($tourScope, $scope): void {
                $tour->published();

                if ($tourScope) {
                    $tour->forScope($tourScope);
                } elseif ($scope === 'non_group') {
                    $tour->where('scope', '!=', TourScope::Group->value);
                }
            })
            ->with('region:id,slug')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'region_id', 'name', 'slug', 'is_featured', 'sort_order'])
            ->groupBy(fn (Destination $destination): string => (string) $destination->region?->slug);

        $queues = $filters->mapWithKeys(fn (array $filter): array => [
            (string) $filter['uuid'] => collect($destinationsByRegion->get((string) $filter['source_slug'], []))->values(),
        ]);
        $links = collect();

        while ($links->count() < TravelHomePageConfig::FEATURED_TOUR_POPULAR_SEARCH_LIMIT && $queues->contains(fn (Collection $queue) => $queue->isNotEmpty())) {
            foreach ($queues as $filterUuid => $queue) {
                $destination = $queue->shift();

                if (! $destination) {
                    continue;
                }

                $links->push([
                    'label' => $destination->name,
                    'url' => route('tours.search', array_filter([
                        'destination' => $destination->slug,
                        'scope' => $scope,
                    ])),
                    'filter_uuid' => (string) $filterUuid,
                ]);

                if ($links->count() >= TravelHomePageConfig::FEATURED_TOUR_POPULAR_SEARCH_LIMIT) {
                    break;
                }
            }
        }

        return $links;
    }
}
