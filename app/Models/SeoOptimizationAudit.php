<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoOptimizationAudit extends Model
{
    use HasUlids;

    protected $fillable = ['page_id', 'actor_id', 'source_version', 'strategy_revision', 'rule_version', 'status', 'score', 'grade', 'snapshot', 'report', 'sheet_sync_status'];

    protected function casts(): array
    {
        return ['score' => 'float', 'snapshot' => 'array', 'report' => 'array'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoOptimizationPage::class, 'page_id');
    }
}
