<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\StoreContentClusterRequest;
use Src\Domains\Seo\Actions\CreateClusterAction;
use Src\Domains\Seo\Actions\DispatchClusterGenerationAction;
use Src\Domains\Seo\Enums\SeoClusterStatus;

class SeoClusterController extends Controller
{
    public function store(StoreContentClusterRequest $request, CreateClusterAction $createCluster, DispatchClusterGenerationAction $dispatchGeneration)
    {
        $cluster = $createCluster->execute(array_merge(
            $request->validated(),
            ['status' => SeoClusterStatus::Approved->value],
        ));

        if ($request->boolean('dispatch_generation', true)) {
            $dispatchGeneration->execute($cluster);
        }

        return response()->json(['data' => $cluster], 201);
    }
}
