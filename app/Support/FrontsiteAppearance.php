<?php

namespace App\Support;

class FrontsiteAppearance
{
    public const STRUCTURED_DATA_KEY = 'frontsite_appearance';

    public static function prepare(mixed $config): array
    {
        return [
            'tour_detail' => [
                'show_hero' => (bool) data_get($config, 'tour_detail.show_hero', true),
            ],
        ];
    }

    public static function tourDetailHeroIsVisible(mixed $structuredData): bool
    {
        return (bool) data_get(
            self::prepare(data_get($structuredData, self::STRUCTURED_DATA_KEY)),
            'tour_detail.show_hero',
            true,
        );
    }
}
