<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class TourCategory extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    protected $table = 'tour_categories';

    protected $fillable = [
        'name',
        'slug',
        'scope',
        'excerpt',
        'content',
        'status',
        'is_featured',
        'sort_order',
        'published_at',
        'rating_average',
        'rating_count',
        'cover_alt',
        'cover_image_url',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'robots_directive',
        'schema',
        'geo_config',
        'gallery',
        'faq_items',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'geo_config' => 'array',
            'faq_items' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'rating_average' => 'decimal:1',
            'rating_count' => 'integer',
            'schema' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->registerFrontsiteImageConversions();
    }

    public function publishedReviews(): MorphMany
    {
        return $this->reviews()->published()->ordered();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $published): void {
                $published->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tour_category_tour');
    }

    public function primaryTours(): HasMany
    {
        return $this->hasMany(Tour::class, 'tour_category_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(TravelReview::class, 'reviewable');
    }
}
