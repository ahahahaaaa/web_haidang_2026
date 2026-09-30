<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class TourDepartureSchedule
{
    public static function firstCurrentLabel(array $schedules): ?string
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        foreach ($schedules as $schedule) {
            $label = trim((string) $schedule);

            if ($label === '') {
                continue;
            }

            if (! preg_match('/(?<!\d)(\d{4}-\d{1,2}-\d{1,2}(?:\s+\d{2}:\d{2}:\d{2})?|\d{1,2}[\/-]\d{1,2}[\/-]\d{4})(?!\d)/u', $label, $matches)) {
                // Free-form notes are not dated departures and remain available as a fallback.
                return $label;
            }

            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'] as $format) {
                $date = \DateTimeImmutable::createFromFormat('!'.$format, $matches[1]);
                $errors = \DateTimeImmutable::getLastErrors();

                if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                    continue;
                }

                if ($date->format('Y-m-d') >= $today) {
                    return $label;
                }

                continue 2;
            }

            // Keep labels whose date-shaped text cannot be parsed reliably.
            return $label;
        }

        return null;
    }
}
