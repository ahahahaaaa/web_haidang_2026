<?php

namespace App\Modules\LegacyMigration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyStagedObject extends Model
{
    protected $fillable = [
        'run_id',
        'chunk_id',
        'object_key',
        'object_type',
        'legacy_id',
        'legacy_key',
        'is_partial',
        'checksum',
        'payload_json',
        'source_created_at',
        'source_updated_at',
        'status',
        'error_text',
    ];

    protected function casts(): array
    {
        return [
            'is_partial' => 'boolean',
            'payload_json' => 'array',
            'source_created_at' => 'datetime',
            'source_updated_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationRun::class, 'run_id');
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationChunk::class, 'chunk_id');
    }
}
