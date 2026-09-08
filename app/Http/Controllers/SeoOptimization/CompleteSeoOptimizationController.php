<?php

namespace App\Http\Controllers\SeoOptimization;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeoOptimization\CompleteSeoOptimizationRequest;
use App\Services\SeoOptimization\OptimizationAutomationService;
use Illuminate\Http\JsonResponse;

class CompleteSeoOptimizationController extends Controller
{
    public function __invoke(CompleteSeoOptimizationRequest $request, OptimizationAutomationService $automation): JsonResponse
    {
        $data = $request->validated();

        return response()->json($automation->complete($data['proposal_id'], $data['content_hash'], $request->user(), $request->attributes->get('seo_optimization_credential')->id));
    }
}
