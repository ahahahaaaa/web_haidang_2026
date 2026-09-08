<?php

namespace App\Http\Controllers;

use App\Services\Travel\VoucherCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherCampaignController extends Controller
{
    public function remembered(string $campaign, Request $request, VoucherCampaignService $vouchers): JsonResponse
    {
        $result = $vouchers->remembered($campaign, $request);
        $response = response()->json([
            'voucher' => $result['voucher'] ?? null,
            'forget_cookie' => (bool) ($result['forget_cookie'] ?? false),
        ]);

        if (($result['cookie'] ?? null) instanceof \Symfony\Component\HttpFoundation\Cookie) {
            $response->withCookie($result['cookie']);
        }

        return $response;
    }
}
