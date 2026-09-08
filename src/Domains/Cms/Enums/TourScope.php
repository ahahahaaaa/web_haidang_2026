<?php

namespace Src\Domains\Cms\Enums;

enum TourScope: string
{
    case Domestic = 'domestic';
    case International = 'international';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Domestic => 'Tour trong nước',
            self::International => 'Tour nước ngoài',
            self::Group => 'Tour đoàn',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Domestic => 'tours.domestic',
            self::International => 'tours.international',
            self::Group => 'tours.group',
        };
    }

    public static function tryFromRoute(string $routeName): ?self
    {
        return match ($routeName) {
            'tours.domestic' => self::Domestic,
            'tours.international' => self::International,
            'tours.group' => self::Group,
            default => null,
        };
    }
}
