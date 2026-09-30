<?php

namespace App\Services\Frontsite;

use App\Http\Controllers\FrontsiteController;
use App\Support\FrontsiteUrls;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Symfony\Component\HttpFoundation\Response;

class PublicUrlTargetRenderer
{
    public function __construct(
        private FrontsiteController $frontsite,
        private Router $router,
    ) {}

    public function render(Request $request, PublicUrlMapping $mapping): ?Response
    {
        $target = $this->target($mapping);

        if (! $target) {
            return null;
        }

        $result = match ($mapping->target_type) {
            'blog_post' => $this->frontsite->blogShow(FrontsiteUrls::blogPostCategorySlug($target), $target),
            'blog_category' => $this->frontsite->blogCategoryShow($request, $target->slug),
            'tour' => $this->frontsite->toursShow($target),
            'tour_category' => $this->frontsite->tourCategoryShow($request, $target),
            'destination' => $this->frontsite->destinationShow($request, $target->slug),
            'region' => $this->frontsite->regionShow($request, $target),
            'service' => $this->frontsite->servicesShow($target),
            'landing_page' => $this->frontsite->landingShow($target->slug),
            default => null,
        };

        if ($result === null) {
            return null;
        }

        if ($result instanceof View) {
            $this->useOriginalUrlAsCanonical($request, $mapping, $result);
        }

        $response = $this->router->prepareResponse($request, $result);
        $response->headers->set('Cache-Control', 'public, max-age='.max(0, (int) config('public_url_mappings.cache_seconds', 300)));
        $response->headers->set('X-Public-URL-Mapping', 'render-target');

        return $response;
    }

    private function target(PublicUrlMapping $mapping): BlogPost|ContentCategory|Tour|TourCategory|Destination|Region|Service|LandingPage|null
    {
        return match ($mapping->target_type) {
            'blog_post' => BlogPost::query()->published()->find($mapping->target_id),
            'blog_category' => ContentCategory::query()->forTaxonomy('blog')->find($mapping->target_id),
            'tour' => Tour::query()->published()->find($mapping->target_id),
            'tour_category' => TourCategory::query()->published()->find($mapping->target_id),
            'destination' => Destination::query()->published()->find($mapping->target_id),
            'region' => Region::query()->published()->find($mapping->target_id),
            'service' => Service::query()->published()->find($mapping->target_id),
            'landing_page' => LandingPage::query()->whereNull('page_key')->where('is_active', true)->find($mapping->target_id),
            default => null,
        };
    }

    private function useOriginalUrlAsCanonical(Request $request, PublicUrlMapping $mapping, View $view): void
    {
        $data = $view->getData();
        $publicUrl = $request->url();
        $targetUrl = url($mapping->target_path);

        if (is_array($data['seo'] ?? null)) {
            $data['seo']['canonical'] = $publicUrl;
            $data['seo']['schema'] = $this->replaceUrl($data['seo']['schema'] ?? null, $targetUrl, $publicUrl);
        }

        if (array_key_exists('articleUrl', $data)) {
            $data['articleUrl'] = $publicUrl;
        }

        $view->with($data);
    }

    private function replaceUrl(mixed $value, string $targetUrl, string $publicUrl): mixed
    {
        if (is_string($value)) {
            return str_replace($targetUrl, $publicUrl, $value);
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->replaceUrl($item, $targetUrl, $publicUrl);
        }

        return $value;
    }
}
