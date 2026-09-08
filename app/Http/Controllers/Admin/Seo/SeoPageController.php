<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Services\Cms\SiteSettingsManager;
use App\Services\Seo\PublicSeoPagePresenter;
use Src\Domains\Seo\Models\SeoPage;

class SeoPageController extends Controller
{
    public function index() { return view('admin.seo.pages.index'); }

    public function edit(SeoPage $page)
    {
        $this->authorize('update', $page);
        return view('admin.seo.pages.edit', compact('page'));
    }

    public function preview(SeoPage $page, PublicSeoPagePresenter $presenter, SiteSettingsManager $site)
    {
        $this->authorize('update', $page);

        return view('themes.'.$site->activeTheme().'.pages.seo.show', $presenter->present($page, isPreview: true));
    }
}
