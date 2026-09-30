<?php

namespace App\Modules\LegacyMigration\Models;

use App\Models\User;
use App\Modules\LegacyMigration\Exceptions\LegacyImageCountLimitExceeded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegacyStagedUrl extends Model
{
    protected $fillable = [
        'run_id',
        'chunk_id',
        'raw_url',
        'raw_path',
        'normalized_path',
        'path_hash',
        'resolved_path',
        'route_kind',
        'root_object_key',
        'payload_json',
        'clicks',
        'impressions',
        'ctr',
        'position',
        'mapping_mode',
        'merge_policy',
        'timestamp_policy',
        'target_type',
        'target_id',
        'target_route',
        'target_parameters_json',
        'target_path',
        'redirect_code',
        'status',
        'mapped_by',
        'mapped_at',
        'casted_at',
        'error_text',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'target_parameters_json' => 'array',
            'clicks' => 'integer',
            'impressions' => 'integer',
            'ctr' => 'decimal:8',
            'position' => 'decimal:4',
            'redirect_code' => 'integer',
            'mapped_at' => 'datetime',
            'casted_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(LegacyMigrationRun::class, 'run_id');
    }

    public function rootObject(): ?LegacyStagedObject
    {
        if (! $this->root_object_key) {
            return null;
        }

        return LegacyStagedObject::query()
            ->where('run_id', $this->run_id)
            ->where('object_key', $this->root_object_key)
            ->first();
    }

    public function mapper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }

    public function latestMediaAudit(): HasOne
    {
        return $this->hasOne(LegacyCastAudit::class, 'staged_url_id')
            ->ofMany(['id' => 'max'], fn (Builder $query) => $query->where('action', 'import_media'));
    }

    public function scopeWithImageCountLimitWarning(Builder $query): Builder
    {
        $message = LegacyImageCountLimitExceeded::MESSAGE;
        $encodedMessage = substr(json_encode($message, JSON_THROW_ON_ERROR), 1, -1);

        return $query->whereHas('latestMediaAudit', fn (Builder $audit) => $audit
            ->where('status', 'warning')
            ->where(fn (Builder $warnings) => $warnings
                ->where('after_json->warnings', 'like', '%"image_count_limit"%')
                ->orWhere('after_json->warnings', 'like', '%'.$message.'%')
                ->orWhere('after_json->warnings', 'like', '%'.$encodedMessage.'%')));
    }
}
