<?php

namespace App\Http\Controllers;

use App\Services\Cms\SiteSettingsManager;
use App\Services\Seo\PublicSeoPagePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Src\Domains\Seo\Repositories\SeoPageRepository;

class FrontsiteSeoPageController extends Controller
{
    public function __construct(
        protected SeoPageRepository $pages,
        protected PublicSeoPagePresenter $presenter,
        protected SiteSettingsManager $site,
    ) {
    }

    public function show(Request $request, string $slug): View
    {
        $page = $this->pages->findPublishedBySlug($slug, $request->route('expectedType'));

        return view('themes.'.$this->site->activeTheme().'.pages.seo.show', $this->presenter->present($page));
    }
}
