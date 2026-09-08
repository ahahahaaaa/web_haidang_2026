<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$registry = $app->make(App\Services\SeoOptimization\PageRegistryService::class);
$pages = [];
foreach (App\Models\SeoOptimizationPage::query()->where('site_id', config('seo_optimization.site_id'))->whereNotIn('classification', ['DRAFT_OR_PRIVATE', 'UNRESOLVED'])->orderBy('page_type')->orderBy('id')->get() as $page) {
    $source = $registry->source($page);
    if ($source && (($source->getAttribute('status') !== null && $source->getAttribute('status') !== 'published') || $source->getAttribute('published_at')?->isFuture())) {
        continue;
    }
    $pages[] = ['page_id'=>$page->id, 'site_id'=>$page->site_id, 'locale'=>$page->locale, 'page_type'=>$page->page_type,
        'title'=>$page->title, 'url'=>App\Support\FrontsiteUrls::canonicalBaseUrl().$page->path, 'owner_type'=>$page->owner_type,
        'owner_id'=>$page->owner_id, 'classification'=>$page->classification, 'source_version'=>$page->source_version,
        'inventory_at'=>$page->last_seen_at?->toIso8601String(), 'brief'=>$page->keyword_brief ?? []];
}
$result = ['environment'=>app()->environment(), 'site_id'=>config('seo_optimization.site_id'), 'exported_at'=>now()->toIso8601String(), 'pages'=>$pages];
file_put_contents(__DIR__.'/inventory.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
echo json_encode(['environment'=>$result['environment'], 'site_id'=>$result['site_id'], 'count'=>count($pages), 'by_type'=>array_count_values(array_column($pages,'page_type'))], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
