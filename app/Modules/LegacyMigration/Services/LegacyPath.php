<?php

namespace App\Modules\LegacyMigration\Services;

use App\Support\FrontsiteUrls;
use InvalidArgumentException;

class LegacyPath
{
    public function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '' || str_contains($value, "\0")) {
            throw new InvalidArgumentException('Đường dẫn URL không hợp lệ.');
        }

        $parts = parse_url($value);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Đường dẫn URL không hợp lệ.');
        }

        $path = FrontsiteUrls::canonicalPath((string) ($parts['path'] ?? $value));

        if (mb_strlen($path) > 700 || ! str_starts_with($path, '/')) {
            throw new InvalidArgumentException('Đường dẫn URL không hợp lệ.');
        }

        return $path;
    }

    public function assertRedirectable(string $sourcePath, string $targetPath): void
    {
        if ($sourcePath === $targetPath) {
            throw new InvalidArgumentException('URL nguồn và URL đích không được trùng nhau.');
        }

        $firstSegment = explode('/', ltrim($sourcePath, '/'), 2)[0] ?? '';

        if (in_array($firstSegment, ['api', 'admin', 'livewire', 'storage', 'build'], true)
            || preg_match('/\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|txt|xml|woff2?|ttf)$/i', $sourcePath)
        ) {
            throw new InvalidArgumentException('URL nguồn thuộc vùng được bảo vệ và không thể tạo redirect.');
        }
    }
}
