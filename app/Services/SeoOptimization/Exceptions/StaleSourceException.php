<?php

namespace App\Services\SeoOptimization\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StaleSourceException extends ConflictHttpException implements ShouldntReport
{
    public function __construct(
        string $message = 'Nội dung hoặc dữ liệu liên quan đã thay đổi; cần tạo yêu cầu mới.',
    ) {
        parent::__construct($message);
    }
}
