<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['frontsite_cache.enabled' => false, 'frontsite_seo.canonical_url' => 'https://haidangtravel.com', 'frontsite_seo.canonical_redirect_enabled' => false, 'session.driver' => 'array']);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$urls = ['/', '/teambuilding'];
foreach ([Src\Domains\Cms\Models\BlogPost::class, Src\Domains\Cms\Models\Tour::class, Src\Domains\Cms\Models\Service::class] as $model) {
    foreach ($model::query()->published()->whereNotNull('canonical_url')->where('canonical_url', '!=', '')->get() as $item) {
        $urls[] = match ($model) {
            Src\Domains\Cms\Models\BlogPost::class => App\Support\FrontsiteUrls::blogPost($item),
            Src\Domains\Cms\Models\Tour::class => route('tours.show', $item),
            default => route('services.show', $item),
        };
    }
}
$report = [];
foreach (array_unique($urls) as $url) {
    $request = Illuminate\Http\Request::create($url, 'GET', server: ['HTTP_ACCEPT' => 'text/html']);
    $response = $kernel->handle($request);
    $html = $response->getContent();
    preg_match('/<link[^>]+rel="canonical"[^>]+href="([^"]+)"/', $html, $canonical);
    $record = ['url' => $url, 'status' => $response->getStatusCode(), 'canonical' => $canonical[1] ?? null];
    foreach (['head', 'body', 'title', 'h1'] as $tag) {
        $record[$tag] = preg_match_all('/<'.$tag.'(?:\s|>)/i', $html);
    }
    if ($url === '/' || $url === '/teambuilding') {
        $path = __DIR__.'/../outputs/seo-primary-fixes-20260918/local-'.($url === '/' ? 'home' : 'teambuilding').'-after.html';
        file_put_contents($path, $html);
    }
    $report[] = $record;
    $kernel->terminate($request, $response);
}
file_put_contents(__DIR__.'/../outputs/seo-primary-fixes-20260918/local-render-after.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
