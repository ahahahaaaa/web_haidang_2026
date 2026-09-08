<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTravelInquiryRequest;
use App\Services\Travel\TravelInquiryService;
use App\Services\Travel\VoucherCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class TravelInquiryController extends Controller
{
    public function store(
        StoreTravelInquiryRequest $request,
        TravelInquiryService $service,
        VoucherCampaignService $vouchers,
    ): RedirectResponse|JsonResponse
    {
        $voucherResult = null;

        try {
            $inquiry = $service->submit($request->validated());
        } catch (Throwable $exception) {
            report($exception);

            $message = 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau hoặc gọi trực tiếp hotline.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 500);
            }

            return back()
                ->withErrors(['form' => $message])
                ->withInput();
        }

        if (filled($request->input('voucher_campaign_slug'))) {
            try {
                $voucherResult = $vouchers->redeem((string) $request->input('voucher_campaign_slug'), $inquiry, $request);
            } catch (Throwable $exception) {
                report($exception);
                $voucherResult = ['voucher' => null, 'cookie' => null, 'status' => 'error'];
            }
        }

        $message = 'Yêu cầu của bạn đã được ghi nhận. Hải Đăng Travel sẽ liên hệ sớm nhất.';

        if ($request->expectsJson()) {
            $response = response()->json([
                'message' => $message,
                'voucher' => $voucherResult['voucher'] ?? null,
                'voucher_status' => $voucherResult['status'] ?? null,
            ]);

            if (($voucherResult['cookie'] ?? null) instanceof \Symfony\Component\HttpFoundation\Cookie) {
                $response->withCookie($voucherResult['cookie']);
            }

            return $response;
        }

        $response = back()
            ->with('travel_inquiry_open_modal', $request->input('submission_mode') === 'modal')
            ->with('travel_inquiry_feedback_mode', $request->input('submission_mode'))
            ->with('travel_inquiry_status', $message);

        if (filled($voucherResult['voucher'] ?? null)) {
            $response->with('travel_inquiry_voucher', $voucherResult['voucher']);
        }

        if (($voucherResult['cookie'] ?? null) instanceof \Symfony\Component\HttpFoundation\Cookie) {
            $response->withCookie($voucherResult['cookie']);
        }

        return $response;
    }
}
