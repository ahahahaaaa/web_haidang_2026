<?php
namespace Src\Domains\Seo\Actions;
use Src\Domains\Seo\Jobs\GenerateSeoBriefJob;
use Src\Domains\Seo\Models\ContentCluster;
class DispatchClusterGenerationAction { public function execute(ContentCluster $cluster): void { GenerateSeoBriefJob::dispatch($cluster->getKey())->onQueue(config('seo_ai.queue', 'seo')); } }
