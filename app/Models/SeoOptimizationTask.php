<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoOptimizationTask extends Model
{
    use HasUlids;

    protected $fillable = ['page_id', 'requested_by', 'status', 'brief', 'snapshot', 'source_version', 'strategy_revision', 'idempotency_key', 'request_hash', 'lease_token_hash', 'leased_by', 'leased_until', 'attempts', 'last_error', 'completed_at', 'automation'];

    protected $hidden = ['lease_token_hash'];

    protected function casts(): array
    {
        return ['brief' => 'array', 'snapshot' => 'array', 'automation' => 'array', 'leased_until' => 'datetime', 'completed_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationPage::class, 'page_id');
    }
}
