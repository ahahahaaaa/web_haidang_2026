<?php

namespace App\Support;

class FrontsiteCardGrid
{
    public const DEFAULT_LIMIT = 6;

    public const DESKTOP_COLUMNS = 4;

    public const DEFAULT_CLASSES = 'grid gap-2 md:grid-cols-2 lg:grid-cols-4';

    public const HORIZONTAL_CLASSES = 'grid gap-2 md:grid-cols-2 lg:grid-cols-1';

    public const MAX_ITEMS = 12;

    public const MAX_ROWS = 3;

    public static function classes(null|int|string $count = null, ?string $defaultClasses = null): string
    {
        if (self::shouldUseHorizontalTourLayout($count)) {
            return self::HORIZONTAL_CLASSES;
        }

        return $defaultClasses ?: self::DEFAULT_CLASSES;
    }

    public static function tourVariant(null|int|string $count = null): string
    {
        return self::shouldUseHorizontalTourLayout($count) ? 'horizontal' : 'default';
    }

    public static function normalizeLimit(null|int|string $limit, int $default = self::DEFAULT_LIMIT): int
    {
        $resolved = (int) ($limit ?? $default);

        if ($resolved <= 0) {
            $resolved = $default;
        }

        return max(1, min(self::MAX_ITEMS, $resolved));
    }

    protected static function shouldUseHorizontalTourLayout(null|int|string $count): bool
    {
        if (! is_numeric($count)) {
            return false;
        }

        $resolved = (int) $count;

        return $resolved > 0 && $resolved < self::DESKTOP_COLUMNS;
    }
}
