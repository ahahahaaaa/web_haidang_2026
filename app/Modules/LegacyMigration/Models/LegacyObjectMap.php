<?php

namespace App\Modules\LegacyMigration\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyObjectMap extends Model
{
    protected $fillable = [
        'source_identity',
        'source_system',
        'object_type',
        'legacy_id',
        'legacy_key',
        'target_type',
        'target_id',
        'last_checksum',
        'is_partial',
        'first_run_id',
        'last_run_id',
    ];

    protected function casts(): array
    {
        return ['is_partial' => 'boolean'];
    }

    public static function identity(string $sourceSystem, string $objectType, string|int|null $legacyId, ?string $legacyKey): string
    {
        return hash('sha256', implode('|', [$sourceSystem, $objectType, (string) ($legacyId ?? ''), (string) ($legacyKey ?? '')]));
    }
}
