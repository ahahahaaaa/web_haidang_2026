<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateAccessKey extends Model
{
    protected $table = 'estimate_access_keys';

    protected $fillable = [
        'label',
        'code',
        'tier_codes',
        'is_active',
        'used_count',
        'last_used_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'tier_codes' => 'array',
            'used_count' => 'integer',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(EstimateRequest::class, 'estimate_access_key_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
