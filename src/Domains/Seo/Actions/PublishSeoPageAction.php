<?php
namespace Src\Domains\Seo\Actions;
use Src\Domains\Seo\Jobs\PublishSeoPageJob;
use Src\Domains\Seo\Models\SeoPage;
class PublishSeoPageAction { public function execute(SeoPage $page): void { PublishSeoPageJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo')); } }
