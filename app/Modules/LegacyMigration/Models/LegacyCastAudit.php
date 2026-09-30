<?php

namespace App\Modules\LegacyMigration\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyCastAudit extends Model
{
    protected $fillable = [
        'run_id',
        'staged_url_id',
        'staged_object_id',
        'target_type',
        'target_id',
        'action',
        'merge_policy',
        'timestamp_policy',
        'before_json',
        'after_json',
        'source_created_at',
        'source_updated_at',
        'previous_target_created_at',
        'previous_target_updated_at',
        'actor_id',
        'status',
        'error_text',
    ];

    protected function casts(): array
    {
        return [
            'before_json' => 'array',
            'after_json' => 'array',
            'source_created_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'previous_target_created_at' => 'datetime',
            'previous_target_updated_at' => 'datetime',
        ];
    }
}
