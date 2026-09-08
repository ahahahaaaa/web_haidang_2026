<?php

namespace App\Livewire\Admin\Cms;

use App\Services\Admin\MediaLibraryBrowser;
use App\Services\Admin\MediaLibraryFileAuditService;
use App\Services\Admin\MediaLibraryUploader;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Layout('layouts.app')]
#[Title('Quản lý media')]
class MediaManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public array $editForm = [
        'alt' => '',
        'name' => '',
    ];

    public string $collectionFilter = '';

    public string $modelFilter = '';

    public ?array $missingMediaAudit = null;

    public ?int $selectedMediaId = null;

    public string $search = '';

    public array $uploadForm = [
        'alt' => '',
        'name' => '',
    ];

    public mixed $uploadImage = null;

    public function deleteMedia(int $id): void
    {
        $media = Media::query()->findOrFail($id);
        $media->delete();
        $this->clearMissingMediaAudit();

        if ($this->selectedMediaId === $id) {
            $this->resetSelection();
        }

        session()->flash('status', 'Đã xóa ảnh khỏi thư viện media.');
    }

    public function auditMissingMediaFiles(MediaLibraryFileAuditService $auditService): void
    {
        $this->missingMediaAudit = $auditService->auditMissingOriginalImages(
            search: $this->search,
            collection: $this->collectionFilter,
            modelType: $this->modelFilter,
        );

        $scannedCount = (int) data_get($this->missingMediaAudit, 'scanned_count', 0);
        $missingCount = (int) data_get($this->missingMediaAudit, 'missing_count', 0);

        session()->flash(
            'status',
            $missingCount > 0
                ? "Đã quét {$scannedCount} ảnh và phát hiện {$missingCount} record bị mất file gốc."
                : "Đã quét {$scannedCount} ảnh. Không phát hiện file gốc nào bị thất lạc."
        );
    }

    public function deleteMissingMediaFiles(MediaLibraryFileAuditService $auditService): void
    {
        if (((int) data_get($this->missingMediaAudit, 'missing_count', 0)) < 1) {
            return;
        }

        $deletedCount = $auditService->deleteMissingOriginalImages(
            search: data_get($this->missingMediaAudit, 'filters.search', $this->search),
            collection: data_get($this->missingMediaAudit, 'filters.collection', $this->collectionFilter),
            modelType: data_get($this->missingMediaAudit, 'filters.model_type', $this->modelFilter),
        );

        if ($this->selectedMediaId !== null && ! Media::query()->whereKey($this->selectedMediaId)->exists()) {
            $this->resetSelection();
        }

        $this->clearMissingMediaAudit();
        $this->resetPage();

        session()->flash(
            'status',
            $deletedCount > 0
                ? "Đã xóa {$deletedCount} record media bị mất file gốc."
                : 'Không còn record media nào bị mất file gốc để xóa.'
        );
    }

    public function render(MediaLibraryBrowser $browser)
    {
        $media = $browser
            ->imageQuery(
                search: $this->search,
                collection: $this->collectionFilter,
                modelType: $this->modelFilter,
            )
            ->paginate(18);

        $selectedMedia = $this->selectedMediaId
            ? Media::query()->whereKey($this->selectedMediaId)->where('mime_type', 'like', 'image/%')->first()
            : null;

        return view('livewire.admin.cms.media-manager', [
            'collections' => $browser->collectionOptions(),
            'mediaItems' => $media,
            'modelOptions' => $browser->modelOptions(),
            'selectedMedia' => $selectedMedia,
            'selectedMediaPayload' => $selectedMedia ? $browser->payload($selectedMedia) : null,
        ]);
    }

    public function saveMetadata(): void
    {
        $validated = $this->validate([
            'editForm.name' => ['required', 'string', 'max:255'],
            'editForm.alt' => ['nullable', 'string', 'max:255'],
        ]);

        $media = Media::query()->findOrFail((int) $this->selectedMediaId);
        $media->name = $validated['editForm']['name'];
        $media->setCustomProperty('alt', $validated['editForm']['alt'] ?? '');
        $media->save();

        session()->flash('status', 'Đã cập nhật metadata ảnh.');
    }

    public function selectMedia(int $id): void
    {
        $media = Media::query()->whereKey($id)->where('mime_type', 'like', 'image/%')->firstOrFail();

        $this->selectedMediaId = (int) $media->getKey();
        $this->editForm = [
            'alt' => (string) data_get($media->custom_properties, 'alt', ''),
            'name' => $media->name,
        ];
    }

    public function updatedCollectionFilter(): void
    {
        $this->clearMissingMediaAudit();
        $this->resetPage();
    }

    public function updatedModelFilter(): void
    {
        $this->clearMissingMediaAudit();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->clearMissingMediaAudit();
        $this->resetPage();
    }

    public function uploadToLibrary(MediaLibraryUploader $uploader): void
    {
        $validated = $this->validate([
            'uploadImage' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'uploadForm.name' => ['nullable', 'string', 'max:255'],
            'uploadForm.alt' => ['nullable', 'string', 'max:255'],
        ]);

        $upload = $this->uploadImage;

        if (! $upload instanceof UploadedFile) {
            return;
        }

        $uploader->uploadToLibrary(
            upload: $upload,
            name: $validated['uploadForm']['name'] ?? '',
            alt: $validated['uploadForm']['alt'] ?? '',
        );

        $this->uploadImage = null;
        $this->uploadForm = [
            'alt' => '',
            'name' => '',
        ];
        $this->clearMissingMediaAudit();

        session()->flash('status', 'Đã tải ảnh lên thư viện media.');
    }

    protected function clearMissingMediaAudit(): void
    {
        $this->missingMediaAudit = null;
    }

    protected function resetSelection(): void
    {
        $this->selectedMediaId = null;
        $this->editForm = [
            'alt' => '',
            'name' => '',
        ];
    }
}
