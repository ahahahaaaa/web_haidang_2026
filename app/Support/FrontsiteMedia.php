<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\Tour;

class FrontsiteMedia
{
    public const SIZE_FULL = 'full';

    public const SIZE_MEDIUM = 'medium';

    public const SIZE_SMALL = 'small';

    /**
     * @var array<string, ?string>
     */
    protected static array $taxonomyTourFallbackCache = [];

    /**
     * @var array<string, bool>
     */
    protected static array $storageUrlExistsCache = [];

    /**
     * @var array<string, ?string>
     */
    protected static array $validatedUrlCache = [];

    /**
     * @param  array<int, string>|string|null  $directUrlAttributes
     */
    public static function modelUrl(
        mixed $model,
        string $collection,
        string $size = self::SIZE_FULL,
        array|string|null $directUrlAttributes = 'cover_image_url',
    ): ?string {
        if (is_object($model) && method_exists($model, 'getFirstMedia')) {
            $mediaUrl = self::mediaUrl($model->getFirstMedia($collection), $size);

            if ($mediaUrl !== null) {
                return $mediaUrl;
            }
        }

        return self::directUrl($model, $directUrlAttributes);
    }

    public static function mediaUrl(?Media $media, string $size = self::SIZE_FULL): ?string
    {
        if (! $media) {
            return null;
        }

        foreach (self::conversionFallbacks($size) as $conversion) {
            if (
                $conversion !== self::SIZE_FULL
                && method_exists($media, 'hasGeneratedConversion')
                && ! $media->hasGeneratedConversion($conversion)
            ) {
                continue;
            }

            try {
                $url = trim((string) $media->getUrl($conversion));
            } catch (\Throwable) {
                continue;
            }

            $resolvedUrl = self::validatedUrl(
                $url,
                (string) ($media->disk ?? 'public'),
            );

            if ($resolvedUrl !== null) {
                return $resolvedUrl;
            }
        }

        try {
            return self::validatedUrl(
                trim((string) $media->getUrl()),
                (string) ($media->disk ?? 'public'),
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve taxonomy/avatar-like media with a tour-cover fallback when the taxonomy
     * record has not been fully migrated into the shared avatar collection yet.
     *
     * @param  array<int, string>|string|null  $directUrlAttributes
     */
    public static function taxonomyAvatarUrl(
        mixed $model,
        string $size = self::SIZE_FULL,
        array|string|null $directUrlAttributes = 'cover_image_url',
        bool $allowTourFallback = true,
    ): ?string {
        $mediaUrl = self::modelUrl($model, 'avatar', $size, null);

        if ($mediaUrl !== null) {
            return $mediaUrl;
        }

        if ($allowTourFallback) {
            $tourCoverUrl = self::relatedTourCoverUrl($model, $size);

            if ($tourCoverUrl !== null) {
                return $tourCoverUrl;
            }
        }

        return self::directUrl($model, $directUrlAttributes);
    }

    /**
     * @param  array<int, string>|string|null  $directUrlAttributes
     * @return array{small: ?string, medium: ?string, full: ?string}
     */
    public static function responsiveUrls(
        mixed $model,
        string $collection,
        array|string|null $directUrlAttributes = 'cover_image_url',
    ): array {
        $full = self::modelUrl($model, $collection, self::SIZE_FULL, $directUrlAttributes);
        $medium = self::modelUrl($model, $collection, self::SIZE_MEDIUM, $directUrlAttributes) ?: $full;
        $small = self::modelUrl($model, $collection, self::SIZE_SMALL, $directUrlAttributes) ?: $medium;

        return [
            self::SIZE_SMALL => $small,
            self::SIZE_MEDIUM => $medium,
            self::SIZE_FULL => $full ?: $medium ?: $small,
        ];
    }

    public static function firstRichTextImageUrl(?string $content): ?string
    {
        $html = RichText::sanitize($content);

        if ($html === '' || ! str_contains($html, '<img')) {
            return null;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $image = $document->getElementsByTagName('img')->item(0);

        if (! $image instanceof \DOMElement) {
            return null;
        }

        $src = trim((string) $image->getAttribute('src'));

        if ($src === '' || Str::startsWith($src, '#')) {
            return null;
        }

        if (Str::startsWith($src, '//')) {
            $src = 'https:'.$src;
        } elseif (Str::startsWith($src, '/')) {
            $src = url($src);
        }

        return self::validatedUrl($src);
    }

    public static function validatedUrl(?string $url, ?string $disk = 'public'): ?string
    {
        $resolvedUrl = trim((string) $url);

        if ($resolvedUrl === '') {
            return null;
        }

        $cacheKey = ($disk ?: 'public').'|'.$resolvedUrl;

        if (array_key_exists($cacheKey, self::$validatedUrlCache)) {
            return self::$validatedUrlCache[$cacheKey];
        }

        $relativeStoragePath = self::storageRelativePath($resolvedUrl);

        if ($relativeStoragePath === null) {
            return self::$validatedUrlCache[$cacheKey] = FrontsiteUrls::cleanInternalUrl($resolvedUrl, true) ?: $resolvedUrl;
        }

        $diskName = $disk ?: 'public';
        $existsCacheKey = $diskName.'|'.$relativeStoragePath;

        if (! array_key_exists($existsCacheKey, self::$storageUrlExistsCache)) {
            try {
                self::$storageUrlExistsCache[$existsCacheKey] = Storage::disk($diskName)->exists($relativeStoragePath);
            } catch (\Throwable) {
                self::$storageUrlExistsCache[$existsCacheKey] = false;
            }
        }

        return self::$validatedUrlCache[$cacheKey] = self::$storageUrlExistsCache[$existsCacheKey]
            ? self::normalizedStorageUrl($resolvedUrl, $relativeStoragePath)
            : null;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function bounds(string $size): array
    {
        return match ($size) {
            self::SIZE_SMALL => [500, 500],
            self::SIZE_MEDIUM => [1000, 1000],
            default => [2400, 2400],
        };
    }

    /**
     * @param  array<int, string>|string|null  $attributes
     */
    protected static function directUrl(mixed $model, array|string|null $attributes): ?string
    {
        foreach (self::normalizeAttributes($attributes) as $attribute) {
            $value = self::validatedUrl(
                trim((string) data_get($model, $attribute)),
                'public',
            );

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected static function conversionFallbacks(string $size): array
    {
        return match ($size) {
            self::SIZE_SMALL => [self::SIZE_SMALL, self::SIZE_MEDIUM, self::SIZE_FULL],
            self::SIZE_MEDIUM => [self::SIZE_MEDIUM, self::SIZE_FULL],
            self::SIZE_FULL => [self::SIZE_FULL],
            default => [],
        };
    }

    /**
     * @param  array<int, string>|string|null  $attributes
     * @return array<int, string>
     */
    protected static function normalizeAttributes(array|string|null $attributes): array
    {
        $resolved = is_array($attributes) ? $attributes : [$attributes];

        return array_values(array_filter(
            array_map(fn ($attribute) => trim((string) $attribute), $resolved),
            fn (string $attribute) => $attribute !== '',
        ));
    }

    protected static function storageRelativePath(string $url): ?string
    {
        $parts = parse_url($url);
        $path = trim((string) ($parts['path'] ?? $url));
        $normalizedPath = '/'.ltrim($path, '/');

        if (! Str::startsWith($normalizedPath, '/storage/')) {
            return null;
        }

        $host = Str::lower(trim((string) ($parts['host'] ?? '')));
        $knownHosts = collect([
            parse_url(config('app.url'), PHP_URL_HOST),
            parse_url(url('/'), PHP_URL_HOST),
            request()?->getHost(),
            ...FrontsiteUrls::canonicalRedirectHosts(),
        ])
            ->filter()
            ->map(fn ($value) => Str::lower((string) $value))
            ->unique()
            ->values();

        if ($host !== '' && ! $knownHosts->contains($host)) {
            return null;
        }

        $relativePath = rawurldecode(ltrim(Str::after($normalizedPath, '/storage/'), '/'));

        return $relativePath !== '' ? $relativePath : null;
    }

    protected static function normalizedStorageUrl(string $originalUrl, string $relativePath): string
    {
        $parts = parse_url($originalUrl);
        $normalizedPath = '/storage/'.ltrim($relativePath, '/');
        $normalizedUrl = FrontsiteUrls::canonicalUrl($normalizedPath);
        $query = filled($parts['query'] ?? null) ? '?'.trim((string) $parts['query']) : '';
        $fragment = filled($parts['fragment'] ?? null) ? '#'.trim((string) $parts['fragment']) : '';

        return $normalizedUrl.$query.$fragment;
    }

    protected static function relatedTourCoverUrl(mixed $model, string $size): ?string
    {
        if (! is_object($model) || ! method_exists($model, 'getKey')) {
            return null;
        }

        $modelKey = $model->getKey();

        if (! filled($modelKey)) {
            return null;
        }

        $cacheKey = get_class($model).':'.$modelKey.':'.$size;

        if (array_key_exists($cacheKey, self::$taxonomyTourFallbackCache)) {
            return self::$taxonomyTourFallbackCache[$cacheKey];
        }

        $resolvedUrl = self::tourCoverFromLoadedRelations($model, $size);

        if ($resolvedUrl === null) {
            $resolvedUrl = self::tourCoverFromQuery($model, $size);
        }

        return self::$taxonomyTourFallbackCache[$cacheKey] = $resolvedUrl;
    }

    protected static function tourCoverFromLoadedRelations(mixed $model, string $size): ?string
    {
        foreach (['primaryTours', 'tours'] as $relation) {
            $loadedTours = self::loadedTourRelation($model, $relation);

            if ($loadedTours->isEmpty()) {
                continue;
            }

            $resolvedUrl = self::tourCoverFromCollection($loadedTours, $size);

            if ($resolvedUrl !== null) {
                return $resolvedUrl;
            }
        }

        return null;
    }

    protected static function tourCoverFromQuery(mixed $model, string $size): ?string
    {
        foreach (['primaryTours', 'tours'] as $relation) {
            if (! method_exists($model, $relation)) {
                continue;
            }

            $tourRelation = $model->{$relation}();

            if (! method_exists($tourRelation, 'with')) {
                continue;
            }

            $tour = $tourRelation
                ->with('media')
                ->where('status', 'published')
                ->where(function ($publishedQuery): void {
                    $publishedQuery
                        ->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->latest('updated_at')
                ->first();

            if ($tour instanceof Tour) {
                $resolvedUrl = self::modelUrl($tour, 'cover', $size, 'cover_image_url');

                if ($resolvedUrl !== null) {
                    return $resolvedUrl;
                }
            }
        }

        return null;
    }

    protected static function tourCoverFromCollection(Collection $tours, string $size): ?string
    {
        foreach ($tours as $tour) {
            if (! $tour instanceof Tour) {
                continue;
            }

            $resolvedUrl = self::modelUrl($tour, 'cover', $size, 'cover_image_url');

            if ($resolvedUrl !== null) {
                return $resolvedUrl;
            }
        }

        return null;
    }

    protected static function loadedTourRelation(mixed $model, string $relation): Collection
    {
        if (! is_object($model) || ! method_exists($model, 'relationLoaded') || ! $model->relationLoaded($relation)) {
            return collect();
        }

        $loaded = $model->getRelation($relation);

        if ($loaded instanceof EloquentCollection) {
            return $loaded
                ->filter(fn ($item) => $item instanceof Tour)
                ->values();
        }

        return collect();
    }
}
