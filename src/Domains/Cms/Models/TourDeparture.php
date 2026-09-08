<?php

namespace Src\Domains\Cms\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TourDeparture extends Model
{
    protected $table = 'tour_departures';

    protected $fillable = [
        'tour_id',
        'departure_date',
        'return_date',
        'departure_location',
        'transport_label',
        'standard_label',
        'base_price',
        'sale_price',
        'available_slots',
        'pricing_note',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'available_slots' => 'integer',
            'base_price' => 'integer',
            'departure_date' => 'date',
            'is_featured' => 'boolean',
            'return_date' => 'date',
            'sale_price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', ['scheduled', 'published']);
    }

    public function scopeCurrentOrUpcoming(Builder $query, ?CarbonInterface $today = null): Builder
    {
        $today ??= Carbon::today(config('app.timezone'));

        return $query->where(function (Builder $dateQuery) use ($today): void {
            $dateQuery
                ->whereNull('departure_date')
                ->orWhereDate('departure_date', '>=', $today->toDateString());
        });
    }

    public function scopeUpcomingPublic(Builder $query, ?CarbonInterface $today = null): Builder
    {
        return $query->published()->currentOrUpcoming($today);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function reviewBatches(): HasMany
    {
        return $this->hasMany(TourReviewBatch::class, 'tour_departure_id');
    }
}