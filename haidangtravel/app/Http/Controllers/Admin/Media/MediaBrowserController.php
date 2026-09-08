<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Services\Admin\MediaLibraryBrowser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaBrowserController extends Controller
{
    public function __invoke(Request $request, MediaLibraryBrowser $browser): JsonResponse
    {
        $images = $browser
            ->imageQuery(
                search: $request->string('q')->trim()->toString(),
                collection: $request->string('collection')->trim()->toString(),
                modelType: $request->string('model')->trim()->toString(),
            )
            ->paginate(18)
            ->withQueryString();

        return response()->json([
            'data' => $images->getCollection()->map(fn ($media) => $browser->payload($media))->values(),
            'meta' => [
                'current_page' => $images->currentPage(),
                'last_page' => $images->lastPage(),
                'per_page' => $images->perPage(),
                'total' => $images->total(),
            ],
            'filters' => [
                'collections' => $browser->collectionOptions(),
                'models' => $browser->modelOptions(),
            ],
        ]);
    }
}
