# Runtime Contract

Use this file when you need the current media popup and Spatie integration contract.

## Shared popup runtime

- `resources/js/admin/quill.js`
- `resources/views/partials/admin/media-browser.blade.php`

The same popup supports:

- Quill insert image mode
- picker mode for model-bound image fields
- quick upload into the shared library

## Browse and upload APIs

- `app/Http/Controllers/Admin/Media/MediaBrowserController.php`
- `app/Http/Controllers/Admin/Media/MediaBrowserUploadController.php`
- `app/Services/Admin/MediaLibraryBrowser.php`
- `app/Services/Admin/MediaLibraryUploader.php`

## Livewire integration

- `app/Livewire/Admin/Cms/Concerns/HandlesMediaUploads.php`
- `resources/views/components/admin/image-dropzone.blade.php`

Important helpers:

- `selectLibraryMediaForUpload()`
- `clearLibraryMediaSelectionForUpload()`
- `syncSingleImageSelection()`
- `syncSingleImageFromLibrary()`
- `resolveSelectedUploadMediaPayload()`

## Library manager

- `app/Livewire/Admin/Cms/MediaManager.php`
- `resources/views/livewire/admin/cms/media-manager.blade.php`

## Key rules

- shared library uploads go to collection `library`
- only image media is surfaced in the browser
- payloads include `id`, `name`, `alt`, `url`, `collection_name`, and model metadata
