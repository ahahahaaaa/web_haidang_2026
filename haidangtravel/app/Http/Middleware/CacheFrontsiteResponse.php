<?php

namespace App\Http\Middleware;

use App\Services\Frontsite\FrontsiteCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CacheFrontsiteResponse
{
    protected const CSRF_PLACEHOLDER = '__FRONTSITE_CSRF_TOKEN__';

    public function __construct(protected FrontsiteCache $cache)
    {
    }

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! $this->shouldRead($request)) {
            return $next($request);
        }

        $groups = $this->groupsFor($request);
        $cacheKey = $this->cacheKey($request);
        $cached = $this->cache->remember(
            $cacheKey,
            $groups,
            fn (): ?array => null,
            $this->ttlFor($request),
        );

        if (is_array($cached)) {
            return $this->responseFromCache($cached, $request);
        }

        $response = $next($request);

        if ($this->shouldStore($request, $response)) {
            $this->storeResponse($request, $response, $groups, $cacheKey);
            $response->headers->set((string) config('frontsite_cache.middleware.header', 'X-Frontsite-Cache'), 'MISS');
        }

        return $response;
    }

    protected function cacheKey(Request $request): string
    {
        return 'response:'.sha1($request->fullUrl());
    }

    protected function groupsFor(Request $request): array
    {
        $route = $request->route();
        $routeName = (string) $route?->getName();
        $groups = ['responses', 'chrome'];

        if ($request->query->count() > 0) {
            $groups[] = 'query';
        }

        return match ($routeName) {
            'home' => [...$groups, 'home', 'landing-page-key:home'],
            'about' => [...$groups, 'landing-page-key:about'],
            'contact' => [...$groups, 'landing-page-key:contact'],
            'services.index' => [...$groups, 'services', 'landing-page-key:services'],
            'service-categories.show' => [...$groups, 'services', 'service-categories'],
            'services.show' => [...$groups, 'services', 'service:'.$this->routeModelKey($request, 'service')],
            'blog.index' => [...$groups, 'blog', 'landing-page-key:blog'],
            'blog.show' => [...$groups, 'blog', 'blog-post:'.$this->routeModelKey($request, 'post')],
            'tours.domestic' => [...$groups, 'tours', 'tour-scope:domestic', 'landing-page-key:domestic_tours'],
            'tours.international' => [...$groups, 'tours', 'tour-scope:international', 'landing-page-key:international_tours'],
            'tours.group' => [...$groups, 'tours', 'tour-scope:group', 'landing-page-key:group_tours'],
            'tours.search' => [...$groups, 'tours', 'query'],
            'tours.show' => [...$groups, 'tours', 'tour:'.$this->routeModelKey($request, 'tour')],
            'tour-categories.show' => [...$groups, 'tours', 'taxonomies', 'tour-categories', 'tour-category:'.$this->routeModelKey($request, 'category')],
            'destinations.show' => [...$groups, 'tours', 'taxonomies', 'destinations'],
            'regions.show' => [...$groups, 'tours', 'taxonomies', 'regions', 'region:'.$this->routeModelKey($request, 'region')],
            'countries.show' => [...$groups, 'tours', 'taxonomies'],
            'landing.show' => [...$groups, 'landing'],
            default => $groups,
        };
    }

    protected function responseFromCache(array $cached, Request $request): Response
    {
        $response = response(
            $this->restoreCsrfToken((string) ($cached['content'] ?? ''), $request),
            (int) ($cached['status'] ?? 200),
        );

        foreach (($cached['headers'] ?? []) as $name => $value) {
            $response->headers->set($name, $value);
        }

        $response->headers->set((string) config('frontsite_cache.middleware.header', 'X-Frontsite-Cache'), 'HIT');

        return $response;
    }

    protected function routeModelKey(Request $request, string $name): string
    {
        $parameter = $request->route($name);

        if (is_object($parameter) && method_exists($parameter, 'getKey')) {
            return (string) $parameter->getKey();
        }

        return sha1((string) $parameter);
    }

    protected function shouldRead(Request $request): bool
    {
        return (bool) config('frontsite_cache.middleware.enabled', true)
            && $this->cache->enabled()
            && $request->isMethod('GET')
            && ! Auth::check()
            && ! $request->ajax()
            && ! $this->hasSessionFeedback($request);
    }

    protected function shouldStore(Request $request, SymfonyResponse $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains($contentType, 'text/html')
            && ! $response->headers->has('Set-Cookie')
            && ! $this->hasSessionFeedback($request);
    }

    protected function storeResponse(Request $request, Response $response, array $groups, string $cacheKey): void
    {
        $contentType = (string) $response->headers->get('Content-Type', 'text/html; charset=UTF-8');
        $payload = [
            'content' => $this->prepareCsrfToken((string) $response->getContent(), $request),
            'headers' => array_filter([
                'Content-Type' => $contentType,
                'X-Robots-Tag' => $response->headers->get('X-Robots-Tag'),
            ]),
            'status' => $response->getStatusCode(),
        ];

        $this->cache->remember(
            $cacheKey,
            $groups,
            fn (): array => $payload,
            $this->ttlFor($request),
        );
    }

    protected function ttlFor(Request $request): int
    {
        return $request->query->count() > 0
            ? $this->cache->ttl('query')
            : $this->cache->ttl('response');
    }

    protected function prepareCsrfToken(string $content, Request $request): string
    {
        $token = $request->session()->token();

        return $token !== '' ? str_replace($token, self::CSRF_PLACEHOLDER, $content) : $content;
    }

    protected function restoreCsrfToken(string $content, Request $request): string
    {
        return str_replace(self::CSRF_PLACEHOLDER, $request->session()->token(), $content);
    }

    protected function hasSessionFeedback(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        return $session->hasOldInput()
            || $session->has('errors')
            || $session->has('travel_inquiry_status')
            || $session->has('travel_inquiry_open_modal')
            || $session->has('travel_inquiry_feedback_mode');
    }
}
