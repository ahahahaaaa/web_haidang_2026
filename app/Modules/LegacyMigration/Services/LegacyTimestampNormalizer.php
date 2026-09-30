<?php

namespace App\Modules\LegacyMigration\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

class LegacyTimestampNormalizer
{
    public function nullable(mixed $value): ?CarbonImmutable
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $raw = trim((string) $value);

        if (str_ends_with($raw, 'Z')) {
            $raw = substr($raw, 0, -1).'+00:00';
        }

        try {
            $sourceTimezone = new DateTimeZone((string) config('legacy_migration.source_timezone'));

            foreach ([
                '!Y-m-d H:i:s.uP',
                '!Y-m-d H:i:sP',
                '!Y-m-d\TH:i:s.uP',
                '!Y-m-d\TH:i:sP',
                '!Y-m-d H:i:s.u',
                '!Y-m-d H:i:s',
                '!Y-m-d\TH:i:s.u',
                '!Y-m-d\TH:i:s',
            ] as $format) {
                $parsed = DateTimeImmutable::createFromFormat($format, $raw, $sourceTimezone);
                $errors = DateTimeImmutable::getLastErrors();

                if ($parsed && (! is_array($errors) || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                    return CarbonImmutable::instance($parsed)->setTimezone((string) config('app.timezone'));
                }
            }
        } catch (Throwable) {
            // Converted to a stable validation error below.
        }

        throw new InvalidArgumentException('Timestamp legacy không hợp lệ.');
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function requiredPair(array $attributes): array
    {
        $createdAt = $this->nullable($attributes['created_at'] ?? null);

        if (! $createdAt instanceof CarbonInterface) {
            throw new InvalidArgumentException('Object nguồn thiếu created_at; không thể đảm bảo đúng ngày tạo.');
        }

        $updatedAt = $this->nullable($attributes['updated_at'] ?? null) ?? $createdAt;

        if ($updatedAt->lessThan($createdAt)) {
            throw new InvalidArgumentException('updated_at nguồn nhỏ hơn created_at.');
        }

        return [$createdAt, $updatedAt];
    }
}
