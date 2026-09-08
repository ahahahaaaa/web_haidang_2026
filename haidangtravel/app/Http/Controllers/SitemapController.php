<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(protected SitemapBuilder $sitemapBuilder)
    {
    }

    public function index(): Response
    {
        return response()
            ->view('seo.sitemap', ['entries' => $this->sitemapBuilder->build()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response()
            ->view('seo.robots', ['sitemapUrl' => route('sitemap')])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
