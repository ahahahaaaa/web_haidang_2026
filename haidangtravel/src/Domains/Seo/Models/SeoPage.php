<?php

namespace Src\Domains\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;

class SeoPage extends Model
{
    protected $table = 'seo_pages';

    protected $fillable = [
        'content_cluster_id','page_type','title','slug','canonical_url',
        'primary_keyword','secondary_keywords','h1','excerpt','content',
        'meta_title','meta_description','og_title','og_description','og_image',
        'schema','faq_items','qa_report','generation_payload','status','published_at'
    ];

    protected $casts = [
        'secondary_keywords' => 'array',
        'schema' => 'array',
        'faq_items' => 'array',
        'qa_report' => 'array',
        'generation_payload' => 'array',
        'published_at' => 'datetime',
        'page_type' => SeoPageType::class,
        'status' => SeoPageStatus::class,
    ];

    public function cluster(): BelongsTo { return $this->belongsTo(ContentCluster::class, 'content_cluster_id'); }
    public function outgoingLinks(): HasMany { return $this->hasMany(SeoLink::class, 'source_page_id'); }
    public function incomingLinks(): HasMany { return $this->hasMany(SeoLink::class, 'target_page_id'); }
}
