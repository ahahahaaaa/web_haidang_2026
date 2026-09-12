<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeoOptimizationPage extends Model
{
    use HasUlids;

    protected $fillable = ['site_id', 'locale', 'page_type', 'owner_type', 'owner_id', 'route_name', 'path', 'title', 'classification', 'source_version', 'source_hash', 'capabilities', 'dependencies', 'keyword_brief', 'last_seen_at'];

    protected function casts(): array
    {
        return ['capabilities' => 'array', 'dependencies' => 'array', 'keyword_brief' => 'array', 'last_seen_at' => 'datetime'];
    }

    public function audits(): HasMany
    {
        return $this->hasMany(SeoOptimizationAudit::class, 'page_id');
    }

    public function latestAudit(): HasOne
    {
        return $this->hasOne(SeoOptimizationAudit::class, 'page_id')
            ->ofMany(['created_at' => 'max', 'id' => 'max']);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(SeoOptimizationProposal::class, 'page_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SeoOptimizationTask::class, 'page_id');
    }

    public function backups(): HasMany
    {
        return $this->hasMany(SeoOptimizationBackup::class, 'page_id');
    }
}
