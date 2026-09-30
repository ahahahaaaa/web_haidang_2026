<?php

namespace App\Modules\LegacyMigration\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyMigrationRedirect extends Model
{
    protected $fillable = [
        'source_hash',
        'source_path',
        'target_path',
        'status_code',
        'is_active',
        'run_id',
        'staged_url_id',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
