<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'projects';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'content_category_id',
        'project_type_id',
        'location',
        'area_value',
        'area_unit',
        'timeline',
        'completion_date',
        'is_featured',
        'cover_alt',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'robots_directive',
        'schema',
        'faq_items',
        'related_questions',
        'gallery',
    ];

    protected function casts(): array
    {
        return [
            'area_value' => 'decimal:2',
            'completion_date' => 'date',
            'is_featured' => 'boolean',
            'faq_items' => 'array',
            'gallery' => 'array',
            'related_questions' => 'array',
            'schema' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
