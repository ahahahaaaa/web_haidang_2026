<?php

namespace App\Http\Controllers\SeoOptimization;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeoOptimization\UploadContentCreationImageRequest;
use App\Services\SeoOptimization\ContentCreationMediaService;
use Illuminate\Http\JsonResponse;

class UploadContentCreationImageController extends Controller
{
    public function __invoke(UploadContentCreationImageRequest $request, string $task, ContentCreationMediaService $media): JsonResponse
    {
        $data = $request->validated();

        return response()->json($media->upload(
            $task,
            $data['lease_token'],
            $data['reference'],
            $data['image'],
            $data['alt'],
            (string) ($data['prompt'] ?? ''),
            $request->user(),
            (string) $request->attributes->get('seo_optimization_credential')->id,
        ));
    }
}
