<?php

namespace App\Modules\LegacyMigration\Exceptions;

use InvalidArgumentException;

class LegacyImageCountLimitExceeded extends InvalidArgumentException
{
    public const MESSAGE = 'Ảnh vượt quá giới hạn số ảnh migrate mỗi bài.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
