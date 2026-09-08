<?php

namespace Src\Domains\Estimator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateComponentNode extends Model
{
    protected $table = 'estimate_component_nodes';

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'node_type',
        'building_types',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'building_types' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(EstimateCatalogItem::class, 'component_node_id')->orderBy('name');
    }
}
