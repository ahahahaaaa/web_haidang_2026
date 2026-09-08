<?php

namespace App\Support;

class SliderLocations
{
    public const HOME_POPUP = 'home-popup';

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function options(): array
    {
        $options = [
            self::HOME_POPUP => [
                'label' => 'Popup Trang chủ',
                'description' => 'Hiển thị popup trên trang chủ sau khi trang đã tải xong.',
            ],
        ];

        foreach (LandingPageBlocks::systemPages() as $pageKey => $label) {
            foreach (['hero' => 'Hero', 'gallery' => 'Gallery'] as $slot => $slotLabel) {
                $location = LandingPageVisuals::sliderLocation((string) $pageKey, $slot);

                $options[$location] = [
                    'label' => $label.' - '.$slotLabel,
                    'description' => 'Slider mặc định cho '.$slotLabel.' của '.$label.'.',
                ];
            }
        }

        return $options;
    }

    public static function label(?string $location): ?string
    {
        $location = trim((string) $location);

        if ($location === '') {
            return null;
        }

        return self::options()[$location]['label'] ?? $location;
    }
}
