<?php

namespace Src\Domains\Seo\Repositories;

use Src\Domains\Seo\Enums\SeoClusterStatus;
use Src\Domains\Seo\Models\ContentCluster;

class SeoClusterRepository
{
    public function find(int $id): ContentCluster { return ContentCluster::query()->findOrFail($id); }
    public function approvedForSync() { return ContentCluster::query()->where('status', SeoClusterStatus::Approved); }
}
