<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SeoOptimizationBackup extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id', 'proposal_id', 'created_by', 'source_version', 'owner_type', 'owner_id',
        'content_snapshot', 'media_snapshot', 'publish_snapshot', 'checksum',
    ];

    protected function casts(): array
    {
        return [
            'content_snapshot' => 'array',
            'media_snapshot' => 'array',
            'publish_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('SEO optimization backups are immutable.'));
        static::deleting(fn (): never => throw new LogicException('SEO optimization backups cannot be deleted.'));
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationPage::class, 'page_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationProposal::class, 'proposal_id');
    }
}
