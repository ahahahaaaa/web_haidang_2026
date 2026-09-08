<?php

namespace App\Support;

use Illuminate\Support\Str;
use Src\Domains\Cms\Models\BlogPost;

class FrontsiteUrls
{
    public const FALLBACK_BLOG_CATEGORY_SLUG = 'bai-viet';

    /**
     * @return array{category: string, post: BlogPost}
     */
    public static function blogPostRouteParameters(BlogPost $post): array
    {
        return [
            'category' => self::blogPostCategorySlug($post),
            'post' => $post,
        ];
    }

    public static function blogPost(BlogPost $post): string
    {
        return route('blog.show', self::blogPostRouteParameters($post));
    }

    public static function canonicalBlogPost(BlogPost $post): string
    {
        $url = filled($post->canonical_url)
            ? (string) $post->canonical_url
            : self::blogPost($post);

        return self::canonicalUrl($url);
    }

    public static function blogPostCategorySlug(BlogPost $post): string
    {
        $slug = '';

        if ($post->relationLoaded('category')) {
            $slug = trim((string) $post->category?->slug);
        } elseif (filled($post->content_category_id)) {
            $slug = trim((string) $post->category()->value('slug'));
        }

        return $slug !== '' ? $slug : self::FALLBACK_BLOG_CATEGORY_SLUG;
    }

    public static function canonicalBaseUrl(): string
    {
        $baseUrl = trim((string) config('frontsite_seo.canonical_url', config('app.url', '')));

        if ($baseUrl === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $parts = parse_url($baseUrl);
        $scheme = Str::lower((string) ($parts['scheme'] ?? 'https'));
        $host = Str::lower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return '';
        }

        $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';

        return $scheme.'://'.$host.$port;
    }

    public static function canonicalHost(): string
    {
        return Str::lower((string) parse_url(self::canonicalBaseUrl(), PHP_URL_HOST));
    }

    /**
     * @return array<int, string>
     */
    public static function canonicalRedirectHosts(): array
    {
        $canonicalHost = self::canonicalHost();

        return collect((array) config('frontsite_seo.canonical_redirect_hosts', []))
            ->push($canonicalHost)
            ->filter()
            ->map(fn (mixed $host): string => Str::lower(trim((string) $host)))
            ->map(fn (string $host): string => preg_replace('#^https?://#i', '', $host) ?? $host)
            ->map(fn (string $host): string => trim(explode('/', $host, 2)[0]))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function canonicalPath(string $path): string
    {
        $parts = parse_url($path);
        $path = rawurldecode((string) ($parts['path'] ?? $path));
        $path = '/'.ltrim($path, '/');
        $path = preg_replace('#^/index\.php(?:/|$)#i', '/', $path) ?? $path;
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $path = $path !== '/' ? rtrim($path, '/') : '/';

        return $path !== '' ? $path : '/';
    }

    public static function canonicalUrl(?string $url, bool $preserveQuery = false): string
    {
        $baseUrl = self::canonicalBaseUrl();
        $value = trim((string) $url);

        if ($baseUrl === '') {
            return $value;
        }

        if ($value === '') {
            $value = request()?->getRequestUri() ?: '/';
        }

        $parts = parse_url($value) ?: [];
        $hasSchemeOrHost = filled($parts['scheme'] ?? null) || filled($parts['host'] ?? null);
        $pathSource = array_key_exists('path', $parts ?: [])
            ? (string) $parts['path']
            : ($hasSchemeOrHost || filled($parts['query'] ?? null) ? '/' : $value);
        $path = self::canonicalPath($pathSource);
        $query = $preserveQuery && filled($parts['query'] ?? null)
            ? '?'.trim((string) $parts['query'])
            : '';

        $canonicalPath = $path === '/' ? '' : $path;

        return rtrim($baseUrl, '/').$canonicalPath.$query;
    }

    public static function cleanInternalUrl(?string $url, bool $absolute = false): ?string
    {
        $value = trim((string) $url);

        if ($value === '') {
            return null;
        }

        foreach (['mailto:', 'tel:', '#'] as $prefix) {
            if (Str::startsWith($value, $prefix)) {
                return $value;
            }
        }

        $parts = parse_url($value) ?: [];
        $host = Str::lower((string) ($parts['host'] ?? ''));
        $hasSchemeOrHost = filled($parts['scheme'] ?? null) || $host !== '';
        $isInternalHost = $host !== '' && in_array($host, self::canonicalRedirectHosts(), true);

        if ($hasSchemeOrHost && ! $isInternalHost) {
            return $value;
        }

        $pathSource = array_key_exists('path', $parts ?: [])
            ? (string) $parts['path']
            : ($hasSchemeOrHost || filled($parts['query'] ?? null) ? '/' : $value);
        $path = self::canonicalPath($pathSource);
        $query = filled($parts['query'] ?? null) ? '?'.trim((string) $parts['query']) : '';
        $fragment = filled($parts['fragment'] ?? null) ? '#'.trim((string) $parts['fragment']) : '';
        $cleanPath = $path.$query.$fragment;

        return $absolute ? self::canonicalUrl($cleanPath, true) : $cleanPath;
    }

    public static function normalizeInternalHtml(string $html): string
    {
        $hosts = self::canonicalRedirectHosts();

        if ($hosts === []) {
            return $html;
        }

        $hostPattern = implode('|', array_map(fn (string $host): string => preg_quote($host, '#'), $hosts));
        $html = preg_replace_callback(
            '#https?://('.$hostPattern.')(?::\d+)?(/[^\s"\'<>]*)?#i',
            fn (array $matches): string => self::canonicalUrl((string) ($matches[2] ?? '/'), true),
            $html
        ) ?? $html;

        return preg_replace('#(?<=["\'])/index\.php(?:/|(?=["\']))#i', '/', $html) ?? $html;
    }
}