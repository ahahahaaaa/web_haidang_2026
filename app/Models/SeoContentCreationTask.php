<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoContentCreationTask extends Model
{
    use HasUlids;

    protected $fillable = [
        'requested_by', 'credential_id', 'content_type', 'status', 'brief', 'payload', 'result',
        'idempotency_key', 'request_hash', 'content_hash', 'lease_token_hash', 'leased_until',
        'attempts', 'last_error', 'completed_at',
    ];

    protected $hidden = ['lease_token_hash'];

    protected function casts(): array
    {
        return [
            'brief' => 'array',
            'payload' => 'array',
            'result' => 'array',
            'leased_until' => 'datetime',
            'completed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationCredential::class, 'credential_id')->withTrashed();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(SeoContentCreationAsset::class, 'task_id');
    }
}
