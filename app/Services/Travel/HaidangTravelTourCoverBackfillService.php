<?php

namespace App\Services\Travel;

use Throwable;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class HaidangTravelTourCoverBackfillService
{
    public function __construct(
        protected HaidangTravelImportService $importService,
    ) {
    }

    /**
     * @return array{
     *     inspected:int,
     *     updated:int,
     *     already_had:int,
     *     still_missing:int,
     *     snapshot_path:string|null
     * }
     */
    public function backfill(bool $refresh = false): array
    {
        $snapshotPath = null;
        $snapshot = $this->importService->loadSnapshot();

        if ($refresh) {
            try {
                $snapshot = $this->importService->fetchSnapshotFromSource();
            } catch (Throwable) {
                $snapshot = $this->importService->loadSnapshot();
            }
        }

        if ($refresh && $snapshot !== []) {
            $snapshotPath = $this->importService->storeSnapshot($snapshot);
        }

        $tourImages = $this->imageMap((array) data_get($snapshot, 'tours', []));
        $destinationImages = $this->imageMap((array) data_get($snapshot, 'destinations', []));
        $regionImages = $this->imageMap((array) data_get($snapshot, 'regions', []));
        $categoryImages = $this->imageMap((array) data_get($snapshot, 'tour_categories', []));
        $scopeFallbackImages = $this->scopeFallbackImages((array) data_get($snapshot, 'tours', []));

        $summary = [
            'inspected' => 0,
            'updated' => 0,
            'already_had' => 0,
            'still_missing' => 0,
            'snapshot_path' => $snapshotPath,
        ];

        Tour::query()
            ->with(['destination', 'region', 'primaryCategory'])
            ->orderBy('id')
            ->get()
            ->each(function (Tour $tour) use (&$summary, $tourImages, $destinationImages, $regionImages, $categoryImages, $scopeFallbackImages): void {
                $summary['inspected']++;

                if (! $this->needsImageBackfill($tour->cover_image_url)) {
                    $summary['already_had']++;

                    return;
                }

                $imageUrl = $tourImages[$tour->slug]
                    ?? $destinationImages[$tour->destination?->slug ?? '']
                    ?? $regionImages[$tour->region?->slug ?? '']
                    ?? $categoryImages[$tour->primaryCategory?->slug ?? '']
                    ?? $this->currentModelImage($tour->destination)
                    ?? $this->currentModelImage($tour->region)
                    ?? $this->currentModelImage($tour->primaryCategory)
                    ?? $scopeFallbackImages[$tour->scope?->value ?? '']
                    ?? $scopeFallbackImages['default'];

                if (blank($imageUrl)) {
                    $summary['still_missing']++;

                    return;
                }

                $tour->forceFill([
                    'cover_alt' => filled($tour->cover_alt) ? $tour->cover_alt : $tour->title,
                    'cover_image_url' => $imageUrl,
                ])->save();

                $summary['updated']++;
            });

        return $summary;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, string>
     */
    protected function imageMap(array $items): array
    {
        $images = [];

        foreach ($items as $item) {
            $slug = trim((string) data_get($item, 'slug'));
            $imageUrl = trim((string) data_get($item, 'cover_image_url'));

            if ($slug === '' || ! $this->isUsableImageUrl($imageUrl)) {
                continue;
            }

            $images[$slug] = $imageUrl;
        }

        return $images;
    }

    protected function currentModelImage(TourCategory|Destination|Region|null $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $directUrl = trim((string) $model->cover_image_url);

        if ($this->isUsableImageUrl($directUrl)) {
            return $directUrl;
        }

        $avatarUrl = trim((string) $model->getFirstMediaUrl('avatar'));

        return $this->isUsableImageUrl($avatarUrl) ? $avatarUrl : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tours
     * @return array<string, string>
     */
    protected function scopeFallbackImages(array $tours): array
    {
        $fallbacks = [
            'default' => '',
        ];

        foreach ($tours as $tour) {
            $imageUrl = trim((string) data_get($tour, 'cover_image_url'));
            $scope = trim((string) data_get($tour, 'scope'));

            if (! $this->isUsableImageUrl($imageUrl)) {
                continue;
            }

            if ($fallbacks['default'] === '') {
                $fallbacks['default'] = $imageUrl;
            }

            if ($scope !== '' && empty($fallbacks[$scope])) {
                $fallbacks[$scope] = $imageUrl;
            }
        }

        return array_filter($fallbacks, fn (string $imageUrl) => $imageUrl !== '');
    }

    protected function needsImageBackfill(?string $imageUrl): bool
    {
        return ! $this->isUsableImageUrl($imageUrl);
    }

    protected function isUsableImageUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        if (preg_match('/logo|icon|user|placeholder|avatar-default|logo1-default/i', $url)) {
            return false;
        }

        return preg_match('/\.(jpe?g|png|webp|gif|avif)(\?|$)/i', $url) === 1
            || str_contains($url, '/thumb/');
    }
}
