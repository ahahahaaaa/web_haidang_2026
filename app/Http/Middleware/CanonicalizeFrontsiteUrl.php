<?php

namespace App\Http\Middleware;

use App\Support\FrontsiteUrls;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CanonicalizeFrontsiteUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldRedirect($request)) {
            return $next($request);
        }

        $targetUrl = $this->targetUrl($request);

        if ($targetUrl === null) {
            return $next($request);
        }

        return redirect()->away($targetUrl, 301);
    }

    protected function shouldRedirect(Request $request): bool
    {
        if (! (bool) config('frontsite_seo.canonical_redirect_enabled', false)) {
            return false;
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        return FrontsiteUrls::canonicalBaseUrl() !== '';
    }

    protected function targetUrl(Request $request): ?string
    {
        $canonicalHost = FrontsiteUrls::canonicalHost();
        $requestHost = Str::lower($request->getHost());
        $redirectHosts = FrontsiteUrls::canonicalRedirectHosts();

        if ($canonicalHost === '' || ! in_array($requestHost, $redirectHosts, true)) {
            return null;
        }

        $requestPath = $this->requestUriPath($request);
        $canonicalPath = FrontsiteUrls::canonicalPath($requestPath);
        $isSecure = $request->isSecure()
            || Str::contains(Str::lower((string) $request->headers->get('X-Forwarded-Proto')), 'https');

        if ($requestHost === $canonicalHost && $isSecure && $requestPath === $canonicalPath) {
            return null;
        }

        $queryString = $request->getQueryString();
        $targetUrl = rtrim(FrontsiteUrls::canonicalBaseUrl(), '/').$canonicalPath;

        return $queryString ? $targetUrl.'?'.$queryString : $targetUrl;
    }

    protected function requestUriPath(Request $request): string
    {
        $requestUriPath = parse_url($request->getRequestUri(), PHP_URL_PATH);
        $path = is_string($requestUriPath) && $requestUriPath !== ''
            ? $requestUriPath
            : $request->getPathInfo();

        return '/'.ltrim($path, '/');
    }
}
