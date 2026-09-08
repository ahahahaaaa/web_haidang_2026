<?php

namespace App\Livewire\Admin\Cms\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HandlesMediaUploads
{
    public array $selectedLibraryMediaSelections = [];

    public function selectLibraryMediaForUpload(
        string $uploadProperty,
        int $mediaId,
        ?string $alt = null,
        ?string $altProperty = null,
    ): void {
        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $this->selectedLibraryMediaSelections[$uploadProperty] = (int) $media->getKey();
        data_set($this, $uploadProperty, null);

        if (filled($altProperty)) {
            data_set(
                $this,
                $altProperty,
                trim((string) $alt) !== ''
                    ? trim((string) $alt)
                    : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name),
            );
        }
    }

    public function clearLibraryMediaSelectionForUpload(string $uploadProperty): void
    {
        unset($this->selectedLibraryMediaSelections[$uploadProperty]);
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function syncSingleImage(
        Model $model,
        string $uploadProperty,
        string $collection,
        array $customProperties = [],
        ?string $fileName = null,
    ): bool
    {
        return $this->syncUploadedImage(
            $model,
            $this->{$uploadProperty} ?? null,
            $collection,
            $customProperties,
            $fileName,
        );
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function syncUploadedImage(
        Model $model,
        mixed $upload,
        string $collection,
        array $customProperties = [],
        ?string $fileName = null,
    ): bool
    {
        if (! $upload instanceof UploadedFile || ! $model instanceof HasMedia) {
            return false;
        }

        $model->clearMediaCollection($collection);
        $resolvedFileName = trim((string) ($fileName ?: $upload->getClientOriginalName()));

        if ($resolvedFileName === '') {
            $resolvedFileName = $upload->hashName();
        }

        $model
            ->addMedia($upload)
            ->usingFileName($resolvedFileName)
            ->withCustomProperties($customProperties)
            ->toMediaCollection($collection, config('media-library.disk_name', 'public'));

        return true;
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function syncSingleImageSelection(
        Model $model,
        string $uploadProperty,
        string $collection,
        array $customProperties = [],
        ?string $fileName = null,
    ): bool {
        $uploaded = $this->syncSingleImage(
            $model,
            $uploadProperty,
            $collection,
            $customProperties,
            $fileName,
        );

        if ($uploaded) {
            return true;
        }

        return $this->syncSingleImageFromLibrary(
            $model,
            $this->selectedLibraryMediaIdForUpload($uploadProperty),
            $collection,
            $customProperties,
        );
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function syncUploadedImageSelection(
        Model $model,
        mixed $upload,
        string $uploadProperty,
        string $collection,
        array $customProperties = [],
        ?string $fileName = null,
    ): bool {
        $uploaded = $this->syncUploadedImage(
            $model,
            $upload,
            $collection,
            $customProperties,
            $fileName,
        );

        if ($uploaded) {
            return true;
        }

        return $this->syncSingleImageFromLibrary(
            $model,
            $this->selectedLibraryMediaIdForUpload($uploadProperty),
            $collection,
            $customProperties,
        );
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function syncSingleImageFromLibrary(Model $model, ?int $mediaId, string $collection, array $customProperties = []): bool
    {
        if (! $mediaId || ! $model instanceof HasMedia) {
            return false;
        }

        $sourceMedia = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->first();

        if (! $sourceMedia) {
            return false;
        }

        $currentMedia = $model->getFirstMedia($collection);
        $resolvedProperties = array_merge(
            $sourceMedia->custom_properties ?? [],
            $customProperties,
            ['source_library_media_id' => (int) $sourceMedia->getKey()],
        );

        if (
            $currentMedia &&
            (int) data_get($currentMedia->custom_properties, 'source_library_media_id') === (int) $sourceMedia->getKey()
        ) {
            foreach ($resolvedProperties as $key => $value) {
                $currentMedia->setCustomProperty($key, $value);
            }

            $currentMedia->save();

            return true;
        }

        $model->clearMediaCollection($collection);

        $sourceMedia->copy(
            model: $model,
            collectionName: $collection,
            diskName: config('media-library.disk_name', 'public'),
            fileAdderCallback: fn ($fileAdder) => $fileAdder->withCustomProperties($resolvedProperties),
        );

        return true;
    }

    protected function selectedLibraryMediaIdForUpload(string $uploadProperty): ?int
    {
        $mediaId = $this->selectedLibraryMediaSelections[$uploadProperty] ?? null;

        return $mediaId ? (int) $mediaId : null;
    }

    protected function resolveSelectedUploadMediaPayload(array $uploadProperties): array
    {
        $selectedIds = collect($uploadProperties)
            ->mapWithKeys(fn (string $uploadProperty) => [$uploadProperty => $this->selectedLibraryMediaIdForUpload($uploadProperty)])
            ->filter(fn (?int $mediaId) => (int) $mediaId > 0)
            ->all();

        if ($selectedIds === []) {
            return [];
        }

        $mediaById = Media::query()
            ->whereIn('id', array_values($selectedIds))
            ->where('mime_type', 'like', 'image/%')
            ->get()
            ->keyBy(fn (Media $media) => (int) $media->getKey());

        $resolved = [];

        foreach ($selectedIds as $uploadProperty => $mediaId) {
            $media = $mediaById->get((int) $mediaId);

            if ($media) {
                $resolved[$uploadProperty] = $media;
            }
        }

        return $resolved;
    }

    protected function resolveSelectedUploadMediaPayloadByPrefix(string $prefix): array
    {
        $uploadProperties = collect(array_keys($this->selectedLibraryMediaSelections))
            ->filter(fn (string $uploadProperty) => $uploadProperty === $prefix || Str::startsWith($uploadProperty, $prefix.'.'))
            ->values()
            ->all();

        return $this->resolveSelectedUploadMediaPayload($uploadProperties);
    }

    protected function reindexLibrarySelectionsAfterRemoval(string $prefix, int $removedIndex): void
    {
        $updatedSelections = [];

        foreach ($this->selectedLibraryMediaSelections as $uploadProperty => $mediaId) {
            if (! Str::startsWith($uploadProperty, $prefix.'.')) {
                $updatedSelections[$uploadProperty] = $mediaId;

                continue;
            }

            $suffix = Str::after($uploadProperty, $prefix.'.');

            if (! ctype_digit($suffix)) {
                $updatedSelections[$uploadProperty] = $mediaId;

                continue;
            }

            $currentIndex = (int) $suffix;

            if ($currentIndex === $removedIndex) {
                continue;
            }

            if ($currentIndex > $removedIndex) {
                $updatedSelections[$prefix.'.'.($currentIndex - 1)] = $mediaId;

                continue;
            }

            $updatedSelections[$uploadProperty] = $mediaId;
        }

        $this->selectedLibraryMediaSelections = $updatedSelections;
    }
}
