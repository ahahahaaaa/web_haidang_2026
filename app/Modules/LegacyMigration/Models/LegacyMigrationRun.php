<?php

namespace App\Modules\LegacyMigration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyMigrationRun extends Model
{
    protected $fillable = [
        'uuid',
        'source_system',
        'source_run_id',
        'schema_version',
        'source_filename',
        'status',
        'expected_chunks',
        'received_chunks',
        'expected_urls',
        'staged_urls',
        'mapped_urls',
        'casted_urls',
        'blocked_urls',
        'failed_urls',
        'summary_json',
        'error_text',
        'source_created_at',
        'started_at',
        'finalized_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_chunks' => 'integer',
            'received_chunks' => 'integer',
            'expected_urls' => 'integer',
            'staged_urls' => 'integer',
            'mapped_urls' => 'integer',
            'casted_urls' => 'integer',
            'blocked_urls' => 'integer',
            'failed_urls' => 'integer',
            'summary_json' => 'array',
            'source_created_at' => 'datetime',
            'started_at' => 'datetime',
            'finalized_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(LegacyMigrationChunk::class, 'run_id');
    }

    public function stagedObjects(): HasMany
    {
        return $this->hasMany(LegacyStagedObject::class, 'run_id');
    }

    public function stagedUrls(): HasMany
    {
        return $this->hasMany(LegacyStagedUrl::class, 'run_id');
    }
}
