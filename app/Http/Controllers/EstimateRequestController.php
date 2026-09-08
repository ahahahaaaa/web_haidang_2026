<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstimateRequestRequest;
use App\Services\Cms\EstimateRequestService;
use App\Services\Cms\SiteSettingsManager;
use App\Support\EstimatePageContent;
use Illuminate\Http\RedirectResponse;
use Src\Domains\Cms\Models\LandingPage;

class EstimateRequestController extends Controller
{
    public function store(
        StoreEstimateRequestRequest $request,
        EstimateRequestService $estimateRequests,
        SiteSettingsManager $site,
    ): RedirectResponse {
        $landing = LandingPage::query()->where('page_key', 'estimate')->first();
        $estimateConfig = EstimatePageContent::prepareConfig($landing?->estimate_config);

        $estimateRequests->submit(
            $request->validated(),
            $estimateConfig,
            $site->current(),
        );

        return back()->with(
            'estimate_request_status',
            data_get($estimateConfig, 'popup.success_message') ?: 'Yêu cầu dự toán đã được ghi nhận. Đội ngũ sẽ phản hồi sớm nhất có thể.',
        );
    }
}
