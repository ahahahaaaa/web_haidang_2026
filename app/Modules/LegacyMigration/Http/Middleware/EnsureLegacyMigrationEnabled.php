<?php

namespace App\Modules\LegacyMigration\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegacyMigrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('legacy_migration.enabled')) {
            return new JsonResponse(['message' => 'Cổng chuyển dữ liệu đang bị tắt.'], 403);
        }

        return $next($request);
    }
}
