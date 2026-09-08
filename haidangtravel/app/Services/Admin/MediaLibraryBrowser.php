<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryBrowser
{
    public function imageQuery(?string $search = null, ?string $collection = null, ?string $modelType = null): Builder
    {
        return Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('file_name', 'like', '%'.$search.'%');
                });
            })
            ->when(filled($collection), fn (Builder $query) => $query->where('collection_name', $collection))
            ->when(filled($modelType), fn (Builder $query) => $query->where('model_type', $modelType))
            ->orderByDesc('created_at');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function collectionOptions(): array
    {
        return Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->select('collection_name')
            ->distinct()
            ->orderBy('collection_name')
            ->pluck('collection_name')
            ->map(fn (string $collection) => [
                'value' => $collection,
                'label' => $collection,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function modelOptions(): array
    {
        return Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type')
            ->filter()
            ->map(fn (string $modelType) => [
                'value' => $modelType,
                'label' => $this->modelLabel($modelType),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     file_name: string,
     *     alt: string,
     *     url: string,
     *     collection_name: string,
     *     model_type: string,
     *     model_label: string,
     *     created_at: ?string,
     *     size: string
     * }
     */
    public function payload(Media $media): array
    {
        return [
            'id' => (int) $media->getKey(),
            'name' => $media->name,
            'file_name' => $media->file_name,
            'alt' => (string) data_get($media->custom_properties, 'alt', ''),
            'url' => $media->getUrl(),
            'collection_name' => $media->collection_name,
            'model_type' => (string) $media->model_type,
            'model_label' => $this->modelLabel((string) $media->model_type),
            'created_at' => optional($media->created_at)?->format('d/m/Y H:i'),
            'size' => Number::fileSize($media->size),
        ];
    }

    public function modelLabel(?string $modelType): string
    {
        return match ($modelType) {
            'Src\\Domains\\Cms\\Models\\BlogPost' => 'Blog',
            'Src\\Domains\\Cms\\Models\\Project' => 'Dự án',
            'Src\\Domains\\Cms\\Models\\Service' => 'Dịch vụ',
            'Src\\Domains\\Cms\\Models\\SiteSetting' => 'Thiết lập site',
            'Src\\Domains\\Cms\\Models\\SliderItem' => 'Slider item',
            default => class_basename((string) $modelType),
        };
    }
}
