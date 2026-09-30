<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Domains\Cms\Enums\TravelInquirySource;

class TravelInquiry extends Model
{
    protected $table = 'travel_inquiries';

    protected $fillable = [
        'source',
        'tour_id',
        'tour_departure_id',
        'tour_flash_sale_item_id',
        'service_id',
        'context_title',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'travel_date',
        'party_size',
        'quoted_unit_price',
        'regular_unit_price',
        'price_type',
        'ticket_count',
        'quoted_at',
        'message',
        'page_url',
        'mail_status',
        'mailed_to',
        'mailed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'mailed_at' => 'datetime',
            'meta' => 'array',
            'party_size' => 'integer',
            'quoted_at' => 'datetime',
            'quoted_unit_price' => 'integer',
            'regular_unit_price' => 'integer',
            'ticket_count' => 'integer',
            'travel_date' => 'date',
            'source' => TravelInquirySource::class,
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function tourDeparture(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function tourFlashSaleItem(): BelongsTo
    {
        return $this->belongsTo(TourFlashSaleItem::class, 'tour_flash_sale_item_id');
    }
}
