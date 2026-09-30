<?php

namespace Src\Domains\Cms\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourFlashSale extends Model
{
    protected $table = 'tour_flash_sales';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon_class',
        'cta_label',
        'cta_url',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TourFlashSaleItem::class, 'tour_flash_sale_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isCurrentlyActive(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return (bool) $this->is_active
            && $this->starts_at?->lessThanOrEqualTo($at)
            && $this->ends_at?->greaterThan($at);
    }

    public function scopeActive(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
