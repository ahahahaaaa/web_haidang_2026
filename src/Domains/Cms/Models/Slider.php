<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slider extends Model
{
    protected $table = 'sliders';

    protected $fillable = [
        'name',
        'location',
        'description',
        'is_active',
        'autoplay_delay',
    ];

    protected function casts(): array
    {
        return [
            'autoplay_delay' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SliderItem::class)->orderBy('order');
    }
}
