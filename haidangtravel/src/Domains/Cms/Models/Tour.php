<?php

namespace Src\Domains\Cms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;
use Src\Domains\Cms\Enums\TourScope;

class Tour extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    protected $table = 'tours';

    protected static function booted(): void
    {
        static::saved(function (self $tour): void {
            $tour->ensurePrimaryTaxonomyLinks();
        });
    }

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'scope',
        'managed_by_user_id',
        'tour_category_id',
        'destination_id',
        'region_id',
        'destination_category_id',
        'region_category_id',
        'transport',
        'departure_location',
        'contact_phone',
        'duration_days',
        'duration_nights',
        'standard_label',
        'base_price',
        'sale_price',
        'rating_average',
        'rating_count',
        'cta_mode',
        'is_featured',
        'sort_order',
        'published_at',
        'cover_alt',
        'cover_image_url',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'robots_directive',
        'schema',
        'itinerary',
        'pricing_table',
        'departure_schedules',
        'inclusions',
        'tour_terms_items',
        'faq_items',
        'gallery',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'cta_mode' => 'string',
            'departure_schedules' => 'array',
            'duration_days' => 'integer',
            'duration_nights' => 'integer',
            'faq_items' => 'array',
            'gallery' => 'array',
            'inclusions' => 'array',
            'is_featured' => 'boolean',
            'itinerary' => 'array',
            'pricing_table' => 'array',
            'published_at' => 'datetime',
            'rating_average' => 'decimal:1',
            'rating_count' => 'integer',
            'sale_price' => 'integer',
            'schema' => 'array',
            'scope' => TourScope::class,
            'sort_order' => 'integer',
            'tour_terms_items' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TourCategory::class, 'tour_category_tour');
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class, 'destination_tour');
    }

    public function destination(): BelongsTo
    {
        return $this->primaryDestination();
    }

    public function departures(): HasMany
    {
        return $this->hasMany(TourDeparture::class, 'tour_id')->orderBy('departure_date')->orderBy('sort_order');
    }

    public function publishedReviews(): MorphMany
    {
        return $this->reviews()->published()->ordered();
    }

    public function legacyDestination(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'destination_category_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_user_id');
    }

    public function ensurePrimaryTaxonomyLinks(): void
    {
        if ($this->tour_category_id) {
            $this->categories()->syncWithoutDetaching([(int) $this->tour_category_id]);
        }

        if ($this->destination_id) {
            $this->destinations()->syncWithoutDetaching([(int) $this->destination_id]);
        }

        if ($this->region_id) {
            $this->regions()->syncWithoutDetaching([(int) $this->region_id]);
        }
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(TourCategory::class, 'tour_category_id');
    }

    public function primaryDestination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function primaryRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(TravelReview::class, 'reviewable');
    }

    public function region(): BelongsTo
    {
        return $this->primaryRegion();
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'region_tour');
    }

    public function legacyRegion(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'region_category_id');
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

    public function syncTaxonomyLinks(array $categoryIds = [], array $destinationIds = [], array $regionIds = []): void
    {
        $this->categories()->sync($this->mergedTaxonomyIds($categoryIds, $this->tour_category_id));
        $this->destinations()->sync($this->mergedTaxonomyIds($destinationIds, $this->destination_id));
        $this->regions()->sync($this->mergedTaxonomyIds($regionIds, $this->region_id));
    }

    public function scopeForScope(Builder $query, TourScope $scope): Builder
    {
        return $query->where('scope', $scope->value);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $published): void {
                $published->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    protected function mergedTaxonomyIds(array $ids, mixed $primaryId): array
    {
        return collect($ids)
            ->push($primaryId)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
