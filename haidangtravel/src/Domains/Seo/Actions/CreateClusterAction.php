<?php
namespace Src\Domains\Seo\Actions;
use Src\Domains\Seo\Models\ContentCluster;
class CreateClusterAction { public function execute(array $payload): ContentCluster { return ContentCluster::query()->create($payload); } }
