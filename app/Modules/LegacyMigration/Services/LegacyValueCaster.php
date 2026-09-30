<?php

namespace App\Modules\LegacyMigration\Services;

use InvalidArgumentException;

class LegacyValueCaster
{
    public function integer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Giá trị không phải số nguyên hợp lệ.');
        }

        return (int) $value;
    }

    public function boolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! in_array($value, [0, 1, '0', '1', false, true], true)) {
            throw new InvalidArgumentException('Giá trị không phải boolean hợp lệ.');
        }

        return (bool) $value;
    }

    public function money(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException('Giá trị tiền không hợp lệ.');
        }

        return (int) round((float) $value);
    }

    public function date(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) $value));
        $errors = \DateTimeImmutable::getLastErrors();

        if (! $date || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) {
            throw new InvalidArgumentException('Ngày không đúng định dạng Y-m-d.');
        }

        return $date->format('Y-m-d');
    }
}
