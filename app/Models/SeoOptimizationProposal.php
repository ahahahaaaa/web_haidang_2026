<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeoOptimizationProposal extends Model
{
    use HasUlids;

    protected $fillable = ['page_id', 'task_id', 'audit_id', 'source_version', 'strategy_revision', 'status', 'patch', 'before', 'qa', 'claims', 'missing_facts', 'notes', 'idempotency_key', 'request_hash', 'content_hash', 'created_by', 'approved_by', 'approved_at', 'applied_by', 'applied_at', 'applied_version', 'rejection_reason'];

    protected function casts(): array
    {
        return ['patch' => 'array', 'before' => 'array', 'qa' => 'array', 'claims' => 'array', 'missing_facts' => 'array', 'approved_at' => 'datetime', 'applied_at' => 'datetime'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationPage::class, 'page_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationTask::class, 'task_id')->withTrashed();
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationAudit::class, 'audit_id');
    }

    public function backup(): HasOne
    {
        return $this->hasOne(SeoOptimizationBackup::class, 'proposal_id');
    }
}
