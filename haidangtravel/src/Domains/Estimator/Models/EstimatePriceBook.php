<?php

namespace Src\Domains\Estimator\Models;

use Illuminate\Database\Eloquent\Model;

class EstimatePriceBook extends Model
{
    protected $table = 'estimate_price_books';

    protected $fillable = [
        'code',
        'name',
        'version',
        'status',
        'level_support',
        'building_types',
        'item_prices',
        'description',
        'published_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level_support' => 'array',
            'building_types' => 'array',
            'item_prices' => 'array',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
