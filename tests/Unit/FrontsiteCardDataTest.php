<?php

namespace Tests\Unit;

use App\Support\FrontsiteCardData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FrontsiteCardDataTest extends TestCase
{
    #[DataProvider('departurePlaceLabels')]
    public function test_departure_place_abbreviation_only_applies_to_place_names(string $place, string $expected): void
    {
        $this->assertSame($expected, FrontsiteCardData::abbreviateDeparturePlace($place));
    }

    public static function departurePlaceLabels(): array
    {
        return [
            'ho chi minh' => ['Hồ Chí Minh', 'HCM'],
            'ho chi minh with city prefix' => ['TP. Hồ Chí Minh', 'HCM'],
            'ha noi' => ['Hà Nội', 'HN'],
            'da nang' => ['Đà Nẵng', 'ĐN'],
            'another proper name' => ['Phú Quốc', 'PQ'],
            'already abbreviated' => ['TP.HCM', 'HCM'],
            'contact fallback' => ['Liên hệ', 'Liên hệ'],
            'imported itinerary sentence' => ['rời TP.HCM đi Ninh Chữ.', 'rời TP.HCM đi Ninh Chữ.'],
        ];
    }
}
