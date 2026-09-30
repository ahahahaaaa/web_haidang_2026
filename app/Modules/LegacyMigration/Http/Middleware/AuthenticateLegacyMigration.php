<?php

namespace App\Modules\LegacyMigration\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateLegacyMigration
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredHash = mb_strtolower(trim((string) config('legacy_migration.token_hash')));
        $token = trim((string) $request->bearerToken());

        if ($configuredHash === '' || $token === '' || ! hash_equals($configuredHash, hash('sha256', $token))) {
            return (new JsonResponse(['message' => 'Bearer token không hợp lệ.'], 401))
                ->header('WWW-Authenticate', 'Bearer');
        }

        $allowedIps = (array) config('legacy_migration.allowed_source_ips', []);

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return new JsonResponse(['message' => 'Địa chỉ nguồn không được phép gửi dữ liệu.'], 403);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
