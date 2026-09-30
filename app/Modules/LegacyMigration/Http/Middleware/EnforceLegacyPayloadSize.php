<?php

namespace App\Modules\LegacyMigration\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceLegacyPayloadSize
{
    public function handle(Request $request, Closure $next): Response
    {
        $maximum = max(1, (int) config('legacy_migration.max_body_bytes'));
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);

        if ($contentLength > $maximum || strlen($request->getContent()) > $maximum) {
            return new JsonResponse(['message' => 'Payload vượt quá dung lượng cho phép.'], 413);
        }

        return $next($request);
    }
}
