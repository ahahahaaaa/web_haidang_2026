<?php

namespace App\Services\SeoOptimization;

use App\Http\Controllers\FrontsiteController;
use App\Models\SeoOptimizationPage;
use App\Services\Cms\SiteSettingsManager;
use App\Services\SeoOptimization\Exceptions\StaleSourceException;
use App\Support\FrontsiteUrls;
use App\Support\RichText;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class PageSnapshotService
{
    public function __construct(
        private PageRegistryService $registry,
        private Application $app,
        private SiteSettingsManager $settings,
    ) {}

    public function capture(SeoOptimizationPage $page): array
    {
        $page = $this->registry->refresh($page);

        if (in_array($page->classification, ['DRAFT_OR_PRIVATE', 'UNRESOLVED'], true)) {
            throw new \DomainException('Không chụp nội dung riêng tư hoặc URL chưa xác định nguồn CMS.');
        }

        $version = (string) $page->source_version;
        $rendered = $this->renderPublic($page, $this->registry->source($page));
        $parsed = $this->parse($rendered['html'], $this->registry->url($page));

        if (! hash_equals($version, $this->registry->currentVersion($page))) {
            throw new StaleSourceException('Nội dung hoặc dữ liệu phụ thuộc đã thay đổi trong khi chụp trang; vui lòng chạy lại.');
        }

        return [
            'page_id' => (string) $page->getKey(),
            'url' => $this->registry->url($page),
            'version' => $version,
            'source_version' => $version,
            'page_type' => $page->page_type,
            'classification' => $rendered['status'] === 200 ? $page->classification : 'EXPECTED_INDEXABLE_ERROR',
            'source_hash' => $page->source_hash,
            'http_status' => $rendered['status'],
            'redirect_location' => $rendered['location'],
            'updated_at' => $this->registry->source($page)?->updated_at?->toAtomString(),
            'captured_at' => now()->toAtomString(),
            'content_contract_version' => ContentWriteContractService::VERSION,
            'source_fields' => $this->registry->sourceFields($page),
            'writable_fields' => $this->registry->writableFields($page),
            'field_contracts' => $this->registry->fieldContracts($page),
            'content_units' => $this->registry->contentUnits($page),
            'fact_sources' => [[
                'id' => 'page',
                'label' => 'Nội dung public hiện tại của trang',
                'kind' => 'current_page_snapshot',
                'url' => $this->registry->url($page),
                'rendered_hash' => $parsed['rendered_hash'],
                'usage' => 'Facts trong snapshot là baseline chính xác để giữ hoặc diễn đạt rõ hơn; không dùng để thêm hoặc đổi số liệu, chính sách hay tuyên bố khi chưa có nguồn mới.',
            ]],
            'render_mode' => 'anonymous_local_blade',
            ...$parsed,
        ];
    }

    public function parse(string $html, string $url): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.($html !== '' ? $html : '<html></html>'), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $title = $this->text($xpath->query('//title')->item(0));
        $description = $this->attribute($xpath, '//meta[@name="description"]', 'content');
        $canonical = $this->attribute($xpath, '//link[@rel="canonical"]', 'href');
        $robots = $this->attribute($xpath, '//meta[@name="robots"]', 'content');
        $structuredData = [];
        $schemaErrors = [];

        foreach ($xpath->query('//script[@type="application/ld+json"]') as $index => $script) {
            try {
                $value = json_decode($script->textContent, true, 128, JSON_THROW_ON_ERROR);
                if (is_array($value)) {
                    $structuredData[] = $value;
                } else {
                    $schemaErrors[] = ['index' => $index, 'code' => 'JSON_LD_OBJECT_REQUIRED'];
                }
            } catch (\JsonException) {
                $schemaErrors[] = ['index' => $index, 'code' => 'INVALID_JSON_LD'];
            }
        }

        $main = $xpath->query('//main')->item(0) ?? $xpath->query('//body')->item(0);

        if ($main !== null) {
            foreach (iterator_to_array($xpath->query('.//script | .//style | .//noscript | .//template | .//nav | .//footer | .//form | .//*[@hidden] | .//*[@aria-hidden="true"] | .//input | .//textarea | .//select', $main)) as $node) {
                $node->parentNode?->removeChild($node);
            }

            foreach ($xpath->query('.//*', $main) as $node) {
                foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                    $name = mb_strtolower($attribute->nodeName);
                    if (str_starts_with($name, 'on') || str_starts_with($name, 'wire:') || str_starts_with($name, 'x-') || str_contains($name, 'token') || str_contains($name, 'csrf')) {
                        $node->removeAttribute($attribute->nodeName);
                    }
                }
            }
        }

        $headings = [];
        $media = [];
        $links = [];

        if ($main !== null) {
            foreach ($xpath->query('.//h1 | .//h2 | .//h3 | .//h4 | .//h5 | .//h6', $main) as $node) {
                $headings[] = ['level' => (int) substr($node->nodeName, 1), 'text' => $this->text($node)];
            }

            foreach ($xpath->query('.//img', $main) as $node) {
                $media[] = [
                    'src' => $node->getAttribute('src'),
                    'alt' => $node->getAttribute('alt'),
                    'caption' => $this->text($xpath->query('ancestor::figure[1]/figcaption', $node)->item(0)),
                ];
            }

            foreach ($xpath->query('.//a[@href]', $main) as $node) {
                $target = $this->internalUrl($node->getAttribute('href'), $url);
                if ($target !== null) {
                    $links[] = ['url' => $target, 'anchor' => $this->text($node), 'rel' => $node->getAttribute('rel')];
                }
            }
        }

        $h1s = array_values(array_filter($headings, fn (array $heading): bool => $heading['level'] === 1));
        $mainHtml = $main ? $document->saveHTML($main) : '';

        return [
            'title' => $title,
            'meta_description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'h1' => $h1s[0]['text'] ?? '',
            'h1_count' => count($h1s),
            'headings' => $headings,
            'html' => $mainHtml,
            'text' => trim(preg_replace('/\s+/u', ' ', RichText::normalizePlain($mainHtml)) ?? ''),
            'structured_data' => $structuredData,
            'schema_errors' => $schemaErrors,
            'media' => $media,
            'internal_links' => $links,
            'rendered_hash' => hash('sha256', json_encode([$title, $description, $canonical, $mainHtml, $structuredData], JSON_THROW_ON_ERROR)),
        ];
    }

    private function renderPublic(SeoOptimizationPage $page, ?Model $source): array
    {
        $original = [];
        foreach (['request', 'session', 'session.store', 'auth'] as $binding) {
            $original[$binding] = $this->app->make($binding);
        }

        $views = $this->app->make('view');
        $originalErrors = $views->shared('errors');
        $request = Request::create($this->registry->url($page), 'GET');
        $session = new Store('seo_optimization_snapshot', new ArraySessionHandler(1));
        $session->start();
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => null);
        $route = clone $this->app->make('router')->getRoutes()->getByName($page->route_name);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        try {
            $this->app->instance('session', $session);
            $this->app->instance('session.store', $session);
            $this->app->instance('auth', new AuthManager($this->app));
            $this->app->instance('request', $request);
            $this->clearContextFacades();
            $views->share('errors', new ViewErrorBag);
            $this->settings->refresh();
            $controller = $this->app->make(FrontsiteController::class);
            $result = $this->dispatch($controller, $page, $source, $request);

            if ($result instanceof View) {
                return ['html' => $result->render(), 'status' => 200, 'location' => null];
            }

            return ['html' => '', 'status' => $result->getStatusCode(), 'location' => $result->headers->get('Location')];
        } finally {
            foreach ($original as $binding => $instance) {
                $this->app->instance($binding, $instance);
            }
            $views->share('errors', $originalErrors ?? new ViewErrorBag);
            $this->settings->refresh();
            $this->clearContextFacades();
        }
    }

    private function dispatch(FrontsiteController $controller, SeoOptimizationPage $page, ?Model $source, Request $request): View|Response
    {
        return match ($page->page_type) {
            'home' => $controller->home(),
            'about' => $controller->about(),
            'contact' => $controller->contact(),
            'service_index' => $controller->services($request),
            'blog_index' => $controller->blog($request),
            'tour_scope' => $controller->toursIndex($request, match ($page->route_name) {
                'tours.domestic' => 'domestic', 'tours.international' => 'international', 'tours.group' => 'group',
                default => throw new \DomainException('Route nhóm tour không hợp lệ.'),
            }),
            'tour' => $controller->toursShow($source),
            'tour_category' => $controller->tourCategoryShow($request, $source),
            'destination', 'country' => $controller->destinationShow($request, $source->slug),
            'region' => $controller->regionShow($request, $source),
            'service_category' => $controller->servicesCategoryShow($request, $source),
            'service' => $controller->servicesShow($source),
            'blog_category' => $controller->blogCategoryShow($request, $source->slug),
            'blog_post' => $controller->blogShow(FrontsiteUrls::blogPostCategorySlug($source), $source),
            'landing' => $controller->landingShow($source->slug),
            default => throw new \DomainException('Page adapter chưa được hỗ trợ.'),
        };
    }

    private function clearContextFacades(): void
    {
        foreach (['auth', 'session', 'request'] as $facade) {
            Facade::clearResolvedInstance($facade);
        }
    }

    private function attribute(DOMXPath $xpath, string $query, string $attribute): string
    {
        $node = $xpath->query($query)->item(0);

        return $node instanceof DOMElement ? trim($node->getAttribute($attribute)) : '';
    }

    private function text(?DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', $node?->textContent ?? '') ?? '');
    }

    private function internalUrl(string $href, string $pageUrl): ?string
    {
        $href = trim($href);
        if ($href === '' || preg_match('/^(?:mailto:|tel:|javascript:|data:|#)/i', $href)) {
            return null;
        }

        $parts = parse_url($href);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (isset($parts['scheme']) && ! in_array(mb_strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['host']) && mb_strtolower($parts['host']) !== mb_strtolower((string) parse_url($pageUrl, PHP_URL_HOST))) {
            return null;
        }

        if (! isset($parts['host']) && ! str_starts_with($href, '/')) {
            $href = rtrim(dirname((string) parse_url($pageUrl, PHP_URL_PATH)), '/').'/'.$href;
        }

        return FrontsiteUrls::canonicalUrl($href, true);
    }
}
