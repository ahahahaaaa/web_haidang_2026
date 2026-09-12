<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryFileAuditService
{
    public function __construct(
        protected MediaLibraryBrowser $browser,
        protected MediaLibraryDeletionService $deletionService,
    ) {}

    /**
     * @return array{
     *     filters: array{search: string, collection: string, model_type: string},
     *     scanned_count: int,
     *     healthy_count: int,
     *     missing_count: int,
     *     missing_size: string,
     *     items: array<int, array{
     *         id: int,
     *         name: string,
     *         file_name: string,
     *         collection_name: string,
     *         model_label: string,
     *         disk: string,
     *         path: string,
     *         created_at: ?string,
     *         size: string
     *     }>,
     *     items_truncated: bool,
     *     ran_at: string
     * }
     */
    public function auditMissingOriginalImages(
        ?string $search = null,
        ?string $collection = null,
        ?string $modelType = null,
        int $sampleLimit = 12,
    ): array {
        $query = $this->imageQuery($search, $collection, $modelType);
        $scannedCount = (clone $query)->count();
        $missingCount = 0;
        $missingBytes = 0;
        $items = [];

        foreach ((clone $query)->reorder('id')->lazyById(100) as $media) {
            if (! $media instanceof Media || $this->originalFileExists($media)) {
                continue;
            }

            $missingCount++;
            $missingBytes += (int) $media->size;

            if (count($items) >= $sampleLimit) {
                continue;
            }

            $items[] = $this->missingItemPayload($media);
        }

        return [
            'filters' => [
                'search' => (string) $search,
                'collection' => (string) $collection,
                'model_type' => (string) $modelType,
            ],
            'scanned_count' => $scannedCount,
            'healthy_count' => max(0, $scannedCount - $missingCount),
            'missing_count' => $missingCount,
            'missing_size' => Number::fileSize($missingBytes),
            'items' => $items,
            'items_truncated' => $missingCount > count($items),
            'ran_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array{deleted_count: int, protected_count: int}
     */
    public function deleteMissingOriginalImages(
        ?string $search = null,
        ?string $collection = null,
        ?string $modelType = null,
    ): array {
        $query = $this->imageQuery($search, $collection, $modelType);
        $deletedCount = 0;
        $protectedCount = 0;

        foreach ((clone $query)->reorder('id')->lazyById(100) as $media) {
            if (! $media instanceof Media || $this->originalFileExists($media)) {
                continue;
            }

            if ($this->deletionService->delete($media)) {
                $deletedCount++;
            } else {
                $protectedCount++;
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'protected_count' => $protectedCount,
        ];
    }

    protected function imageQuery(?string $search = null, ?string $collection = null, ?string $modelType = null): Builder
    {
        return $this->browser->imageQuery(
            search: $search,
            collection: $collection,
            modelType: $modelType,
        );
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     file_name: string,
     *     collection_name: string,
     *     model_label: string,
     *     disk: string,
     *     path: string,
     *     created_at: ?string,
     *     size: string
     * }
     */
    protected function missingItemPayload(Media $media): array
    {
        return [
            'id' => (int) $media->getKey(),
            'name' => $media->name,
            'file_name' => $media->file_name,
            'collection_name' => $media->collection_name,
            'model_label' => $this->browser->modelLabel((string) $media->model_type),
            'disk' => $media->disk,
            'path' => $media->getPathRelativeToRoot(),
            'created_at' => optional($media->created_at)?->format('d/m/Y H:i'),
            'size' => Number::fileSize((int) $media->size),
        ];
    }

    protected function originalFileExists(Media $media): bool
    {
        return Storage::disk($media->disk)->exists($media->getPathRelativeToRoot());
    }
}
