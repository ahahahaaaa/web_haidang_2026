<?php

namespace Tests\Unit;

use App\Support\TourCardStyle;
use PHPUnit\Framework\TestCase;

class TourCardStyleTest extends TestCase
{
    public function test_default_and_legacy_booking_ctas_use_brand_orange_and_keep_other_variants(): void
    {
        $this->assertSame(TourCardStyle::CTA_ORANGE, TourCardStyle::normalizeCtaVariant(null));
        $this->assertStringContainsString('frontsite-tour-booking-cta', TourCardStyle::ctaClasses(null));
        $this->assertSame(TourCardStyle::CTA_ORANGE, TourCardStyle::normalizeCtaVariant(TourCardStyle::CTA_AMBER));
        $this->assertStringContainsString('frontsite-tour-booking-cta', TourCardStyle::ctaClasses(TourCardStyle::CTA_AMBER));
        $this->assertSame(TourCardStyle::CTA_BLUE, TourCardStyle::normalizeCtaVariant(TourCardStyle::CTA_BLUE));
    }

    public function test_card_cta_keeps_its_right_edge_flush_with_the_card_shell(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

        $this->assertIsString($css);
        $this->assertStringContainsString(
            'clip-path: polygon(12% 0, 100% 0, 100% 100%, 0 100%);',
            $css,
        );
        $this->assertStringNotContainsString(
            'clip-path: polygon(12% 0, 100% 0, 88% 100%, 0 100%);',
            $css,
        );
    }

    public function test_booking_cta_avoids_blue_pointer_focus_and_keeps_an_orange_keyboard_focus(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');
        $blueVariantClasses = TourCardStyle::ctaClasses(TourCardStyle::CTA_BLUE);

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '.frontsite-theme .frontsite-tour-card-cta-wrap:has(.frontsite-tour-card-cta:focus-visible)',
            $css,
        );
        $this->assertStringContainsString('outline: 3px solid rgb(255 106 0 / 0.38);', $css);
        $this->assertStringNotContainsString('outline: 3px solid var(--color-secondary);', $css);
        $this->assertStringContainsString('focus-visible:ring-primary/35', $blueVariantClasses);
        $this->assertStringNotContainsString('focus-visible:ring-secondary/35', $blueVariantClasses);
        $this->assertStringNotContainsString('border-secondary', $blueVariantClasses);
    }
}
