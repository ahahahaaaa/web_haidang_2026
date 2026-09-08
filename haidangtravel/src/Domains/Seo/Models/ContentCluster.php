<?php

namespace Src\Domains\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Domains\Seo\Enums\SeoClusterStatus;
use Src\Domains\Seo\Enums\SeoPageType;

class ContentCluster extends Model
{
    protected $table = 'content_clusters';

    protected $fillable = ['name','primary_keyword','secondary_keywords','lsi_keywords','intent','target_page_type','business_value','priority_score','status','context'];

    protected $casts = [
        'secondary_keywords' => 'array',
        'lsi_keywords' => 'array',
        'context' => 'array',
        'status' => SeoClusterStatus::class,
        'target_page_type' => SeoPageType::class,
    ];

    public function seoPages(): HasMany
    {
        return $this->hasMany(SeoPage::class, 'content_cluster_id');
    }
}
