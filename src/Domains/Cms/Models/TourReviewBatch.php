<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourReviewBatch extends Model
{
    protected $table = 'tour_review_batches';

    protected $fillable = [
        'tour_id',
        'tour_departure_id',
        'departure_date',
        'label',
        'enabled',
        'password',
        'token',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'enabled' => 'boolean',
            'password' => 'encrypted',
            'sort_order' => 'integer',
        ];
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TravelReview::class, 'tour_review_batch_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function publicReviewUrl(): ?string
    {
        if (! $this->tour || ! $this->token) {
            return null;
        }

        return route('tour-reviews.public.show', [
            'tour' => $this->tour,
            'token' => $this->token,
        ]);
    }

    public function requiresPassword(): bool
    {
        return filled($this->password);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('departure_date')
            ->orderBy('id');
    }
}
