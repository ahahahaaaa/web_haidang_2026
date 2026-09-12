<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoOptimizationRedirect extends Model
{
    use HasUlids;

    protected $fillable = [
        'page_id', 'proposal_id', 'site_id', 'locale', 'source_path', 'source_hash', 'target_path', 'status_code', 'is_active',
    ];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'is_active' => 'boolean'];
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
