<?php

namespace App\View\Composers;

use App\Services\Frontsite\TourSearchFilterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontsiteTourDiscoveryComposer
{
    private const OVERLAY_VIEW = 'themes.haidangtravel.partials.hero-tour-search-overlay';

    private const RENDERED_ATTRIBUTE = 'frontsite_tour_search_overlay_rendered';

    public function __construct(
        private readonly Request $request,
        private readonly TourSearchFilterService $tourSearchFilters,
    ) {}

    public function compose(View $view): void
    {
        $view->with('sitewideTourSearchFilter', $this->tourSearchFilters->viewData($this->request));

        if ($view->getName() !== self::OVERLAY_VIEW) {
            return;
        }

        $shouldRender = ! $this->request->attributes->getBoolean(self::RENDERED_ATTRIBUTE);

        if ($shouldRender) {
            $this->request->attributes->set(self::RENDERED_ATTRIBUTE, true);
        }

        $view->with('renderSitewideTourSearchFilter', $shouldRender);
    }
}
