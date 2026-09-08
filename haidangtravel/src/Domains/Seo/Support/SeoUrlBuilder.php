<?php

namespace Src\Domains\Seo\Support;

use Src\Domains\Seo\Enums\SeoPageType;

class SeoUrlBuilder
{
    public function canonicalUrl(SeoPageType|string|null $pageType, string $slug): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $type = $pageType instanceof SeoPageType
            ? $pageType
            : SeoPageType::tryFrom((string) $pageType);

        $path = $type?->publicPath($slug) ?? '/seo-pages/'.$slug;

        if ($path === '/') {
            return $baseUrl !== '' ? $baseUrl : '/';
        }

        return $baseUrl !== '' ? $baseUrl.$path : $path;
    }
}
