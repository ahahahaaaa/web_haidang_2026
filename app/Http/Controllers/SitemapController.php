<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(protected SitemapBuilder $sitemapBuilder) {}

    public function index(): Response
    {
        $entries = $this->sitemapBuilder->build();

        return response()
            ->view(count($entries) > SitemapBuilder::PAGE_SIZE ? 'seo.sitemap-index' : 'seo.sitemap', [
                'entries' => count($entries) > SitemapBuilder::PAGE_SIZE ? $this->sitemapBuilder->index() : $entries,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function sitemapIndex(): Response
    {
        return response()
            ->view('seo.sitemap-index', ['entries' => $this->sitemapBuilder->index()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function type(string $type, string $page = '1'): Response
    {
        return response()
            ->view('seo.sitemap', ['entries' => $this->sitemapBuilder->forType($type, (int) $page)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response()
            ->view('seo.robots', ['sitemapUrl' => route('sitemap'), 'sitemapIndexUrl' => route('sitemap.index')])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
