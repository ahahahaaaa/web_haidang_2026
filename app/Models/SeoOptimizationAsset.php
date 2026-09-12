<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SeoOptimizationAsset extends Model
{
    use HasUlids;

    protected $fillable = ['task_id', 'page_id', 'media_id', 'created_by', 'source_revision', 'source_type', 'source_url', 'prompt', 'alt', 'sha256', 'request_hash', 'manifest_hash', 'manifest'];

    protected function casts(): array
    {
        return ['manifest' => 'array'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationTask::class, 'task_id')->withTrashed();
    }
}
