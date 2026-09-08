<?php

namespace Src\Domains\Cms\Enums;

enum TravelInquirySource: string
{
    case Tour = 'tour';
    case Service = 'service';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Tour => 'Tour',
            self::Service => 'Dịch vụ',
            self::General => 'Liên hệ chung',
        };
    }
}
