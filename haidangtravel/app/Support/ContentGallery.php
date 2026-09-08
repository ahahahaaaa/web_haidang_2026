<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

class ContentGallery
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_MP4 = 'mp4';

    public const TYPE_YOUTUBE = 'youtube';

    public static function defaultItem(string $type = self::TYPE_IMAGE): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => self::normalizeType($type),
            'title' => '',
            'description' => '',
            'image_alt' => '',
            'image_url' => '',
            'video_url' => '',
        ];
    }

    public static function normalize(?array $items): array
    {
        return self::prepare($items);
    }

    public static function prepare(?array $items): array
    {
        return collect(is_array($items) ? array_values($items) : [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'uuid' => self::uuidValue(data_get($item, 'uuid')),
                'type' => self::normalizeType(data_get($item, 'type')),
                'title' => self::stringValue(data_get($item, 'title')),
                'description' => self::stringValue(data_get($item, 'description')),
                'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                'image_url' => self::stringValue(data_get($item, 'image_url')),
                'video_url' => self::stringValue(data_get($item, 'video_url')),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  HasMedia  $model
     * @param  callable(string): string  $collectionResolver
     * @return array<int, array<string, mixed>>
     */
    public static function resolveForFrontsite(
        HasMedia $model,
        ?array $items,
        callable $collectionResolver,
        ?string $fallbackImageUrl = null,
        ?string $fallbackAlt = null,
        ?string $fallbackThumbnailUrl = null,
    ): array {
        return collect(self::prepare($items))
            ->map(function (array $item) use ($collectionResolver, $fallbackAlt, $fallbackImageUrl, $fallbackThumbnailUrl, $model): array {
                $type = $item['type'];
                $fullImageUrl = null;
                $imageUrl = null;
                $smallImageUrl = null;
                $thumbnailFallbackUrl = $fallbackThumbnailUrl ?: $fallbackImageUrl;
                $thumbnailUrl = $thumbnailFallbackUrl;
                $embedUrl = null;
                $videoUrl = null;

                if ($type === self::TYPE_IMAGE) {
                    $fullImageUrl = self::stringValue(
                        FrontsiteMedia::modelUrl(
                            $model,
                            $collectionResolver($item['uuid']),
                            FrontsiteMedia::SIZE_FULL,
                            null,
                        )
                    );
                    $imageUrl = self::stringValue(
                        FrontsiteMedia::modelUrl(
                            $model,
                            $collectionResolver($item['uuid']),
                            FrontsiteMedia::SIZE_MEDIUM,
                            null,
                        )
                    );
                    $smallImageUrl = self::stringValue(
                        FrontsiteMedia::modelUrl(
                            $model,
                            $collectionResolver($item['uuid']),
                            FrontsiteMedia::SIZE_SMALL,
                            null,
                        )
                    );

                    if ($fullImageUrl === '') {
                        $fullImageUrl = self::stringValue(
                            FrontsiteMedia::validatedUrl((string) $item['image_url'])
                        );
                    }

                    if ($imageUrl === '') {
                        $imageUrl = $fullImageUrl;
                    }

                    if ($smallImageUrl === '') {
                        $smallImageUrl = $imageUrl !== '' ? $imageUrl : $fullImageUrl;
                    }

                    $thumbnailUrl = $smallImageUrl !== '' ? $smallImageUrl : $thumbnailFallbackUrl;
                }

                if ($type === self::TYPE_YOUTUBE) {
                    $youtubeId = self::youtubeId($item['video_url']);

                    if ($youtubeId) {
                        $embedUrl = 'https://www.youtube.com/embed/'.$youtubeId;
                        $thumbnailUrl = 'https://i.ytimg.com/vi/'.$youtubeId.'/hqdefault.jpg';
                    }
                }

                if ($type === self::TYPE_MP4) {
                    $videoUrl = $item['video_url'];
                }

                return [
                    ...$item,
                    'embed_url' => $embedUrl,
                    'full_image_url' => $fullImageUrl ?? null,
                    'image_url' => $imageUrl,
                    'small_image_url' => $smallImageUrl ?? null,
                    'thumbnail_url' => $thumbnailUrl,
                    'video_url' => $videoUrl,
                    'lightbox_kind' => $type,
                    'resolved_title' => $item['title'] !== '' ? $item['title'] : self::defaultTitle($type),
                    'resolved_description' => $item['description'] !== '' ? $item['description'] : self::defaultDescription($type),
                    'resolved_alt' => $item['image_alt'] !== '' ? $item['image_alt'] : ($fallbackAlt ?: self::defaultTitle($type)),
                ];
            })
            ->filter(fn (array $item) => match ($item['type']) {
                self::TYPE_IMAGE => filled($item['image_url']),
                self::TYPE_YOUTUBE => filled($item['embed_url']),
                self::TYPE_MP4 => filled($item['video_url']),
                default => false,
            })
            ->values()
            ->all();
    }

    public static function serviceCollection(string $uuid): string
    {
        return 'service-gallery-'.$uuid;
    }

    public static function projectCollection(string $uuid): string
    {
        return 'project-gallery-'.$uuid;
    }

    public static function taxonomyCollection(string $taxonomy, string $uuid): string
    {
        return trim($taxonomy) !== ''
            ? trim($taxonomy).'-gallery-'.$uuid
            : 'taxonomy-gallery-'.$uuid;
    }

    public static function tourCollection(string $uuid): string
    {
        return 'tour-gallery-'.$uuid;
    }

    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($host === 'youtu.be' && $path !== '') {
            return self::sanitizeYoutubeId(Str::before($path, '/'));
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str((string) ($parts['query'] ?? ''), $query);

            if (! empty($query['v'])) {
                return self::sanitizeYoutubeId((string) $query['v']);
            }

            foreach (['embed/', 'shorts/', 'live/'] as $segment) {
                if (str_starts_with($path, $segment)) {
                    return self::sanitizeYoutubeId(Str::after($path, $segment));
                }
            }
        }

        return null;
    }

    protected static function defaultDescription(string $type): string
    {
        return match ($type) {
            self::TYPE_YOUTUBE => 'Video YouTube',
            self::TYPE_MP4 => 'Video MP4',
            default => 'Hình ảnh',
        };
    }

    protected static function defaultTitle(string $type): string
    {
        return match ($type) {
            self::TYPE_YOUTUBE => 'Video YouTube',
            self::TYPE_MP4 => 'Video MP4',
            default => 'Hình ảnh',
        };
    }

    protected static function normalizeType(mixed $type): string
    {
        return match (trim((string) $type)) {
            self::TYPE_YOUTUBE => self::TYPE_YOUTUBE,
            self::TYPE_MP4 => self::TYPE_MP4,
            default => self::TYPE_IMAGE,
        };
    }

    protected static function sanitizeYoutubeId(string $value): ?string
    {
        $value = trim(Str::before($value, '?'));

        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $value) ? $value : null;
    }

    protected static function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    protected static function uuidValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : (string) Str::uuid();
    }
}
