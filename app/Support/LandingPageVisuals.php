<?php

namespace App\Support;

use Illuminate\Support\Str;

class LandingPageVisuals
{
    public const SOURCE_MEDIA = 'media';

    public const SOURCE_NONE = 'none';

    public const SOURCE_SLIDER = 'slider';

    public static function defaultConfig(): array
    {
        return [
            'gallery' => [
                'description' => '',
                'enabled' => false,
                'eyebrow' => '',
                'items' => [
                    self::galleryItem(),
                ],
                'slider_id' => null,
                'source' => self::SOURCE_NONE,
                'title' => '',
            ],
            'hero' => [
                'enabled' => true,
                'media_alt' => '',
                'slider_id' => null,
                'source' => self::SOURCE_NONE,
            ],
        ];
    }

    public static function galleryCollection(string $uuid): string
    {
        return 'landing-gallery-'.$uuid;
    }

    public static function galleryItem(): array
    {
        return [
            'image_alt' => '',
            'subtitle' => '',
            'title' => '',
            'url' => '',
            'uuid' => (string) Str::uuid(),
        ];
    }

    public static function heroCollection(): string
    {
        return 'landing-hero';
    }

    public static function prepare(?array $config): array
    {
        $defaults = self::defaultConfig();

        return [
            'gallery' => [
                'description' => self::stringValue(data_get($config, 'gallery.description', data_get($defaults, 'gallery.description'))),
                'enabled' => (bool) data_get($config, 'gallery.enabled', data_get($defaults, 'gallery.enabled')),
                'eyebrow' => self::stringValue(data_get($config, 'gallery.eyebrow', data_get($defaults, 'gallery.eyebrow'))),
                'items' => collect(is_array(data_get($config, 'gallery.items')) ? array_values(data_get($config, 'gallery.items')) : data_get($defaults, 'gallery.items'))
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                        'subtitle' => self::stringValue(data_get($item, 'subtitle')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'url' => self::stringValue(data_get($item, 'url')),
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                    ])
                    ->values()
                    ->all(),
                'slider_id' => self::integerValue(data_get($config, 'gallery.slider_id')),
                'source' => self::sourceValue(data_get($config, 'gallery.source', data_get($defaults, 'gallery.source'))),
                'title' => self::stringValue(data_get($config, 'gallery.title', data_get($defaults, 'gallery.title'))),
            ],
            'hero' => [
                'enabled' => (bool) data_get($config, 'hero.enabled', data_get($defaults, 'hero.enabled')),
                'media_alt' => self::stringValue(data_get($config, 'hero.media_alt', data_get($defaults, 'hero.media_alt'))),
                'slider_id' => self::integerValue(data_get($config, 'hero.slider_id')),
                'source' => self::sourceValue(data_get($config, 'hero.source', data_get($defaults, 'hero.source'))),
            ],
        ];
    }

    public static function sliderLocation(string $pageKey, string $slot): string
    {
        return Str::slug($pageKey.'-'.$slot);
    }

    protected static function integerValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $resolved = (int) $value;

        return $resolved > 0 ? $resolved : null;
    }

    protected static function sourceValue(mixed $value): string
    {
        return match (trim((string) $value)) {
            self::SOURCE_MEDIA => self::SOURCE_MEDIA,
            self::SOURCE_SLIDER => self::SOURCE_SLIDER,
            default => self::SOURCE_NONE,
        };
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
