<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$models = [
    'tour' => Src\Domains\Cms\Models\Tour::class,
    'blog' => Src\Domains\Cms\Models\BlogPost::class,
    'service' => Src\Domains\Cms\Models\Service::class,
    'landing' => Src\Domains\Cms\Models\LandingPage::class,
];
$report = [];
foreach ($models as $type => $model) {
    $report[$type] = [
        'total' => $model::query()->count(),
        'explicit_canonicals' => $model::query()->whereNotNull('canonical_url')->where('canonical_url', '!=', '')
            ->get(['id', 'slug', 'canonical_url', 'robots_directive'])->toArray(),
    ];
}
$report['landing_documents'] = [];
foreach (Src\Domains\Cms\Models\LandingPage::query()->get(['id', 'slug', 'page_key', 'body', 'blocks']) as $landing) {
    $contents = ['body' => $landing->body];
    foreach ((array) $landing->blocks as $index => $block) {
        if (($block['type'] ?? '') === 'html_widget') {
            $contents['widget_'.$index] = $block['html'] ?? '';
        }
    }
    foreach ($contents as $source => $content) {
        if (preg_match('~<(?:html|head|body)\b~i', (string) $content)) {
            $report['landing_documents'][] = ['id' => $landing->id, 'slug' => $landing->slug, 'page_key' => $landing->page_key, 'source' => $source, 'bytes' => strlen((string) $content)];
        }
    }
}
$path = __DIR__.'/../outputs/seo-primary-fixes-20260918/local-data-audit.json';
file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
