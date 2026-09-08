<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SeoOptimizationOutbox extends Model
{
    use HasUlids;

    protected $table = 'seo_optimization_outbox';

    protected $fillable = ['event_key', 'destination', 'payload', 'status', 'attempts', 'last_error', 'synced_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'attempts' => 'integer', 'synced_at' => 'datetime'];
    }
}
