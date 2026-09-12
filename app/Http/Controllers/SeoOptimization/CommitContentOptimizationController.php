<?php

namespace App\Http\Controllers\SeoOptimization;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeoOptimization\CommitContentOptimizationRequest;
use App\Services\SeoOptimization\OptimizationAutomationService;
use Illuminate\Http\JsonResponse;

class CommitContentOptimizationController extends Controller
{
    public function __invoke(CommitContentOptimizationRequest $request, OptimizationAutomationService $automation): JsonResponse
    {
        $data = $request->validated();

        return response()->json($automation->complete(
            $data['proposal_id'],
            $data['content_hash'],
            $request->user(),
            $request->attributes->get('seo_optimization_credential')->id,
        ));
    }
}
