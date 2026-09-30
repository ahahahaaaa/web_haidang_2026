<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourFlashSaleItem extends Model
{
    protected $table = 'tour_flash_sale_items';

    protected $attributes = [
        'booked_quantity' => 0,
    ];

    protected $fillable = [
        'tour_flash_sale_id',
        'tour_id',
        'tour_departure_id',
        'flash_price',
        'ticket_quantity',
        'booked_quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'flash_price' => 'integer',
            'ticket_quantity' => 'integer',
            'booked_quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function effectiveTicketQuantity(): int
    {
        if ($this->ticket_quantity !== null) {
            return max(0, (int) $this->ticket_quantity);
        }

        return max(0, (int) ($this->departure?->available_slots ?? 0));
    }

    public function remainingTicketQuantity(): int
    {
        return max(0, $this->effectiveTicketQuantity() - (int) $this->booked_quantity);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(TourFlashSale::class, 'tour_flash_sale_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
