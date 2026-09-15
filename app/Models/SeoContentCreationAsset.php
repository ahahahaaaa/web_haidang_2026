<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SeoContentCreationAsset extends Model
{
    use HasUlids;

    protected $fillable = ['task_id', 'media_id', 'created_by', 'reference', 'sha256', 'manifest'];

    protected function casts(): array
    {
        return ['manifest' => 'array'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SeoContentCreationTask::class, 'task_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
