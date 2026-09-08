<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class BlogPost extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'faq_items',
        'status',
        'content_category_id',
        'country_destination_id',
        'destination_id',
        'author_name',
        'published_at',
        'is_featured',
        'sort_order',
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
        'reading_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'faq_items' => 'array',
            'geo_config' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reading_time_minutes' => 'integer',
            'schema' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    public function countryDestination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'country_destination_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->registerFrontsiteImageConversions();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $published): void {
                $published->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
