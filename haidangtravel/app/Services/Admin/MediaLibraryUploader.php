<?php

namespace App\Services\Admin;

use App\Services\Cms\SiteSettingsManager;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryUploader
{
    public function __construct(
        protected SiteSettingsManager $siteSettings,
    ) {}

    public function uploadToLibrary(UploadedFile $upload, ?string $name = null, ?string $alt = null): Media
    {
        $resolvedName = trim((string) $name);
        $resolvedName = $resolvedName !== ''
            ? $resolvedName
            : pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME);

        $resolvedAlt = trim((string) $alt);
        $resolvedAlt = $resolvedAlt !== '' ? $resolvedAlt : $resolvedName;

        $settings = $this->siteSettings->current();

        return $settings
            ->addMedia($upload)
            ->usingName($resolvedName)
            ->usingFileName($upload->getClientOriginalName())
            ->withCustomProperties([
                'alt' => $resolvedAlt,
            ])
            ->toMediaCollection('library', config('media-library.disk_name', 'public'));
    }
}
