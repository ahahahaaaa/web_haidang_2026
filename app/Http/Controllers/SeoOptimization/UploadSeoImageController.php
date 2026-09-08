<?php

namespace App\Http\Controllers\SeoOptimization;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeoOptimization\UploadSeoImageRequest;
use App\Services\SeoOptimization\OptimizationMediaService;
use Illuminate\Http\JsonResponse;

class UploadSeoImageController extends Controller
{
    public function __invoke(UploadSeoImageRequest $request, string $task, OptimizationMediaService $media): JsonResponse
    {
        $data = $request->validated();

        return response()->json($media->upload($task, $data['lease_token'], $data['image'], $data['alt'], $data['prompt'],
            $request->user(), (string) $request->attributes->get('seo_optimization_credential')->id));
    }
}
