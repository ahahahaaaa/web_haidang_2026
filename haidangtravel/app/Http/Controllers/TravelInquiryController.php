<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTravelInquiryRequest;
use App\Services\Travel\TravelInquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class TravelInquiryController extends Controller
{
    public function store(StoreTravelInquiryRequest $request, TravelInquiryService $service): RedirectResponse|JsonResponse
    {
        try {
            $service->submit($request->validated());
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

        $message = 'Yêu cầu của bạn đã được ghi nhận. Hải Đăng Travel sẽ liên hệ sớm nhất.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return back()
            ->with('travel_inquiry_open_modal', $request->input('submission_mode') === 'modal')
            ->with('travel_inquiry_feedback_mode', $request->input('submission_mode'))
            ->with('travel_inquiry_status', $message);
    }
}
