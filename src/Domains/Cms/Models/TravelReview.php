<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TravelReview extends Model
{
    protected $table = 'travel_reviews';

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'tour_review_batch_id',
        'title',
        'author_name',
        'author_title',
        'author_email',
        'author_phone',
        'content',
        'rating_value',
        'status',
        'source',
        'is_featured',
        'sort_order',
        'published_at',
        'submitted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'metadata' => 'array',
            'published_at' => 'date',
            'rating_value' => 'decimal:1',
            'sort_order' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tourReviewBatch(): BelongsTo
    {
        return $this->belongsTo(TourReviewBatch::class, 'tour_review_batch_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $published): void {
                $published->whereNull('published_at')->orWhereDate('published_at', '<=', now());
            });
    }
}
