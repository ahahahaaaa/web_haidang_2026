<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoOptimizationPolicy extends Model
{
    protected $fillable = ['site_id', 'publish_mode', 'allowed_page_types', 'revision', 'updated_by'];

    protected $attributes = ['publish_mode' => 'preview'];

    protected function casts(): array
    {
        return ['allowed_page_types' => 'array'];
    }
}
