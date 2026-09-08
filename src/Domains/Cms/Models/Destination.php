<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class Destination extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    public const COUNTRY_ROOT_SLUG_PREFIX = 'du-lich-';

    protected $table = 'destinations';

    protected $attributes = [
        'show_tours_on_page' => true,
        'show_blogs_on_page' => false,
    ];

    protected $fillable = [
        'region_id',
        'country_id',
        'is_country_root',
        'show_tours_on_page',
        'show_blogs_on_page',
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
            'is_country_root' => 'boolean',
            'show_blogs_on_page' => 'boolean',
            'show_tours_on_page' => 'boolean',
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

    public static function countryBaseSlug(string $value): string
    {
        $slug = Str::slug($value);

        if (str_starts_with($slug, self::COUNTRY_ROOT_SLUG_PREFIX)) {
            return substr($slug, strlen(self::COUNTRY_ROOT_SLUG_PREFIX));
        }

        return $slug;
    }

    public static function countryRootSlug(string $value): string
    {
        $baseSlug = self::countryBaseSlug($value);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'viet-nam';

        return self::COUNTRY_ROOT_SLUG_PREFIX.$baseSlug;
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(self::class, 'country_id');
    }

    public function childDestinations(): HasMany
    {
        return $this->hasMany(self::class, 'country_id');
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

    public function scopeCountryRoots(Builder $query): Builder
    {
        return $query->where('is_country_root', true);
    }

    public function scopeRegularDestinations(Builder $query): Builder
    {
        return $query->where(function (Builder $destination): void {
            $destination
                ->whereNull('is_country_root')
                ->orWhere('is_country_root', false);
        });
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'destination_tour');
    }

    public function primaryTours(): HasMany
    {
        return $this->hasMany(Tour::class, 'destination_id');
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'destination_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(TravelReview::class, 'reviewable');
    }
}
