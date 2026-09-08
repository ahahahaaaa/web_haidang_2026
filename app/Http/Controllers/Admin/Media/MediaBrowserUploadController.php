<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Services\Admin\MediaLibraryBrowser;
use App\Services\Admin\MediaLibraryUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaBrowserUploadController extends Controller
{
    public function __invoke(
        Request $request,
        MediaLibraryUploader $uploader,
        MediaLibraryBrowser $browser,
    ): JsonResponse {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'name' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $media = $uploader->uploadToLibrary(
            upload: $validated['image'],
            name: $validated['name'] ?? '',
            alt: $validated['alt'] ?? '',
        );

        return response()->json([
            'data' => $browser->payload($media),
            'message' => 'Đã tải ảnh lên thư viện media.',
            'filters' => [
                'collections' => $browser->collectionOptions(),
                'models' => $browser->modelOptions(),
            ],
        ], 201);
    }
}
