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
        'service_id',
        'context_title',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'travel_date',
        'party_size',
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
}
