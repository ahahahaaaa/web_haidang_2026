<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Src\Domains\Cms\Models\SiteSetting;

class FooterSocialLinks
{
    public const STRUCTURED_DATA_KEY = 'footer_social_links';

    /**
     * @return array<string, array{label: string, icon?: string, image?: string}>
     */
    public static function platforms(): array
    {
        return [
            'facebook' => [
                'label' => 'Facebook',
                'icon' => 'fa-brands fa-facebook-f',
            ],
            'messenger' => [
                'label' => 'Messenger',
                'icon' => 'fa-brands fa-facebook-messenger',
            ],
            'youtube' => [
                'label' => 'YouTube',
                'icon' => 'fa-brands fa-youtube',
            ],
            'tiktok' => [
                'label' => 'TikTok',
                'icon' => 'fa-brands fa-tiktok',
            ],
            'instagram' => [
                'label' => 'Instagram',
                'icon' => 'fa-brands fa-instagram',
            ],
            'linkedin' => [
                'label' => 'LinkedIn',
                'icon' => 'fa-brands fa-linkedin-in',
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'icon' => 'fa-brands fa-whatsapp',
            ],
            'telegram' => [
                'label' => 'Telegram',
                'icon' => 'fa-brands fa-telegram',
            ],
            'zalo' => [
                'label' => 'Zalo',
                'image' => 'images/zalo-footer-logo.svg',
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function platformOptions(): array
    {
        return collect(self::platforms())
            ->map(fn (array $platform, string $key): array => [
                'value' => $key,
                'label' => $platform['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function platformKeys(): array
    {
        return array_keys(self::platforms());
    }

    public static function blankItem(string $platform = 'facebook'): array
    {
        return [
            'platform' => array_key_exists($platform, self::platforms()) ? $platform : 'facebook',
            'label' => '',
            'description' => '',
            'url' => '',
            'is_active' => true,
        ];
    }

    public static function storedConfig(mixed $value): array
    {
        $items = collect(self::normalizeItems($value, includeBlank: false, requireUrl: true, requireActive: false))
            ->map(fn (array $item): array => Arr::only($item, [
                'platform',
                'label',
                'description',
                'url',
                'is_active',
            ]))
            ->values()
            ->all();

        return [
            'is_configured' => true,
            'items' => $items,
        ];
    }

    public static function formItems(mixed $storedConfig, SiteSetting $settings): array
    {
        if (self::isConfigured($storedConfig)) {
            return self::normalizeItems(
                data_get($storedConfig, 'items', []),
                includeBlank: false,
                requireUrl: false,
                requireActive: false,
            );
        }

        $fallbackItems = self::fallbackItems($settings);

        return $fallbackItems !== [] ? $fallbackItems : [self::blankItem()];
    }

    public static function footerItems(SiteSetting $settings): array
    {
        $storedConfig = data_get($settings->structured_data, self::STRUCTURED_DATA_KEY);

        if (self::isConfigured($storedConfig)) {
            return self::normalizeItems(data_get($storedConfig, 'items', []), includeBlank: false, requireUrl: true);
        }

        return self::fallbackItems($settings);
    }

    /**
     * @return array<int, string>
     */
    public static function sameAsUrls(SiteSetting $settings): array
    {
        return collect(self::footerItems($settings))
            ->pluck('url')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected static function isConfigured(mixed $storedConfig): bool
    {
        return is_array($storedConfig)
            && filter_var(data_get($storedConfig, 'is_configured'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === true;
    }

    protected static function fallbackItems(SiteSetting $settings): array
    {
        return self::normalizeItems([
            [
                'platform' => 'zalo',
                'label' => 'Zalo hỗ trợ',
                'url' => $settings->zalo_url,
            ],
            [
                'platform' => 'facebook',
                'label' => 'Fanpage chính thức',
                'url' => $settings->facebook_url,
            ],
            [
                'platform' => 'youtube',
                'label' => 'YouTube Channel',
                'url' => $settings->youtube_url,
            ],
            [
                'platform' => 'tiktok',
                'label' => 'TikTok Channel',
                'url' => $settings->tiktok_url,
            ],
            [
                'platform' => 'instagram',
                'label' => 'Instagram',
                'url' => $settings->instagram_url,
            ],
            [
                'platform' => 'messenger',
                'label' => 'Messenger',
                'url' => $settings->messenger_url,
            ],
            [
                'platform' => 'linkedin',
                'label' => 'LinkedIn',
                'url' => $settings->linkedin_url,
            ],
        ], includeBlank: false, requireUrl: true);
    }

    protected static function normalizeItems(
        mixed $items,
        bool $includeBlank = false,
        bool $requireUrl = true,
        bool $requireActive = true,
    ): array {
        $platforms = self::platforms();

        $normalized = collect(is_array($items) ? $items : [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item) use ($platforms): array {
                $platformKey = trim((string) data_get($item, 'platform', 'facebook'));
                $platformKey = array_key_exists($platformKey, $platforms) ? $platformKey : 'facebook';
                $platform = $platforms[$platformKey];
                $defaultLabel = $platform['label'];
                $label = trim((string) data_get($item, 'label', ''));

                return [
                    'platform' => $platformKey,
                    'label' => $label !== '' ? $label : $defaultLabel,
                    'description' => trim((string) data_get($item, 'description', '')),
                    'url' => trim((string) data_get($item, 'url', '')),
                    'is_active' => filter_var(data_get($item, 'is_active', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
                    'icon' => Arr::get($platform, 'icon'),
                    'image' => Arr::get($platform, 'image'),
                    'platform_label' => $defaultLabel,
                ];
            })
            ->filter(fn (array $item): bool => ! $requireActive || $item['is_active'])
            ->filter(fn (array $item): bool => ! $requireUrl || $item['url'] !== '')
            ->values()
            ->all();

        return $normalized !== [] || ! $includeBlank ? $normalized : [self::blankItem()];
    }
}
