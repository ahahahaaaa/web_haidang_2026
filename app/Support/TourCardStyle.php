<?php

namespace App\Support;

final class TourCardStyle
{
    public const CTA_AMBER = 'amber';

    public const CTA_BLUE = 'blue';

    public const CTA_GREEN = 'green';

    public const CTA_ORANGE = 'orange';

    public const CTA_RED = 'red';

    public const DEFAULT_CTA_VARIANT = self::CTA_ORANGE;

    /**
     * @return array<string, string>
     */
    public static function ctaVariants(): array
    {
        return [
            self::CTA_ORANGE => 'Cam thương hiệu',
            self::CTA_BLUE => 'Xanh thương hiệu',
            self::CTA_RED => 'Đỏ khuyến mãi',
            self::CTA_GREEN => 'Xanh lá',
        ];
    }

    public static function normalizeCtaVariant(mixed $variant): string
    {
        $variant = trim((string) $variant);

        if ($variant === self::CTA_AMBER) {
            return self::CTA_ORANGE;
        }

        return array_key_exists($variant, self::ctaVariants())
            ? $variant
            : self::DEFAULT_CTA_VARIANT;
    }

    public static function ctaClasses(mixed $variant): string
    {
        return match (self::normalizeCtaVariant($variant)) {
            self::CTA_BLUE => 'border-transparent bg-secondary text-white hover:border-transparent hover:bg-[#003B7A] focus-visible:ring-primary/35',
            self::CTA_RED => 'border-[#E31C25] bg-[#E31C25] text-white hover:brightness-110 focus-visible:ring-red-300/50',
            self::CTA_GREEN => 'border-emerald-600 bg-emerald-600 text-white hover:border-emerald-700 hover:bg-emerald-700 focus-visible:ring-emerald-300/50',
            default => 'frontsite-tour-booking-cta focus-visible:ring-primary/35',
        };
    }
}
