<?php

namespace App\Modules\LegacyMigration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyMigrationChunk extends Model
{
    protected $fillable = [
        'run_id',
        'sequence',
        'source_chunk_uuid',
        'idempotency_key',
        'payload_hash',
        'payload_disk',
        'payload_path',
        'payload_bytes',
        'url_count',
        'object_count',
        'status',
        'attempts',
        'error_text',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'payload_bytes' => 'integer',
            'url_count' => 'integer',
            'object_count' => 'integer',
            'attempts' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationRun::class, 'run_id');
    }
}
