<?php

namespace App\Support;

class VietnamPhoneNumber
{
    public static function normalize(mixed $value): ?string
    {
        $phone = trim((string) $value);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone) ?: $phone;

        if (str_starts_with($phone, '+84')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '84') && strlen($phone) >= 11) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone;
    }

    public static function isValid(mixed $value): bool
    {
        $phone = static::normalize($value);

        if (! is_string($phone)) {
            return false;
        }

        return preg_match('/^0(?:3|5|7|8|9)\d{8}$/', $phone) === 1
            || preg_match('/^02\d{8,9}$/', $phone) === 1;
    }
}
