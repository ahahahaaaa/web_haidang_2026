<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgencyExportToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $configuredToken = trim((string) config('agency_export.token'));

        if ($configuredToken === '') {
            return response()->json([
                'message' => 'Chưa cấu hình token API export cho agency.',
            ], 503);
        }

        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken === '') {
            return response()->json([
                'message' => 'Thiếu bearer token API agency.',
            ], 401);
        }

        if (! hash_equals($configuredToken, $bearerToken)) {
            return response()->json([
                'message' => 'Bearer token API agency không hợp lệ.',
            ], 403);
        }

        return $next($request);
    }
}
