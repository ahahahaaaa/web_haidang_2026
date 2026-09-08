<?php

namespace Src\Domains\Estimator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateRoomTemplate extends Model
{
    protected $table = 'estimate_room_templates';

    protected $fillable = [
        'code',
        'name',
        'room_type',
        'building_types',
        'description',
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

    public function templateItems(): HasMany
    {
        return $this->hasMany(EstimateRoomTemplateItem::class, 'room_template_id')->orderBy('sort_order')->orderBy('id');
    }
}
