<?php

namespace Src\Domains\Estimator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateCatalogItem extends Model
{
    protected $table = 'estimate_catalog_items';

    protected $fillable = [
        'component_node_id',
        'code',
        'name',
        'item_type',
        'unit',
        'pricing_mode',
        'default_quantity_formula',
        'level_support',
        'building_types',
        'price_book_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level_support' => 'array',
            'building_types' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function componentNode(): BelongsTo
    {
        return $this->belongsTo(EstimateComponentNode::class, 'component_node_id');
    }

    public function roomTemplateItems(): HasMany
    {
        return $this->hasMany(EstimateRoomTemplateItem::class, 'catalog_item_id');
    }
}
