<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SeoOptimizationEvent extends Model
{
    use HasUlids;

    protected $fillable = ['page_id', 'proposal_id', 'actor_id', 'event', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
