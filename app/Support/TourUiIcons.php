<?php

namespace App\Support;

use Illuminate\Support\Str;

class TourUiIcons
{
    public const DEPARTURE_DATE = 'fa-regular fa-calendar-days';

    public const DEPARTURE_LOCATION = 'fa-solid fa-location-dot';

    public const DURATION = 'fa-regular fa-clock';

    public const SLOTS = 'fa-solid fa-users';

    public const STANDARD = 'fa-solid fa-star';

    public static function transport(?string $label): string
    {
        $normalized = Str::lower(Str::ascii(trim((string) $label)));

        if (Str::contains($normalized, ['bay', 'plane', 'air'])) {
            return 'fa-solid fa-plane-departure';
        }

        if (Str::contains($normalized, ['xe', 'bus', 'car', 'coach'])) {
            return 'fa-solid fa-bus-simple';
        }

        if (Str::contains($normalized, ['tau', 'train'])) {
            return 'fa-solid fa-train';
        }

        return 'fa-solid fa-route';
    }
}
