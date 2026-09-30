<?php

namespace App\Modules\LegacyMigration\Exceptions;

use InvalidArgumentException;
use Throwable;

class LegacyImageDownloadFailure extends InvalidArgumentException
{
    public function __construct(
        string $message,
        public readonly string $sourceUrl,
        public readonly string $reasonCode,
        public readonly bool $retryable = false,
        public readonly ?string $detail = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function context(): array
    {
        return [
            'image_source' => self::redactedUrl($this->sourceUrl),
            'image_error_code' => $this->reasonCode,
            'retryable' => $this->retryable,
            'detail' => $this->detail,
        ];
    }

    public static function redactedUrl(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '');
    }
}
