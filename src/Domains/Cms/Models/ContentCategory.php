<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class ContentCategory extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    protected $table = 'content_categories';

    protected $fillable = [
        'taxonomy',
        'name',
        'slug',
        'parent_id',
        'description',
        'content',
        'faq_items',
        'geo_config',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'faq_items' => 'array',
            'geo_config' => 'array',
            'is_default' => 'boolean',
            'parent_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeForTaxonomy(Builder $query, string $taxonomy): Builder
    {
        return $query->where('taxonomy', $taxonomy);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'content_category_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'content_category_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'content_category_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->registerFrontsiteImageConversions();
    }

    public function toursByDestination(): HasMany
    {
        return $this->hasMany(Tour::class, 'destination_category_id');
    }

    public function toursByRegion(): HasMany
    {
        return $this->hasMany(Tour::class, 'region_category_id');
    }
}
