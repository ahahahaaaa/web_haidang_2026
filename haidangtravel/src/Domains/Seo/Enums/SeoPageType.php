<?php

namespace Src\Domains\Seo\Enums;

enum SeoPageType: string
{
    case Homepage = 'homepage';
    case ServiceCategory = 'category_service';
    case Service = 'service';
    case Blog = 'blog';
    case Contact = 'contact';
    case TourCategory = 'tour_category';
    case Destination = 'destination';
    case Region = 'region';

    // Legacy types kept to avoid enum-cast failures on older records.
    case Country = 'country';
    case Project = 'project';
    case LocationLanding = 'location_landing';

    public function label(): string
    {
        return match ($this) {
            self::Homepage => 'Trang chủ',
            self::ServiceCategory => 'Danh mục dịch vụ',
            self::Service => 'Dịch vụ',
            self::Blog => 'Blog',
            self::Contact => 'Liên hệ',
            self::TourCategory => 'Danh mục tour',
            self::Destination => 'Điểm đến',
            self::Region => 'Vùng miền',
            self::Country => 'Quốc gia (legacy)',
            self::Project => 'Dự án (legacy)',
            self::LocationLanding => 'Landing khu vực (legacy)',
        };
    }

    public function publicPath(string $slug): string
    {
        return match ($this) {
            self::Homepage => '/',
            self::ServiceCategory => '/dich-vu/danh-muc/'.$slug,
            self::Service => '/dich-vu/'.$slug,
            self::TourCategory => '/danh-muc-tour/'.$slug,
            self::Destination => '/diem-den/'.$slug,
            self::Region => '/vung-mien/'.$slug,
            default => '/seo-pages/'.$slug,
        };
    }

    public function sectionLabel(): string
    {
        return match ($this) {
            self::TourCategory => 'Danh mục tour',
            self::Destination => 'Điểm đến',
            self::Region => 'Vùng miền',
            self::Country => 'Quốc gia (legacy)',
            self::ServiceCategory => 'Danh mục dịch vụ',
            self::Service => 'Dịch vụ',
            self::Blog => 'Blog',
            self::Contact => 'Liên hệ',
            self::Homepage => 'Trang chủ',
            self::Project => 'Dự án',
            self::LocationLanding => 'Landing khu vực',
        };
    }

    public function isTravelHub(): bool
    {
        return in_array($this, [
            self::TourCategory,
            self::Destination,
            self::Region,
            self::ServiceCategory,
        ], true);
    }

    public function usesLocationContext(): bool
    {
        return in_array($this, [
            self::Destination,
            self::Region,
            self::LocationLanding,
        ], true);
    }

    public static function adminOptions(): array
    {
        return [
            self::TourCategory->value => self::TourCategory->label(),
            self::Destination->value => self::Destination->label(),
            self::Region->value => self::Region->label(),
            self::ServiceCategory->value => self::ServiceCategory->label(),
            self::Service->value => self::Service->label(),
            self::Blog->value => self::Blog->label(),
            self::Contact->value => self::Contact->label(),
        ];
    }

    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
