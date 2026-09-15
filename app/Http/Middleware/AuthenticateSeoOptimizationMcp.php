<?php

namespace App\Http\Middleware;

use App\Models\SeoOptimizationCredential;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSeoOptimizationMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('seo_optimization.enabled', true) || ! config('seo_optimization.mcp_enabled', false)) {
            return $this->error('MCP_DISABLED', 'Kết nối SEO AI Optimize chưa được bật.', 404);
        }

        $origin = $request->headers->get('Origin');
        if ($origin !== null && ! in_array($origin, config('seo_optimization.mcp_allowed_origins', []), true)) {
            return $this->error('ORIGIN_DENIED', 'Nguồn gửi yêu cầu không được cho phép.', 403);
        }

        $maxBytes = $request->routeIs('mcp.seo-optimization.media', 'mcp.seo-optimization.content-creation.media')
            ? (int) config('seo_optimization.media_max_bytes', 10485760) + 65536
            : (int) config('seo_optimization.mcp_max_request_bytes', 1048576);
        if ((int) $request->header('Content-Length', 0) > $maxBytes || strlen($request->getContent()) > $maxBytes) {
            return $this->error('PAYLOAD_TOO_LARGE', 'Yêu cầu vượt giới hạn kích thước.', 413);
        }

        $token = $request->bearerToken();
        if (! is_string($token) || strlen($token) < 32 || strlen($token) > 512) {
            return $this->unauthenticated();
        }

        $credential = SeoOptimizationCredential::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $credential || $credential->revoked_at !== null
            || ($credential->expires_at !== null && $credential->expires_at->isPast())
            || ! $credential->user || ! $credential->user->is_active
            || $credential->user->email_verified_at === null) {
            return $this->unauthenticated();
        }

        $guard = Auth::guard();
        $previousUser = $guard->hasUser() ? $guard->user() : null;
        $previousAuthResolver = Auth::userResolver();
        $previousRequestResolver = $request->getUserResolver();
        $user = $credential->user;

        $guard->setUser($user);
        Auth::resolveUsersUsing(static fn () => $user);
        $request->setUserResolver(static fn () => $user);
        $request->attributes->set('seo_optimization_credential', $credential);
        $credential->forceFill(['last_used_at' => now()])->saveQuietly();

        try {
            $response = $next($request);
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

            return $response;
        } finally {
            $request->attributes->remove('seo_optimization_credential');
            $request->setUserResolver($previousRequestResolver);
            Auth::resolveUsersUsing($previousAuthResolver);
            $previousUser ? $guard->setUser($previousUser) : $guard->forgetUser();
        }
    }

    private function unauthenticated(): Response
    {
        return $this->error('UNAUTHENTICATED', 'Cần token SEO AI Optimize còn hiệu lực.', 401)
            ->header('WWW-Authenticate', 'Bearer realm="seo-optimization", error="invalid_token"');
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
