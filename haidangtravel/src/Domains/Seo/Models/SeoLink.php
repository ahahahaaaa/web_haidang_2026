<?php

namespace Src\Domains\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoLink extends Model
{
    protected $table = 'seo_links';
    protected $fillable = ['source_page_id','target_page_id','anchor_text','link_type','priority','status'];

    public function sourcePage(): BelongsTo { return $this->belongsTo(SeoPage::class, 'source_page_id'); }
    public function targetPage(): BelongsTo { return $this->belongsTo(SeoPage::class, 'target_page_id'); }
}
