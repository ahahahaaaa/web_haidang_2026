<?php

namespace App\Http\Controllers;

use App\Services\Cms\PublicBlogPostPresenter;
use App\Services\Cms\SiteSettingsManager;
use App\Services\Frontsite\FrontsiteGeoPresenter;
use App\Services\Travel\VoucherCampaignService;
use App\Support\ContentGallery;
use App\Support\ContentCategoryTree;
use App\Support\FrontsiteCardData;
use App\Support\FrontsiteCardGrid;
use App\Support\FrontsiteGalleryData;
use App\Support\FrontsiteMedia;
use App\Support\FrontsiteUrls;
use App\Support\LandingPageBlocks;
use App\Support\LandingPageVisuals;
use App\Support\ReviewContent;
use App\Support\RichText;
use App\Support\SliderLocations;
use App\Support\TravelHomePageConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;

class FrontsiteController extends Controller
{
    public function __construct(
        protected SiteSettingsManager $site,
        protected PublicBlogPostPresenter $blogPresenter,
        protected FrontsiteGeoPresenter $geoPresenter,
        protected VoucherCampaignService $voucherCampaigns,
    ) {}

    public function home(): View
    {
        $landing = $this->landing('home');
        $landingBlocks = LandingPageBlocks::normalize($landing?->blocks ?? []);
        $homeConfig = TravelHomePageConfig::prepare($landing?->home_config, $landingBlocks);
        $homeConfig = $this->resolveHomepageConfigMedia($homeConfig);
        $homeBlockVisibility = $this->homeBlockVisibility($landing, $homeConfig);
        $featuredServiceSlugs = collect(data_get($homeConfig, 'featured_service_slugs', []))->filter()->values()->all();
        $featuredBlogSlugs = collect(data_get($homeConfig, 'featured_blog_slugs', []))->filter()->values()->all();
        $featuredDestinationSlugs = collect(data_get($homeConfig, 'featured_destination_slugs', []))->filter()->values()->all();
        $featuredTourLimit = FrontsiteCardGrid::normalizeLimit(
            data_get($homeConfig, 'featured_tour_limit'),
            FrontsiteCardGrid::MAX_ITEMS,
        );
        $featuredBlogLimit = FrontsiteCardGrid::normalizeLimit(
            data_get($homeConfig, 'featured_blog_limit'),
            max(3, count($featuredBlogSlugs)),
        );
        $featuredTourCategory = $this->homepageFeaturedTourCategory(
            trim((string) data_get($homeConfig, 'featured_tour_category_slug', '')),
        );
        $domesticTours = $this->queryHomepageFeaturedTours(TourScope::Domestic, $featuredTourLimit, $featuredTourCategory);
        $internationalTours = $this->queryHomepageFeaturedTours(TourScope::International, $featuredTourLimit, $featuredTourCategory);
        $groupTours = $this->queryHomepageFeaturedTours(TourScope::Group, $featuredTourLimit, $featuredTourCategory);
        $featuredTours = collect([$domesticTours, $internationalTours, $groupTours])
            ->flatten(1)
            ->values();

        $featuredServices = Service::query()
            ->published()
            ->with('category')
            ->when(
                $featuredServiceSlugs !== [],
                fn ($query) => $query->whereIn('slug', $featuredServiceSlugs),
                fn ($query) => $query->where('is_featured', true)->limit(4),
            )
            ->get();
        $featuredServices = $featuredServiceSlugs !== []
            ? $this->sortModelsBySlug($featuredServices, $featuredServiceSlugs)->take(4)->values()
            : $featuredServices;

        $featuredPosts = BlogPost::query()
            ->published()
            ->with(['category.parent', 'media', 'category.media', 'category.parent.media', 'countryDestination', 'destination.country'])
            ->when(
                $featuredBlogSlugs !== [],
                fn ($query) => $query->whereIn('slug', $featuredBlogSlugs),
                fn ($query) => $query->latest('published_at')->limit($featuredBlogLimit),
            )
            ->get();
        $featuredPosts = $featuredBlogSlugs !== []
            ? $this->sortModelsBySlug($featuredPosts, $featuredBlogSlugs)
                ->take($featuredBlogLimit)
                ->values()
            : $featuredPosts;

        $homeTourCategories = TourCategory::query()
            ->published()
            ->where('is_featured', true)
            ->with('media')
            ->withCount(['tours' => fn (Builder $tourQuery) => $tourQuery->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();
        $homeDestinations = $this->homepageFeaturedDestinations($featuredDestinationSlugs);
        $homeFaqItems = $this->homepageFaqItems($landing);
        $homeRegionTaxonomyTabsBlock = $this->resolveHomeRegionTaxonomyTabsBlock($landing);
        $homeTourTaxonomyTabsBlock = $this->resolveHomeTourTaxonomyTabsBlock($landing);
        $homeHeroDemoBlock = $this->resolveHomeHeroDemoBlock($landing);
        $homePopupSlider = $this->resolveHomePopupSlider($landing);
        $homeLayoutOrder = TravelHomePageConfig::homeLayoutOrder(
            $homeConfig,
            $landingBlocks,
        );
        $homeLayoutBlocks = collect($this->resolveLandingContentBlocks($landing, true))
            ->filter(fn (array $block) => (bool) ($block['is_enabled'] ?? true))
            ->reject(fn (array $block) => in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_HERO_SLIDER,
                LandingPageBlocks::TYPE_HERO_MEDIA,
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
            ], true))
            ->keyBy(fn (array $block) => (string) ($block['uuid'] ?? ''));
        $homeBlogPreviewSchemaTitle = trim((string) data_get($homeConfig, 'blog_preview.title', '')) ?: 'Cẩm nang du lịch';
        $homeBlogPreviewSchemaDescription = trim((string) data_get($homeConfig, 'blog_preview.description', ''));

        return view($this->theme('pages.home'), [
            'landing' => $landing,
            'landingHtmlWidgetBlocks' => $this->resolveLandingHtmlWidgetBlocks($landing),
            'homePositionedBlockTypes' => collect($homeLayoutOrder)
                ->map(fn (string $token) => TravelHomePageConfig::homeLayoutBlockUuid($token))
                ->filter()
                ->map(fn (string $uuid) => data_get($homeLayoutBlocks->get($uuid), 'type'))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'homeLayoutBlocks' => $homeLayoutBlocks,
            'homeLayoutOrder' => $homeLayoutOrder,
            'featuredTours' => $featuredTours,
            'featuredServices' => $featuredServices,
            'featuredPosts' => $featuredPosts,
            'homeConfig' => $homeConfig,
            'homeBlockVisibility' => $homeBlockVisibility,
            'domesticTours' => $domesticTours,
            'internationalTours' => $internationalTours,
            'groupTours' => $groupTours,
            'homeTourCategories' => $homeTourCategories,
            'homeDestinations' => $homeDestinations,
            'geo' => $this->geoPresenter->forHome($landing, $featuredTours, $homeTourCategories, $homeDestinations),
            'homeFaqItems' => collect($homeFaqItems),
            'homeHeroDemoBlock' => $homeHeroDemoBlock,
            'homePopupSlider' => $homePopupSlider,
            'homeRegionTaxonomyTabsBlock' => $homeRegionTaxonomyTabsBlock,
            'homeTourTaxonomyTabsBlock' => $homeTourTaxonomyTabsBlock,
            ...$this->landingVisuals($landing),
            'seo' => $this->seo([
                'title' => $landing?->meta_title ?: $this->site->current()->seo_title ?: $this->site->current()->site_name,
                'description' => $landing?->meta_description ?: $this->site->current()->seo_description,
                'canonical' => route('home'),
                'og_image' => $this->landingPrimaryImageUrl($landing, FrontsiteMedia::SIZE_SMALL),
                'schema' => $this->graphSchema(array_merge([
                    $this->organizationSchema(),
                    $this->localBusinessSchema(),
                    $this->websiteSchema(),
                ], $this->homepageSchemaGraph(
                    $landing,
                    $homeBlockVisibility['featured_tours'] ? $featuredTours : collect(),
                    $homeBlockVisibility['topic_rail'] ? $homeTourCategories : collect(),
                    $homeBlockVisibility['destination_slider'] ? $homeDestinations : collect(),
                    $homeBlockVisibility['blog_preview'] ? $featuredPosts : collect(),
                    $homeLayoutBlocks,
                    $homeBlogPreviewSchemaTitle,
                    $homeBlogPreviewSchemaDescription !== '' ? $homeBlogPreviewSchemaDescription : null,
                    $homeBlockVisibility['faq'] ? $homeFaqItems : [],
                    route('home'),
                ))),
            ]),
        ]);
    }

    public function about(): View
    {
        $landing = $this->landing('about');
        $aboutUrl = route('about');
        $aboutTitle = $landing?->title ?: 'Về chúng tôi';
        $aboutDescription = $landing?->meta_description ?: $landing?->intro_excerpt ?: $this->site->current()->seo_description;

        return view($this->theme('pages.about'), [
            'landing' => $landing,
            'landingContentBlocks' => $this->resolveLandingContentBlocks($landing),
            'landingHtmlWidgetBlocks' => $this->resolveLandingHtmlWidgetBlocks($landing),
            ...$this->landingVisuals($landing),
            'seo' => $this->seo([
                'title' => $landing?->meta_title ?: $aboutTitle.' | '.$this->site->current()->site_name,
                'description' => $aboutDescription,
                'canonical' => $aboutUrl,
                'og_image' => $this->landingPrimaryImageUrl($landing, FrontsiteMedia::SIZE_SMALL),
                'schema' => $this->graphSchema([
                    $this->organizationSchema(),
                    $this->schemaNode([
                        '@id' => $aboutUrl.'#webpage',
                        '@type' => 'AboutPage',
                        'about' => $this->organizationReference(),
                        'description' => $aboutDescription,
                        'image' => $this->landingImageSchema($landing),
                        'mainEntity' => $this->organizationReference(),
                        'mainEntityOfPage' => $aboutUrl,
                        'name' => $aboutTitle,
                        'primaryImageOfPage' => $this->landingPrimaryImageSchema($landing, $aboutUrl),
                        'url' => $aboutUrl,
                    ]),
                    $this->breadcrumbSchema([
                        ['name' => 'Trang chủ', 'url' => route('home')],
                        ['name' => $aboutTitle, 'url' => $aboutUrl],
                    ]),
                ]),
            ]),
        ]);
    }

    public function contact(): View
    {
        $landing = $this->landing('contact');
        $contactUrl = route('contact');
        $contactTitle = $landing?->title ?: 'Liên hệ';
        $contactDescription = $landing?->meta_description ?: $landing?->hero_excerpt ?: $this->site->current()->seo_description;

        return view($this->theme('pages.contact'), [
            'landing' => $landing,
            'landingHtmlWidgetBlocks' => $this->resolveLandingHtmlWidgetBlocks($landing),
            'featuredServices' => Service::query()->published()->where('is_featured', true)->limit(4)->get(),
            ...$this->landingVisuals($landing),
            'seo' => $this->seo([
                'title' => $landing?->meta_title ?: $contactTitle.' | '.$this->site->current()->site_name,
                'description' => $contactDescription,
                'canonical' => $contactUrl,
                'og_image' => $this->landingPrimaryImageUrl($landing, FrontsiteMedia::SIZE_SMALL),
                'schema' => $this->graphSchema([
                    $this->organizationSchema(),
                    $this->schemaNode([
                        '@id' => $contactUrl.'#webpage',
                        '@type' => 'ContactPage',
                        'about' => $this->organizationReference(),
                        'description' => $contactDescription,
                        'image' => $this->landingImageSchema($landing),
                        'mainEntity' => $this->organizationReference(),
                        'mainEntityOfPage' => $contactUrl,
                        'name' => $contactTitle,
                        'primaryImageOfPage' => $this->landingPrimaryImageSchema($landing, $contactUrl),
                        'url' => $contactUrl,
                    ]),
                    $this->breadcrumbSchema([
                        ['name' => 'Trang chủ', 'url' => route('home')],
                        ['name' => $contactTitle, 'url' => $contactUrl],
                    ]),
                ]),
            ]),
        ]);
    }

    public function services(Request $request): RedirectResponse|View
    {
        $selectedServiceCategory = null;
        $selectedServiceCategorySlug = trim((string) $request->query('category'));

        if ($selectedServiceCategorySlug !== '') {
            $selectedServiceCategory = ContentCategory::query()
                ->forTaxonomy('service')
                ->where('slug', $selectedServiceCategorySlug)
                ->whereHas('services', fn ($query) => $query->published())
                ->first();
        }

        if ($selectedServiceCategory && $request->query->keys() === ['category']) {
            return redirect()->route('service-categories.show', ['category' => $selectedServiceCategory->slug], 301);
        }

        return $this->renderServiceListing($request, $selectedServiceCategory);
    }

    public function servicesCategoryShow(Request $request, ContentCategory $category): View
    {
        abort_unless(
            $category->taxonomy === 'service'
            && $category->services()->published()->exists(),
            404
        );

        return $this->renderServiceListing($request, $category);
    }

    public function servicesShow(Service $service): View
    {
        $service->loadMissing('category');
        $serviceUrl = route('services.show', $service);
        $faqSchema = $this->faqSchema($service->faq_items ?? []);
        $serviceCategoryUrl = $service->category && $service->category->taxonomy === 'service'
            ? route('service-categories.show', ['category' => $service->category->slug])
            : route('services.index');

        return view($this->theme('pages.services.show'), [
            'service' => $service,
            'serviceCategoryUrl' => $serviceCategoryUrl,
            'geo' => $this->geoPresenter->forService($service),
            'relatedServices' => Service::query()->published()->with('category')->whereKeyNot($service->getKey())->where('is_featured', true)->limit(4)->get(),
            'seo' => $this->seo([
                'title' => $service->meta_title ?: $service->title.' | '.$this->site->current()->site_name,
                'description' => $service->meta_description ?: $service->excerpt ?: $this->site->current()->seo_description,
                'canonical' => $service->canonical_url ?: $serviceUrl,
                'og_title' => $service->og_title ?: $service->meta_title ?: $service->title,
                'og_description' => $service->og_description ?: $service->meta_description ?: $service->excerpt ?: $this->site->current()->seo_description,
                'og_image' => $this->modelMediaUrl($service, 'cover', FrontsiteMedia::SIZE_SMALL),
                'schema' => $this->graphSchema([
                    $this->organizationSchema(),
                    $this->schemaNode([
                        '@id' => $serviceUrl.'#service',
                        '@type' => 'Service',
                        'category' => $service->category?->name,
                        'name' => $service->title,
                        'serviceType' => $service->category?->name ?: $service->title,
                        'description' => $service->excerpt ?: strip_tags((string) $service->content),
                        'image' => $this->modelImageSchema($service, 'cover', trim((string) (data_get($service, 'cover_alt') ?: $service->title))),
                        'areaServed' => 'Việt Nam',
                        'mainEntityOfPage' => $serviceUrl,
                        'provider' => $this->organizationReference(),
                        'url' => $serviceUrl,
                    ]),
                    $faqSchema,
                    $this->breadcrumbSchema([
                        ['name' => 'Trang chủ', 'url' => route('home')],
                        ['name' => 'Dịch vụ', 'url' => route('services.index')],
                        ...($service->category ? [['name' => $service->category->name, 'url' => $serviceCategoryUrl]] : []),
                        ['name' => $service->title, 'url' => $serviceUrl],
                    ]),
                ]),
            ]),
        ]);
    }

    public function toursIndex(Request $request, string $scope): View
    {
        $tourScope = TourScope::tryFrom($scope) ?? abort(404);
        $pageKey = match ($tourScope) {
            TourScope::Domestic => 'domestic_tours',
            TourScope::International => 'international_tours',
            TourScope::Group => 'group_tours',
        };
        $landing = $this->landing($pageKey);

        return $this->renderTourListing(
            $request,
            Tour::query()->published()->forScope($tourScope),
            [
                'breadcrumbs' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => $landing?->title ?: $tourScope->label()],
                ],
                'canonical' => route($tourScope->routeName()),
                'context_badge' => $landing?->hero_badge ?: $tourScope->label(),
                'context_label' => $tourScope->label(),
                'landing' => $landing,
                'page_content' => $landing?->body,
                'page_description' => $landing?->hero_excerpt ?: $landing?->intro_excerpt ?: $this->site->current()->site_description,
                'page_intro' => $landing?->intro_title ?: 'Nhóm tour',
                'page_title' => $landing?->hero_title ?: $landing?->title ?: $tourScope->label(),
                'search_category_options' => in_array($tourScope, [TourScope::Domestic, TourScope::International], true)
                    ? $this->tourListingSearchCategories($tourScope)
                    : [],
                'seo_description' => $landing?->meta_description ?: $landing?->hero_excerpt ?: $this->site->current()->seo_description,
                'seo_title' => $landing?->meta_title ?: ($landing?->title ?: 'Tours').' | '.$this->site->current()->site_name,
            ],
        );
    }

    public function tourSearch(Request $request): View
    {
        $searchQuery = trim($request->string('q')->toString());
        $pageTitle = $searchQuery !== ''
            ? 'Kết quả tìm tour cho "'.$searchQuery.'"'
            : 'Tìm tour';
        $pageDescription = $searchQuery !== ''
            ? 'Kết quả phù hợp với từ khóa tìm tour và bộ lọc bạn đang chọn.'
            : 'Tìm nhanh tour theo từ khóa, ngày đi và ngân sách.';

        return $this->renderTourListing(
            $request,
            Tour::query()->published(),
            [
                'breadcrumbs' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => 'Tìm tour'],
                ],
                'canonical' => route('tours.search'),
                'context_badge' => 'Tìm tour',
                'context_label' => 'Tìm tour',
                'page_description' => $pageDescription,
                'page_intro' => 'Kết quả tìm nhanh',
                'page_title' => $pageTitle,
                'hide_listing_context_panel' => true,
                'robots_directive' => 'noindex,follow',
                'seo_description' => $pageDescription,
                'seo_title' => $pageTitle.' | '.$this->site->current()->site_name,
            ],
        );
    }

    public function toursShow(Tour $tour): View
    {
        $tour->loadMissing(['primaryCategory', 'destination.country', 'destination.region', 'region']);
        $reviewsEnabled = $this->travelReviewsEnabled();
        $tourRelations = [
            'agencySyncStates' => fn ($syncStateQuery) => $syncStateQuery
                ->whereNotNull('tour_code')
                ->latest('last_synced_at')
                ->latest('updated_at'),
            'departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date')->orderBy('sort_order'),
        ];

        if ($reviewsEnabled) {
            $tourRelations['publishedReviews'] = fn ($reviewQuery) => $reviewQuery->limit(6);
            $tourRelations['reviewBatches'] = fn ($reviewBatchQuery) => $reviewBatchQuery
                ->with('departure')
                ->enabled()
                ->ordered();
        }

        $tour->load($tourRelations);
        $tourUrl = route('tours.show', $tour);
        $offerGraph = $this->tourOfferGraph($tour, $tourUrl);
        $faqSchema = $this->faqSchema($tour->faq_items ?? []);
        $itinerarySchema = $this->tourItinerarySchema($tour, $tourUrl);
        $reviewItems = $reviewsEnabled ? ReviewContent::fromModels($tour->publishedReviews) : [];
        $reviewSummary = ReviewContent::aggregate(
            $reviewItems,
            filled($tour->rating_average) ? (float) $tour->rating_average : null,
            filled($tour->rating_count) ? (int) $tour->rating_count : null,
        );
        $tourPlaceSchemas = $this->tourPlaceSchemas($tour);
        $tourHeroMedia = $this->tourResponsiveImageUrls($tour);
        $tourBreadcrumbs = $this->tourHierarchyBreadcrumbs(
            $tour->scope,
            $tour->region ?: $tour->destination?->region,
            $tour->destination,
            ['label' => $tour->title, 'url' => $tourUrl],
        );
        $relatedTours = $this->relatedToursForTour($tour);

        return view($this->theme('pages.tours.show'), [
            'tour' => $tour,
            'breadcrumbs' => $tourBreadcrumbs,
            'geo' => $this->geoPresenter->forTour($tour),
            'reviewItems' => $reviewItems,
            'reviewSummary' => $reviewSummary,
            'tourReviewBatches' => $reviewsEnabled ? $tour->reviewBatches : collect(),
            'tourHeroMedia' => $tourHeroMedia,
            'tourVoucherCta' => $this->tourVoucherCta(),
            'relatedTours' => $relatedTours,
            'seo' => $this->seo([
                'title' => $tour->meta_title ?: $tour->title,
                'description' => $tour->meta_description ?: $tour->excerpt ?: $this->site->current()->seo_description,
                'canonical' => $tour->canonical_url ?: $tourUrl,
                'og_image' => $tourHeroMedia[FrontsiteMedia::SIZE_SMALL] ?? null,
                'schema' => $this->graphSchema(array_merge([
                    $this->organizationSchema(),
                    $this->tourDetailEntitySchema($tour, $tourUrl, $offerGraph['primary'], $reviewItems, $reviewSummary, $itinerarySchema),
                ], $tourPlaceSchemas, $offerGraph['nodes'], [
                    $itinerarySchema,
                    $faqSchema,
                    $this->breadcrumbSchema($this->breadcrumbSchemaItems($tourBreadcrumbs)),
                ])),
            ]),
        ]);
    }

    protected function relatedToursForTour(Tour $tour): Collection
    {
        $tour->loadMissing(['categories', 'destinations']);

        $categoryIds = $this->relatedTourTaxonomyIds($tour->categories, $tour->tour_category_id);
        $destinationIds = $this->relatedTourTaxonomyIds($tour->destinations, $tour->destination_id);

        if ($categoryIds->isEmpty() && $destinationIds->isEmpty()) {
            return collect();
        }

        return Tour::query()
            ->published()
            ->whereKeyNot($tour->getKey())
            ->where('scope', $tour->scope->value)
            ->where(function (Builder $relatedQuery) use ($categoryIds, $destinationIds): void {
                if ($categoryIds->isNotEmpty()) {
                    $relatedQuery
                        ->whereIn('tour_category_id', $categoryIds->all())
                        ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereKey($categoryIds->all()));
                }

                if ($destinationIds->isNotEmpty()) {
                    $destinationFilter = fn (Builder $destinationQuery) => $destinationQuery->whereKey($destinationIds->all());

                    if ($categoryIds->isEmpty()) {
                        $relatedQuery
                            ->whereIn('destination_id', $destinationIds->all())
                            ->orWhereHas('destinations', $destinationFilter);

                        return;
                    }

                    $relatedQuery
                        ->orWhereIn('destination_id', $destinationIds->all())
                        ->orWhereHas('destinations', $destinationFilter);
                }
            })
            ->with([
                'departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date'),
                'destination.country',
                'destination.media',
                'primaryCategory.media',
                'region.media',
            ])
            ->limit(4)
            ->get();
    }

    protected function relatedTourTaxonomyIds(Collection $taxonomies, mixed $primaryId): Collection
    {
        return $taxonomies
            ->pluck('id')
            ->push($primaryId)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }

    public function legacyTourShow(Tour $tour): RedirectResponse
    {
        return redirect()->route('tours.show', $tour, 301);
    }

    public function tourCategoryShow(Request $request, TourCategory $category): View
    {
        $reviewsEnabled = $this->travelReviewsEnabled();

        if ($reviewsEnabled) {
            $category->load([
                'publishedReviews' => fn ($reviewQuery) => $reviewQuery->limit(6),
            ]);
        }

        return $this->renderTourListing(
            $request,
            $category->tours()->published(),
            [
                'breadcrumbs' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => 'Danh mục tour', 'url' => route('tour-categories.show', $category)],
                    ['label' => $category->name],
                ],
                'canonical' => route('tour-categories.show', $category),
                'context_badge' => 'Danh mục tour',
                'context_label' => $category->name,
                'fixed_category' => $category,
                'page_content' => $category->content,
                'page_description' => $category->excerpt ?: $this->site->current()->site_description,
                'page_faq_items' => $category->faq_items ?? [],
                'page_intro' => 'Landing danh mục',
                'page_rating_average' => filled($category->rating_average) ? (float) $category->rating_average : null,
                'page_rating_count' => filled($category->rating_count) ? (int) $category->rating_count : null,
                'page_reviews' => $reviewsEnabled ? ReviewContent::fromModels($category->publishedReviews) : [],
                'page_title' => $category->name,
                'allow_scope_filter' => true,
                'show_voucher_cta' => true,
                'seo_description' => $category->meta_description ?: $category->excerpt ?: $this->site->current()->seo_description,
                'seo_title' => $category->meta_title ?: $category->name,
            ],
        );
    }

    public function destinationShow(Request $request, string $destination): RedirectResponse|View
    {
        $destinationModel = $this->findPublishedDestinationBySlug($destination);

        if (! $destinationModel) {
            $canonicalCountrySlug = Destination::countryRootSlug($destination);

            if ($canonicalCountrySlug !== $destination) {
                $country = Destination::query()
                    ->published()
                    ->countryRoots()
                    ->where('slug', $canonicalCountrySlug)
                    ->first();

                if ($country) {
                    return redirect()->route('countries.show', ['slug' => $country->slug], 301);
                }
            }

            return $this->landingShow('tour-'.$destination);
        }

        if ((bool) $destinationModel->is_country_root) {
            return $this->countryShow($request, $destinationModel->slug);
        }

        $reviewsEnabled = $this->travelReviewsEnabled();

        return $this->renderTourListing(
            $request,
            $destinationModel->tours()->published(),
            [
                'breadcrumbs' => $this->tourHierarchyBreadcrumbs(
                    $destinationModel->scope,
                    $destinationModel->region,
                    $destinationModel,
                ),
                'canonical' => route('destinations.show', $destinationModel),
                'context_badge' => 'Điểm đến',
                'context_label' => $destinationModel->name,
                'fixed_destination' => $destinationModel,
                'page_content' => $destinationModel->content,
                'page_description' => $destinationModel->excerpt ?: $this->site->current()->site_description,
                'page_faq_items' => $destinationModel->faq_items ?? [],
                'page_intro' => 'Khám phá theo điểm đến',
                'page_rating_average' => filled($destinationModel->rating_average) ? (float) $destinationModel->rating_average : null,
                'page_rating_count' => filled($destinationModel->rating_count) ? (int) $destinationModel->rating_count : null,
                'page_reviews' => $reviewsEnabled ? ReviewContent::fromModels($destinationModel->publishedReviews) : [],
                'page_title' => $destinationModel->name,
                'show_tours_on_page' => (bool) ($destinationModel->show_tours_on_page ?? true),
                'show_blogs_on_page' => (bool) ($destinationModel->show_blogs_on_page ?? false),
                'show_voucher_cta' => true,
                'seo_description' => $destinationModel->meta_description ?: $destinationModel->excerpt ?: $this->site->current()->seo_description,
                'seo_title' => $destinationModel->meta_title ?: $destinationModel->name,
            ],
        );
    }

    public function legacyDestinationShow(string $destination): RedirectResponse
    {
        $destinationModel = $this->findPublishedDestinationBySlug($destination);

        abort_unless($destinationModel, 404);

        if ((bool) $destinationModel->is_country_root) {
            return redirect()->route('countries.show', ['slug' => $destinationModel->slug], 301);
        }

        return redirect()->route('destinations.show', $destinationModel, 301);
    }

    public function legacyCountryShow(string $slug): RedirectResponse
    {
        $countryQuery = Destination::query()
            ->published()
            ->countryRoots();
        $country = (clone $countryQuery)->where('slug', $slug)->first();
        $canonicalCountrySlug = Destination::countryRootSlug($slug);

        if (! $country && $canonicalCountrySlug !== $slug) {
            $country = (clone $countryQuery)->where('slug', $canonicalCountrySlug)->first();
        }

        abort_unless($country, 404);

        return redirect()->route('countries.show', ['slug' => $country->slug], 301);
    }

    public function regionShow(Request $request, Region $region): View
    {
        return $this->renderTourListing(
            $request,
            $region->tours()->published(),
            [
                'breadcrumbs' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => 'Vùng miền', 'url' => route('regions.show', $region)],
                    ['label' => $region->name],
                ],
                'canonical' => route('regions.show', $region),
                'context_badge' => 'Vùng miền',
                'context_label' => $region->name,
                'fixed_region' => $region,
                'page_content' => $region->content,
                'page_description' => $region->excerpt ?: $this->site->current()->site_description,
                'page_intro' => 'Khám phá theo vùng miền',
                'page_title' => $region->name,
                'seo_description' => $region->meta_description ?: $region->excerpt ?: $this->site->current()->seo_description,
                'seo_title' => $region->meta_title ?: $region->name,
            ],
        );
    }

    public function countryShow(Request $request, string $slug): RedirectResponse|View
    {
        $relations = ['media'];

        if ($this->travelReviewsEnabled()) {
            $relations['publishedReviews'] = fn ($reviewQuery) => $reviewQuery->limit(6);
        }

        $countryQuery = Destination::query()
            ->published()
            ->countryRoots()
            ->with($relations);
        $country = (clone $countryQuery)->where('slug', $slug)->first();
        $canonicalCountrySlug = Destination::countryRootSlug($slug);

        if (! $country && $canonicalCountrySlug !== $slug) {
            $country = (clone $countryQuery)->where('slug', $canonicalCountrySlug)->first();

            if ($country) {
                return redirect()->route('countries.show', ['slug' => $country->slug], 301);
            }
        }

        $country ??= $countryQuery->where('slug', $slug)->firstOrFail();
        $reviewsEnabled = $this->travelReviewsEnabled();
        $countryScope = TourScope::tryFrom((string) $country->scope);
        $breadcrumbs = array_values(array_filter([
            ['label' => 'Trang chủ', 'url' => route('home')],
            $countryScope ? ['label' => $countryScope->label(), 'url' => route($countryScope->routeName())] : null,
            ['label' => $country->name, 'url' => route('countries.show', ['slug' => $country->slug])],
        ]));

        return $this->renderTourListing(
            $request,
            Tour::query()->published(),
            [
                'breadcrumbs' => $breadcrumbs,
                'canonical' => route('countries.show', ['slug' => $country->slug]),
                'context_badge' => 'Quốc gia',
                'context_label' => $country->name,
                'fixed_country' => $country,
                'page_content' => $country->content,
                'page_description' => $country->excerpt ?: $this->site->current()->site_description,
                'page_faq_items' => $country->faq_items ?? [],
                'page_intro' => 'Khám phá theo quốc gia',
                'page_rating_average' => filled($country->rating_average) ? (float) $country->rating_average : null,
                'page_rating_count' => filled($country->rating_count) ? (int) $country->rating_count : null,
                'page_reviews' => $reviewsEnabled ? ReviewContent::fromModels($country->publishedReviews) : [],
                'page_title' => $country->name,
                'show_tours_on_page' => (bool) ($country->show_tours_on_page ?? true),
                'show_blogs_on_page' => (bool) ($country->show_blogs_on_page ?? false),
                'show_voucher_cta' => true,
                'seo_description' => $country->meta_description ?: $country->excerpt ?: $this->site->current()->seo_description,
                'seo_title' => $country->meta_title ?: 'Tour '.$country->name,
            ],
        );
    }

    public function blog(Request $request): RedirectResponse|View
    {
        $selectedCategorySlug = trim($request->string('category')->toString());

        if ($selectedCategorySlug !== '') {
            $category = ContentCategory::query()
                ->forTaxonomy('blog')
                ->where('slug', $selectedCategorySlug)
                ->firstOrFail();
            $query = collect($request->query())
                ->except('category')
                ->reject(fn ($value) => $value === null || $value === '')
                ->all();
            $target = route('blog-categories.show', ['slug' => $category->slug]);

            return redirect()->to($query === [] ? $target : $target.'?'.Arr::query($query), 301);
        }

        return $this->renderBlogListing($request);
    }

    public function blogCategoryShow(Request $request, string $slug): RedirectResponse|View
    {
        if ($request->query->has('category')) {
            $query = collect($request->query())
                ->except('category')
                ->reject(fn ($value) => $value === null || $value === '')
                ->all();
            $target = route('blog-categories.show', ['slug' => $slug]);

            return redirect()->to($query === [] ? $target : $target.'?'.Arr::query($query), 301);
        }

        $category = ContentCategory::query()
            ->forTaxonomy('blog')
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->renderBlogListing($request, $category);
    }

    protected function renderBlogListing(Request $request, ?ContentCategory $selectedBlogCategory = null): View
    {
        $landing = $this->landing('blog');
        $searchQuery = trim($request->string('q')->toString());
        $blogCategories = ContentCategory::query()
            ->forTaxonomy('blog')
            ->with(['media', 'parent.media'])
            ->withCount(['blogPosts as blog_posts_count' => fn (Builder $query) => $query->published()])
            ->ordered()
            ->get();
        $selectedBlogCategory = $selectedBlogCategory
            ? ($blogCategories->firstWhere('id', $selectedBlogCategory->getKey()) ?? $selectedBlogCategory->loadMissing(['media', 'parent.media']))
            : null;
        $selectedBlogCategoryIds = $selectedBlogCategory
            ? ContentCategoryTree::descendantIds($blogCategories, (int) $selectedBlogCategory->getKey())
            : [];
        $blogHasVariableFilters = $searchQuery !== '';
        $listingUrl = $selectedBlogCategory
            ? route('blog-categories.show', ['slug' => $selectedBlogCategory->slug])
            : route('blog.index');
        $pageTitle = $selectedBlogCategory?->name ?: ($landing?->title ?: 'Blog');
        $pageDescription = $selectedBlogCategory?->description
            ?: ($landing?->meta_description ?: $landing?->hero_excerpt ?: $landing?->intro_excerpt ?: $this->site->current()->seo_description);
        $posts = BlogPost::query()
            ->published()
            ->with(['category.parent', 'media', 'category.media', 'category.parent.media', 'countryDestination', 'destination.country'])
            ->when(
                $selectedBlogCategory,
                function (Builder $query) use ($selectedBlogCategoryIds): void {
                    $query->whereIn('content_category_id', $selectedBlogCategoryIds);
                }
            )
            ->when($searchQuery !== '', function (Builder $query) use ($searchQuery): void {
                [$likeQuery, $slugLikeQuery] = $this->frontsiteSearchLikeTerms($searchQuery);

                $query->where(function (Builder $blogQuery) use ($likeQuery, $slugLikeQuery): void {
                    $blogQuery
                        ->where('title', 'like', $likeQuery)
                        ->when($slugLikeQuery, fn (Builder $query, string $slugLikeQuery) => $query->orWhere('slug', 'like', $slugLikeQuery))
                        ->orWhereHas('category', function (Builder $categoryQuery) use ($likeQuery, $slugLikeQuery): void {
                            $categoryQuery
                                ->where('name', 'like', $likeQuery)
                                ->when($slugLikeQuery, fn (Builder $query, string $slugLikeQuery) => $query->orWhere('slug', 'like', $slugLikeQuery))
                                ->orWhereHas('parent', function (Builder $parentQuery) use ($likeQuery, $slugLikeQuery): void {
                                    $parentQuery
                                        ->where('name', 'like', $likeQuery)
                                        ->when($slugLikeQuery, fn (Builder $query, string $slugLikeQuery) => $query->orWhere('slug', 'like', $slugLikeQuery));
                                });
                        });
                });
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $blogAssociatedMedia = $this->blogListingAssociatedMediaSchema(
            $selectedBlogCategory,
            $landing,
            $posts->getCollection(),
        );

        return view($this->theme('pages.blog.index'), [
            'landing' => $landing,
            'landingHtmlWidgetBlocks' => $this->resolveLandingHtmlWidgetBlocks($landing),
            'blogSearchAction' => $listingUrl,
            'categoryTree' => ContentCategoryTree::applyBranchCounts($blogCategories),
            'posts' => $posts,
            'selectedBlogCategory' => $selectedBlogCategory,
            'selectedBlogCategoryRoot' => $selectedBlogCategory?->parent ?: $selectedBlogCategory,
            'selectedBlogCategoryTrail' => ContentCategoryTree::pathCategories($selectedBlogCategory),
            'geo' => $this->geoPresenter->forBlogListing($landing, $selectedBlogCategory, $posts),
            ...$this->landingVisuals($landing),
            'seo' => $this->seo([
                'title' => $selectedBlogCategory
                    ? $pageTitle.' | '.$this->site->current()->site_name
                    : ($landing?->meta_title ?: 'Blog | '.$this->site->current()->site_name),
                'description' => $pageDescription,
                'canonical' => $listingUrl,
                'og_image' => $this->blogListingImageUrl($selectedBlogCategory, $landing, FrontsiteMedia::SIZE_SMALL),
                'robots' => $blogHasVariableFilters ? 'noindex,follow' : 'index,follow',
                'schema' => $this->graphSchema([
                    $this->organizationSchema(),
                    $this->collectionPageSchema(
                        $listingUrl,
                        $pageTitle,
                        $pageDescription,
                        ['@id' => $listingUrl.'#blog-list'],
                        null,
                        $this->blogListingImageUrl($selectedBlogCategory, $landing),
                        null,
                        $blogAssociatedMedia,
                    ),
                    $this->blogItemListSchema(
                        $posts->getCollection(),
                        $listingUrl.'#blog-list',
                        $pageTitle,
                        $pageDescription,
                    ),
                    $this->faqSchema($selectedBlogCategory->faq_items ?? []),
                    $this->breadcrumbSchema([
                        ['name' => 'Trang chủ', 'url' => route('home')],
                        ['name' => $landing?->title ?: 'Blog', 'url' => route('blog.index')],
                        ...ContentCategoryTree::pathCategories($selectedBlogCategory)
                            ->map(fn (ContentCategory $category) => [
                                'name' => $category->name,
                                'url' => route('blog-categories.show', ['slug' => $category->slug]),
                            ])
                            ->all(),
                    ]),
                ]),
            ]),
        ]);
    }

    public function legacyBlogShow(BlogPost $post): RedirectResponse
    {
        abort_unless($this->isPublicBlogPost($post), 404);

        return redirect()->to(FrontsiteUrls::blogPost($post), 301);
    }

    public function blogShow(string $category, BlogPost $post): RedirectResponse|View
    {
        $post->loadMissing(['media', 'category.media', 'category.parent.media', 'countryDestination', 'destination.country']);

        abort_unless($this->isPublicBlogPost($post), 404);

        if ($category !== FrontsiteUrls::blogPostCategorySlug($post)) {
            return redirect()->to(FrontsiteUrls::blogPost($post), 301);
        }

        $blogCategoryTrail = ContentCategoryTree::pathCategories($post->category);
        $presentedPost = $this->blogPresenter->present($post);
        $faqSchema = $this->faqSchema($post->faq_items ?? []);
        $articleImage = $this->articleImageUrl($post, FrontsiteMedia::SIZE_SMALL);
        $articleHeroMedia = $this->articleResponsiveImageUrls($post);
        $articleUrl = FrontsiteUrls::canonicalBlogPost($post);
        $relatedPosts = collect();

        if ($post->category) {
            $allBlogCategories = ContentCategory::query()
                ->forTaxonomy('blog')
                ->with(['media', 'parent.media'])
                ->ordered()
                ->get();
            $branchRoot = $post->category->parent ?: $post->category;
            $branchIds = ContentCategoryTree::descendantIds($allBlogCategories, (int) $branchRoot->getKey());

            $relatedPosts = BlogPost::query()
                ->published()
                ->with(['category.parent', 'media', 'category.media', 'category.parent.media', 'countryDestination', 'destination.country'])
                ->whereKeyNot($post->getKey())
                ->whereIn('content_category_id', $branchIds)
                ->latest('published_at')
                ->limit(4)
                ->get();
        }

        return view($this->theme('pages.blog.show'), [
            'post' => $post,
            'blogCategoryTrail' => $blogCategoryTrail,
            'relatedPosts' => $relatedPosts,
            'geo' => $this->geoPresenter->forBlogPost($post),
            'renderedContent' => $presentedPost['renderedContent'],
            'tocItems' => $presentedPost['tocItems'],
            'articleHeroMedia' => $articleHeroMedia,
            'articleUrl' => $articleUrl,
            'seo' => $this->seo([
                'title' => $post->meta_title ?: $post->title,
                'description' => $post->meta_description ?: $post->excerpt ?: $this->site->current()->seo_description,
                'canonical' => $articleUrl,
                'og_image' => $articleImage,
                'schema' => $this->graphSchema([
                    $this->organizationSchema(),
                    $this->blogSchema(),
                    $this->blogArticleAuthorSchema($post),
                    $this->blogArticlePostingSchema($post, $blogCategoryTrail, $articleUrl, $presentedPost['tocItems'] !== []),
                    $faqSchema,
                    $this->breadcrumbSchema(
                        collect([
                            ['name' => 'Trang chủ', 'url' => route('home')],
                            ['name' => 'Blog', 'url' => route('blog.index')],
                        ])->concat(
                            $blogCategoryTrail->map(fn (ContentCategory $category) => [
                                'name' => $category->name,
                                'url' => route('blog-categories.show', ['slug' => $category->slug]),
                            ])
                        )->push([
                            'name' => $post->title,
                            'url' => $articleUrl,
                        ])->all()
                    ),
                ]),
            ]),
        ]);
    }

    public function landingShow(string $slug): View
    {
        $landing = LandingPage::query()
            ->whereNull('page_key')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        $landingEditorMode = $landing->isHtmlMode()
            ? LandingPage::EDITOR_MODE_HTML
            : LandingPage::EDITOR_MODE_BLOCKS;
        $landingContentBlocks = $landingEditorMode === LandingPage::EDITOR_MODE_HTML
            ? []
            : $this->resolveLandingContentBlocks($landing, includeVisualBlocks: true);
        $landingHasGeoAnswerBlock = collect($this->resolveLandingBlocks($landing))
            ->contains(fn (array $block): bool => ($block['type'] ?? null) === LandingPageBlocks::TYPE_GEO_ANSWER);
        $landingHtmlContent = $landingEditorMode === LandingPage::EDITOR_MODE_HTML
            ? (string) ($landing->body ?? '')
            : null;
        $landingUrl = FrontsiteUrls::canonicalUrl($landing->canonical_url ?: url('/'.$landing->slug));
        $voucherCampaign = $this->voucherCampaigns->activeForLanding($landing);

        return view($this->theme('pages.landing.show'), [
            'landing' => $landing,
            'landingContentBlocks' => $landingContentBlocks,
            'landingEditorMode' => $landingEditorMode,
            'landingHasGeoAnswerBlock' => $landingHasGeoAnswerBlock,
            'geo' => $landingHasGeoAnswerBlock ? ['is_enabled' => false] : $this->geoPresenter->forLanding($landing),
            'landingHtmlContent' => $landingHtmlContent,
            'voucherCampaign' => $voucherCampaign,
            'voucherCampaignSlug' => $voucherCampaign?->slug,
            ...$this->landingVisuals($landing),
            'seo' => $this->seo([
                'title' => $landing->meta_title ?: $landing->title,
                'description' => $this->landingSummaryText($landing) ?: $this->site->current()->seo_description,
                'canonical' => $landingUrl,
                'og_image' => $this->landingPrimaryImageUrl($landing, FrontsiteMedia::SIZE_SMALL),
                'schema' => $this->graphSchema(array_merge([
                    $this->organizationSchema(),
                ], $this->landingPageSchemaGraph($landing, $landingContentBlocks, $landingUrl), [
                    $this->breadcrumbSchema([
                        ['name' => 'Trang chủ', 'url' => route('home')],
                        ['name' => $landing->title, 'url' => $landingUrl],
                    ]),
                ])),
            ]),
        ]);
    }

    protected function landingSummaryText(LandingPage $landing): ?string
    {
        if (filled($landing->meta_description)) {
            return (string) $landing->meta_description;
        }

        if ($landing->isHtmlMode()) {
            $summary = trim(strip_tags((string) $landing->body));

            return $summary !== '' ? Str::limit($summary, 160, '') : null;
        }

        $summary = $landing->hero_excerpt ?: $landing->intro_excerpt;

        return filled($summary) ? (string) $summary : null;
    }

    protected function applyTourFilters(
        Builder|Relation $query,
        Request $request,
        array $fixedFilters = [],
        array $except = [],
    ): Builder|Relation {
        $searchQuery = trim($request->string('q')->toString());
        $selectedBudget = $request->string('budget')->toString();
        $selectedDepartureDate = $request->string('departure_date')->toString();
        $selectedTransport = $request->string('transport')->toString();
        $selectedCategory = $request->string('category')->toString();
        $selectedDestination = $request->string('destination')->toString();
        $selectedScope = $request->string('scope')->toString();

        if (($fixedFilters['category'] ?? null) instanceof TourCategory) {
            $query->whereHas('categories', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($fixedFilters['category']->getKey()));
        }

        if (($fixedFilters['destination'] ?? null) instanceof Destination) {
            $query->whereHas('destinations', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($fixedFilters['destination']->getKey()));
        }

        if (($fixedFilters['country'] ?? null) instanceof Destination) {
            $this->applyCountryDestinationFilter($query, $fixedFilters['country']);
        }

        if (($fixedFilters['region'] ?? null) instanceof Region) {
            $query->whereHas('regions', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($fixedFilters['region']->getKey()));
        }

        if ($searchQuery !== '' && ! in_array('q', $except, true)) {
            $query->where(function (Builder $searchBuilder) use ($searchQuery): void {
                [$likeQuery, $slugLikeQuery] = $this->frontsiteSearchLikeTerms($searchQuery);

                $searchBuilder
                    ->where('title', 'like', $likeQuery)
                    ->when($slugLikeQuery, fn (Builder $query, string $slugLikeQuery) => $query->orWhere('slug', 'like', $slugLikeQuery))
                    ->orWhereHas('primaryCategory', fn (Builder $category) => $this->applyFrontsiteNameOrSlugSearch($category, $likeQuery, $slugLikeQuery))
                    ->orWhereHas('categories', fn (Builder $category) => $this->applyFrontsiteNameOrSlugSearch($category, $likeQuery, $slugLikeQuery))
                    ->orWhereHas('destination', fn (Builder $destination) => $this->applyFrontsiteNameOrSlugSearch($destination, $likeQuery, $slugLikeQuery))
                    ->orWhereHas('destinations', fn (Builder $destination) => $this->applyFrontsiteNameOrSlugSearch($destination, $likeQuery, $slugLikeQuery))
                    ->orWhereHas('region', fn (Builder $region) => $this->applyFrontsiteNameOrSlugSearch($region, $likeQuery, $slugLikeQuery));
            });
        }

        if ($selectedCategory !== '' && ! in_array('category', $except, true) && ! isset($fixedFilters['category'])) {
            $query->whereHas('categories', fn (Builder $category) => $category->where('slug', $selectedCategory));
        }

        if ($selectedDestination !== '' && ! in_array('destination', $except, true) && ! isset($fixedFilters['destination'])) {
            $query->whereHas('destinations', fn (Builder $destination) => $destination->where('slug', $selectedDestination));
        }

        if ($selectedScope !== '' && ! in_array('scope', $except, true) && ($tourScope = TourScope::tryFrom($selectedScope))) {
            $query->forScope($tourScope);
        }

        if ($selectedTransport !== '' && ! in_array('transport', $except, true)) {
            $query->where(function (Builder $transportQuery) use ($selectedTransport): void {
                $transportQuery
                    ->where('transport', $selectedTransport)
                    ->orWhereHas('departures', fn (Builder $departure) => $departure->upcomingPublic()->where('transport_label', $selectedTransport));
            });
        }

        if ($selectedDepartureDate !== '' && ! in_array('departure_date', $except, true)) {
            $query->whereHas(
                'departures',
                fn (Builder $departure) => $departure->upcomingPublic()->whereDate('departure_date', $selectedDepartureDate)
            );
        }

        if ($selectedBudget !== '' && ! in_array('budget', $except, true)) {
            [$minBudget, $maxBudget] = $this->tourBudgetRange($selectedBudget);

            if ($minBudget !== null) {
                $query->where(function (Builder $priceQuery) use ($maxBudget, $minBudget): void {
                    $priceQuery
                        ->where(function (Builder $tourPrice) use ($maxBudget, $minBudget): void {
                            $this->applyBudgetAmountConstraint($tourPrice, ['sale_price', 'base_price'], $minBudget, $maxBudget);
                        })
                        ->orWhereHas('departures', function (Builder $departure) use ($maxBudget, $minBudget): void {
                            $departure
                                ->upcomingPublic()
                                ->where(function (Builder $departurePrice) use ($maxBudget, $minBudget): void {
                                    $this->applyBudgetAmountConstraint($departurePrice, ['sale_price', 'base_price'], $minBudget, $maxBudget);
                                });
                        });
                });
            }
        }

        return $query;
    }

    protected function applyCountryDestinationFilter(Builder|Relation $query, Destination $country): void
    {
        $query->where(function (Builder $tourQuery) use ($country): void {
            $tourQuery
                ->whereHas('destination', function (Builder $destinationQuery) use ($country): void {
                    $destinationQuery
                        ->whereKey($country->getKey())
                        ->orWhere('country_id', $country->getKey());
                })
                ->orWhereHas('destinations', function (Builder $destinationQuery) use ($country): void {
                    $destinationQuery
                        ->whereKey($country->getKey())
                        ->orWhere('country_id', $country->getKey());
                });
        });
    }

    protected function renderTourListing(Request $request, Builder|Relation $baseQuery, array $page): View
    {
        $fixedFilters = array_filter([
            'category' => data_get($page, 'fixed_category'),
            'country' => data_get($page, 'fixed_country'),
            'destination' => data_get($page, 'fixed_destination'),
            'region' => data_get($page, 'fixed_region'),
        ]);
        $selectedCategory = $request->string('category')->toString();
        $allowScopeFilter = (bool) data_get($page, 'allow_scope_filter', false);
        $selectedScope = $allowScopeFilter ? $request->string('scope')->toString() : '';
        $filterExcept = $allowScopeFilter ? [] : ['scope'];
        $showToursOnPage = (bool) data_get($page, 'show_tours_on_page', true);
        $showBlogsOnPage = (bool) data_get($page, 'show_blogs_on_page', false);
        $destinationBlogPosts = $this->destinationBlogPosts(
            data_get($page, 'fixed_destination') ?: data_get($page, 'fixed_country'),
            $showBlogsOnPage,
        );

        if ($showToursOnPage) {
            $tours = $this->applyTourFilters(
                (clone $baseQuery)->with([
                    'primaryCategory.media',
                    'destination.country',
                    'destination.media',
                    'region.media',
                    'departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date'),
                ]),
                $request,
                $fixedFilters,
                $filterExcept,
            )
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->paginate(12)
                ->withQueryString();
        } else {
            $tours = $this->emptyTourPaginator($request);
        }

        $listingUrl = data_get($page, 'canonical', url()->current());
        $searchQuery = $request->string('q')->toString();
        $searchCategoryOptions = collect(data_get($page, 'search_category_options', []));
        $hasVariableFilters = collect([
            $searchQuery !== '',
            $request->string('budget')->toString() !== '',
            $request->string('departure_date')->toString() !== '',
            $request->string('transport')->toString() !== '',
            ! isset($fixedFilters['category']) && $selectedCategory !== '',
            ! isset($fixedFilters['destination']) && $request->string('destination')->toString() !== '',
            $allowScopeFilter && $selectedScope !== '',
        ])->contains(true);
        $currentUrl = url()->current();
        $pageFaqItems = data_get($page, 'page_faq_items', []);
        $reviewsEnabled = $this->travelReviewsEnabled();
        $pageReviewItems = $reviewsEnabled ? ReviewContent::prepareItems(data_get($page, 'page_reviews')) : [];
        $pageReviewSummary = ReviewContent::aggregate(
            $pageReviewItems,
            data_get($page, 'page_rating_average'),
            data_get($page, 'page_rating_count'),
        );
        $fixedContextCards = collect([
            data_get($page, 'fixed_category') ? ['label' => 'Danh mục', 'value' => data_get($page, 'fixed_category.name'), 'url' => route('tour-categories.show', data_get($page, 'fixed_category'))] : null,
            data_get($page, 'fixed_country') ? ['label' => 'Quốc gia', 'value' => data_get($page, 'fixed_country.name'), 'url' => route('countries.show', ['slug' => data_get($page, 'fixed_country.slug')])] : null,
            data_get($page, 'fixed_destination') ? ['label' => 'Điểm đến', 'value' => data_get($page, 'fixed_destination.name'), 'url' => route('destinations.show', data_get($page, 'fixed_destination'))] : null,
            data_get($page, 'fixed_region') ? ['label' => 'Vùng miền', 'value' => data_get($page, 'fixed_region.name'), 'url' => route('regions.show', data_get($page, 'fixed_region'))] : null,
        ])->filter()->values();
        $countryClusterDestinations = $this->countryClusterDestinations(data_get($page, 'fixed_country'));
        $listingPrimaryEntityGraph = $this->listingPrimaryEntityGraph(
            $page,
            $listingUrl,
            $tours->getCollection(),
            $pageReviewItems,
            $pageReviewSummary,
        );
        $tourListId = $listingUrl.'#tour-list';
        $destinationBlogListId = $listingUrl.'#destination-blog-list';
        $tourListSchema = $showToursOnPage
            ? $this->tourItemListSchema(
                $tours->getCollection(),
                $tourListId,
                (string) data_get($page, 'page_title'),
                data_get($page, 'page_description'),
            )
            : null;
        $destinationBlogListSchema = $destinationBlogPosts->isNotEmpty()
            ? $this->blogItemListSchema(
                $destinationBlogPosts,
                $destinationBlogListId,
                'Cẩm nang du lịch '.data_get($page, 'page_title'),
                'Danh sách bài viết đang hiển thị trên trang điểm đến để hỗ trợ người đọc chuẩn bị hành trình.',
            )
            : null;
        $listingMainEntityRef = match (true) {
            $showToursOnPage => ['@id' => $tourListId],
            $destinationBlogListSchema !== null => ['@id' => $destinationBlogListId],
            default => null,
        };
        $pageSchemaNode = $this->shouldUseDetailedListingCollectionPage($page)
            ? $this->collectionPageSchema(
                $listingUrl,
                (string) data_get($page, 'page_title'),
                data_get($page, 'page_description'),
                $listingMainEntityRef,
                $listingPrimaryEntityGraph['ref'],
                $this->listingCollectionPageImageUrl($page),
            )
            : $this->schemaNode([
                '@type' => 'CollectionPage',
                'about' => $listingPrimaryEntityGraph['ref'],
                'description' => data_get($page, 'page_description'),
                'mainEntity' => $listingMainEntityRef,
                'name' => data_get($page, 'page_title'),
                'url' => $listingUrl,
            ]);
        $pageGallery = $this->listingPageGallery($page);
        $landingVisuals = $this->landingVisuals(data_get($page, 'landing'));
        $listingHero = $this->listingPageHeroFromGallery($page);
        $showTourVoucherCta = (bool) data_get($page, 'show_voucher_cta', false);


        if ($listingHero) {
            $landingVisuals['landingHero'] = $listingHero;
        }

        return view($this->theme('pages.tours.listing'), [
            'breadcrumbs' => data_get($page, 'breadcrumbs', []),
            'currentUrl' => $currentUrl,
            'filters' => [
                'category' => $selectedCategory,
                'q' => $searchQuery,
                'scope' => $selectedScope,
            ],
            'fixedContextCards' => $fixedContextCards,
            'countryClusterDestinations' => $countryClusterDestinations,
            'pageGallery' => $pageGallery,
            'pageGalleryDesktopVerticalThumbs' => $this->usesDesktopVerticalPageGalleryThumbs($page),
            'pageBadge' => data_get($page, 'context_badge'),
            'pageContent' => data_get($page, 'page_content'),
            'pageDescription' => data_get($page, 'page_description'),
            'pageFaqItems' => $pageFaqItems,
            'geo' => $this->geoPresenter->forTourListing($page, $tours),
            'pageIntro' => data_get($page, 'page_intro'),
            'pageReviewItems' => $pageReviewItems,
            'pageReviewSummary' => $pageReviewSummary,
            'searchSelectFields' => collect()
                ->when($searchCategoryOptions->isNotEmpty(), fn (Collection $fields) => $fields->push([
                    'icon' => 'fa-solid fa-shapes',
                    'name' => 'category',
                    'options' => $searchCategoryOptions->all(),
                    'placeholder' => 'Tất cả chủ đề',
                    'searchable' => false,
                    'value' => $selectedCategory,
                ]))
                ->when($allowScopeFilter, fn (Collection $fields) => $fields->push([
                    'icon' => 'fa-solid fa-map-location-dot',
                    'label' => 'Phân loại tour',
                    'name' => 'scope',
                    'options' => $this->tourScopeSearchOptions(),
                    'placeholder' => 'Tất cả',
                    'searchable' => false,
                    'value' => $selectedScope,
                ]))
                ->values()
                ->all(),
            'pageTitle' => data_get($page, 'page_title'),
            'showToursOnPage' => $showToursOnPage,
            'showBlogsOnPage' => $showBlogsOnPage,
            'destinationBlogPosts' => $destinationBlogPosts,
            'tours' => $tours,
            'tourVoucherCta' => $showToursOnPage && $showTourVoucherCta ? $this->tourVoucherCta() : null,
            'hideListingContextPanel' => (bool) data_get($page, 'hide_listing_context_panel', false),
            'landingConfiguredBlockTypes' => collect($this->resolveLandingBlocks(data_get($page, 'landing')))
                ->pluck('type')
                ->values()
                ->all(),
            'landingContentBlocks' => $this->resolveLandingContentBlocks(data_get($page, 'landing')),
            ...$landingVisuals,
            'seo' => $this->seo([
                'title' => data_get($page, 'seo_title'),
                'description' => data_get($page, 'seo_description'),
                'canonical' => $listingUrl,
                'og_image' => $this->listingCollectionPageImageUrl($page, FrontsiteMedia::SIZE_SMALL),
                'robots' => $hasVariableFilters ? 'noindex,follow' : data_get($page, 'robots_directive', 'index,follow'),
                'schema' => $this->graphSchema(array_filter(array_merge([
                    $this->organizationSchema(),
                    $pageSchemaNode,
                ], $listingPrimaryEntityGraph['nodes'], [
                    $tourListSchema,
                    $destinationBlogListSchema,
                    $this->faqSchema($pageFaqItems),
                    $this->breadcrumbSchema($this->breadcrumbSchemaItems(data_get($page, 'breadcrumbs', []))),
                ]))),
            ]),
        ]);
    }

    protected function emptyTourPaginator(Request $request): LengthAwarePaginator
    {
        return (new LengthAwarePaginator(
            collect(),
            0,
            12,
            1,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        ))->appends($request->query());
    }

    /**
     * @return Collection<int, BlogPost>
     */
    protected function destinationBlogPosts(mixed $destination, bool $showBlogsOnPage): Collection
    {
        if (! $showBlogsOnPage || ! $destination instanceof Destination) {
            return collect();
        }

        return BlogPost::query()
            ->published()
            ->with(['category.parent', 'media', 'category.media', 'category.parent.media', 'countryDestination', 'destination.country'])
            ->when(
                (bool) $destination->is_country_root,
                fn (Builder $query) => $query->where(function (Builder $blogQuery) use ($destination): void {
                    $blogQuery
                        ->where('country_destination_id', $destination->getKey())
                        ->orWhere('destination_id', $destination->getKey());
                }),
                fn (Builder $query) => $query->where('destination_id', $destination->getKey()),
            )
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    protected function isPublicBlogPost(BlogPost $post): bool
    {
        return $post->status === 'published'
            && ($post->published_at === null || $post->published_at->lte(now()));
    }

    /**
     * @return Collection<int, Destination>
     */
    protected function countryClusterDestinations(mixed $country): Collection
    {
        if (! $country instanceof Destination) {
            return collect();
        }

        return $country->childDestinations()
            ->published()
            ->regularDestinations()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function tourVoucherCta(): ?array
    {
        $campaign = $this->voucherCampaigns->activeForTourVoucherCta();

        if (! $campaign) {
            return null;
        }

        $landing = $campaign->landingPage;
        $url = $landing && blank($landing->page_key) && $landing->is_active
            ? url('/'.$landing->slug)
            : url('/voucher-du-lich');

        return [
            'campaign' => $campaign,
            'url' => $url,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function listingPageGallery(array $page): array
    {
        $category = data_get($page, 'fixed_category');
        $country = data_get($page, 'fixed_country');

        if ($category instanceof TourCategory) {
            $category->loadMissing('media');

            return FrontsiteGalleryData::taxonomy($category, 'category', $category->name, $category->cover_alt, false, true);
        }

        if ($country instanceof Destination) {
            $country->loadMissing('media');

            return FrontsiteGalleryData::taxonomy($country, 'country', $country->name, $country->cover_alt, true, true);
        }

        $destination = data_get($page, 'fixed_destination');

        if ($destination instanceof Destination) {
            $destination->loadMissing('media');

            return FrontsiteGalleryData::taxonomy($destination, 'destination', $destination->name, $destination->cover_alt, true, true);
        }

        return [];
    }

    protected function listingPageHeroFromGallery(array $page): ?array
    {
        $category = data_get($page, 'fixed_category');
        $country = data_get($page, 'fixed_country');
        $destination = data_get($page, 'fixed_destination');
        $region = data_get($page, 'fixed_region');
        $gallery = [];

        if ($destination instanceof Destination) {
            $destination->loadMissing('media');

            return [
                'autoplay_delay' => null,
                'enabled' => true,
                'managed' => true,
                'media_alt' => trim((string) ($destination->cover_alt ?: $destination->name)),
                'media_medium_url' => FrontsiteMedia::taxonomyAvatarUrl($destination, FrontsiteMedia::SIZE_MEDIUM) ?: '',
                'media_small_url' => FrontsiteMedia::taxonomyAvatarUrl($destination, FrontsiteMedia::SIZE_SMALL) ?: '',
                'media_url' => FrontsiteMedia::taxonomyAvatarUrl($destination, FrontsiteMedia::SIZE_FULL) ?: '',
                'mode' => LandingPageVisuals::SOURCE_MEDIA,
                'slides' => [],
            ];
        }

        if ($category instanceof TourCategory) {
            $category->loadMissing('media');
            $gallery = FrontsiteGalleryData::taxonomy($category, 'category', $category->name, $category->cover_alt, true, true);
        } elseif ($country instanceof Destination) {
            $country->loadMissing('media');
            $gallery = FrontsiteGalleryData::taxonomy($country, 'country', $country->name, $country->cover_alt, true, true);
        } elseif ($region instanceof Region) {
            $region->loadMissing('media');
            $gallery = FrontsiteGalleryData::taxonomy($region, 'region', $region->name, $region->cover_alt, true, true);
        }

        $slides = collect($gallery)
            ->map(fn (array $item): ?array => $this->listingPageHeroSlideFromGalleryItem($item, $page))
            ->filter()
            ->values()
            ->all();

        if ($slides === []) {
            return null;
        }

        return [
            'autoplay_delay' => 5500,
            'enabled' => true,
            'managed' => true,
            'media_alt' => '',
            'media_medium_url' => '',
            'media_small_url' => '',
            'media_url' => '',
            'mode' => LandingPageVisuals::SOURCE_SLIDER,
            'slides' => $slides,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function listingPageHeroSlideFromGalleryItem(array $item, array $page): ?array
    {
        $kind = trim((string) ($item['lightbox_kind'] ?? $item['type'] ?? ContentGallery::TYPE_IMAGE));
        $isYoutube = $kind === ContentGallery::TYPE_YOUTUBE;
        $isMp4 = $kind === ContentGallery::TYPE_MP4;
        $isVideo = $isYoutube || $isMp4;
        $pageTitle = trim((string) data_get($page, 'page_title'));
        $pageDescription = trim((string) data_get($page, 'page_description'));
        $thumbnailUrl = $isMp4
            ? ''
            : trim((string) (($item['thumbnail_image'] ?? '') ?: ($item['thumbnail_url'] ?? '') ?: ($item['small_image_url'] ?? '')));
        $imageUrl = $isMp4
            ? ''
            : trim((string) (($item['stage_src'] ?? '') ?: ($item['image_url'] ?? '') ?: ($item['full_image_url'] ?? '') ?: $thumbnailUrl));
        $fullImageUrl = $isMp4
            ? ''
            : trim((string) (($item['lightbox_src'] ?? '') ?: ($item['full_image_url'] ?? '') ?: $imageUrl));
        $embedUrl = $isYoutube ? trim((string) ($item['embed_url'] ?? '')) : '';
        $videoUrl = $isMp4
            ? trim((string) (($item['lightbox_src'] ?? '') ?: ($item['stage_src'] ?? '') ?: ($item['video_url'] ?? '')))
            : '';

        if (! $isYoutube && ! $isMp4 && $imageUrl === '') {
            return null;
        }

        if ($isYoutube && $embedUrl === '' && $thumbnailUrl === '') {
            return null;
        }

        if ($isMp4 && $videoUrl === '') {
            return null;
        }

        return [
            'description' => trim((string) ($item['resolved_description'] ?? '')) ?: $pageDescription,
            'effect' => 'animate__fadeInUp',
            'embed_url' => $embedUrl,
            'full_image_url' => $fullImageUrl,
            'full_inner_image_url' => '',
            'full_mobile_image_url' => $fullImageUrl,
            'image_alt' => trim((string) ($item['resolved_alt'] ?? '')) ?: $pageTitle,
            'image_url' => $imageUrl,
            'inner_image_url' => '',
            'mobile_image_url' => $imageUrl,
            'primary_label' => 'Gửi yêu cầu',
            'primary_url' => route('contact'),
            'prefer_video_background' => $isVideo,
            'secondary_label' => '',
            'secondary_url' => '',
            'show_inner_media' => false,
            'show_overlay' => true,
            'thumbnail_url' => $thumbnailUrl,
            'title' => trim((string) ($item['resolved_title'] ?? '')) ?: $pageTitle,
            'video_kind' => $isMp4 ? ContentGallery::TYPE_MP4 : ($isYoutube ? ContentGallery::TYPE_YOUTUBE : null),
            'video_url' => $videoUrl,
        ];
    }

    protected function usesDesktopVerticalPageGalleryThumbs(array $page): bool
    {
        return data_get($page, 'fixed_category') instanceof TourCategory
            || data_get($page, 'fixed_country') instanceof Destination
            || data_get($page, 'fixed_destination') instanceof Destination;
    }

    protected function resolveLandingBlocks(?LandingPage $landing): array
    {
        if (! $landing) {
            return [];
        }

        return LandingPageBlocks::normalize($landing->blocks ?? []);
    }

    /**
     * @return array<string, bool>
     */
    protected function homeBlockVisibility(?LandingPage $landing, array $homeConfig): array
    {
        return [
            'search' => $this->homeConfigSectionEnabled($homeConfig, 'search'),
            'geo_answer' => $this->homeConfigSectionEnabled($homeConfig, 'geo_answer'),
            'topic_rail' => $this->homeConfigSectionEnabled($homeConfig, 'topic_rail'),
            'featured_tours' => $this->homeConfigSectionEnabled($homeConfig, 'featured_tours'),
            'tour_taxonomy_tabs' => $this->homeConfigSectionEnabled($homeConfig, 'tour_taxonomy_tabs'),
            'region_taxonomy_tabs' => $this->homeConfigSectionEnabled($homeConfig, 'region_taxonomy_tabs'),
            'destination_slider' => $this->homeConfigSectionEnabled($homeConfig, 'destination_slider'),
            'gallery' => $this->homeConfigSectionEnabled($homeConfig, 'gallery'),
            'services' => $this->homeConfigSectionEnabled($homeConfig, 'services'),
            'trust' => $this->homeConfigSectionEnabled($homeConfig, 'trust'),
            'process' => $this->homeConfigSectionEnabled($homeConfig, 'process'),
            'blog_preview' => $this->homeConfigSectionEnabled($homeConfig, 'blog_preview'),
            'faq' => $this->homeConfigSectionEnabled($homeConfig, 'faq')
                && $this->homeLandingBlockEnabled($landing, LandingPageBlocks::TYPE_FAQ),
            'cta' => $this->homeConfigSectionEnabled($homeConfig, 'cta')
                && $this->homeLandingBlockEnabled($landing, LandingPageBlocks::TYPE_CTA),
        ];
    }

    protected function homeConfigSectionEnabled(array $homeConfig, string $key): bool
    {
        return (bool) data_get($homeConfig, $key.'.is_enabled', true);
    }

    protected function travelReviewsEnabled(): bool
    {
        return (bool) config('travel_reviews.enabled', true);
    }

    protected function homeLandingBlockEnabled(?LandingPage $landing, string $type): bool
    {
        $blocks = collect($this->resolveLandingBlocks($landing))
            ->filter(fn (array $block) => ($block['type'] ?? null) === $type);

        return $blocks->isEmpty()
            || $blocks->contains(fn (array $block) => (bool) ($block['is_enabled'] ?? true));
    }

    protected function renderServiceListing(Request $request, ?ContentCategory $selectedServiceCategory = null): View
    {
        $landing = $this->landing('services');
        $listingUrl = $selectedServiceCategory
            ? route('service-categories.show', ['category' => $selectedServiceCategory->slug])
            : route('services.index');
        $searchQuery = trim($request->string('q')->toString());
        $hasVariableFilters = $searchQuery !== '';
        $serviceCategories = ContentCategory::query()
            ->forTaxonomy('service')
            ->whereHas('services', fn ($query) => $query->published())
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description']);
        $services = Service::query()
            ->published()
            ->with('category')
            ->when($selectedServiceCategory, fn ($query) => $query->where('content_category_id', $selectedServiceCategory->getKey()))
            ->when($searchQuery !== '', function (Builder $query) use ($searchQuery): void {
                [$likeQuery, $slugLikeQuery] = $this->frontsiteSearchLikeTerms($searchQuery);

                $query->where(function (Builder $serviceQuery) use ($likeQuery, $slugLikeQuery): void {
                    $serviceQuery
                        ->where('title', 'like', $likeQuery)
                        ->when($slugLikeQuery, fn (Builder $query, string $slugLikeQuery) => $query->orWhere('slug', 'like', $slugLikeQuery))
                        ->orWhereHas('category', fn (Builder $categoryQuery) => $this->applyFrontsiteNameOrSlugSearch($categoryQuery, $likeQuery, $slugLikeQuery));
                });
            })
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->get();
        $pageTitle = $selectedServiceCategory?->name ?: ($landing?->hero_title ?: $landing?->title ?: 'Dịch vụ');
        $pageDescription = $selectedServiceCategory?->description ?: ($landing?->hero_excerpt ?: $landing?->intro_excerpt ?: $this->site->current()->seo_description);

        return view($this->theme('pages.services.index'), [
            'landing' => $landing,
            'landingHtmlWidgetBlocks' => $this->resolveLandingHtmlWidgetBlocks($landing),
            'selectedServiceCategory' => $selectedServiceCategory,
            'serviceCategories' => $serviceCategories,
            'services' => $services,
            'geo' => $this->geoPresenter->forServiceListing($landing, $selectedServiceCategory, $services, $serviceCategories),
            ...$this->landingVisuals($landing),
            'seo' => $this->seo([
                'title' => $selectedServiceCategory
                    ? ($pageTitle.' | '.$this->site->current()->site_name)
                    : ($landing?->meta_title ?: ($pageTitle.' | '.$this->site->current()->site_name)),
                'description' => $selectedServiceCategory?->description
                    ?: ($landing?->meta_description ?: $pageDescription),
                'canonical' => $listingUrl,
                'og_image' => $this->serviceListingImageUrl($selectedServiceCategory, $landing, FrontsiteMedia::SIZE_SMALL),
                'robots' => $hasVariableFilters ? 'noindex,follow' : 'index,follow',
                'schema' => $this->graphSchema([
                    $this->organizationSchema(),
                    $this->collectionPageSchema(
                        $listingUrl,
                        $pageTitle,
                        $pageDescription,
                        ['@id' => $listingUrl.'#service-list'],
                        null,
                        $this->serviceListingImageUrl($selectedServiceCategory, $landing),
                    ),
                    $this->serviceItemListSchema($services, $listingUrl.'#service-list', $pageTitle, $pageDescription),
                    $this->breadcrumbSchema([
                        ['name' => 'Trang chủ', 'url' => route('home')],
                        ['name' => $landing?->title ?: 'Dịch vụ', 'url' => route('services.index')],
                        ...($selectedServiceCategory ? [['name' => $selectedServiceCategory->name, 'url' => $listingUrl]] : []),
                    ]),
                ]),
            ]),
            ]);
    }

    protected function frontsiteSearchLikeTerms(string $searchQuery): array
    {
        $likeQuery = '%'.$searchQuery.'%';
        $slugQuery = Str::slug($searchQuery);

        return [
            $likeQuery,
            $slugQuery !== '' ? '%'.$slugQuery.'%' : null,
        ];
    }

    protected function applyFrontsiteNameOrSlugSearch(Builder $query, string $likeQuery, ?string $slugLikeQuery): void
    {
        $query
            ->where('name', 'like', $likeQuery)
            ->when($slugLikeQuery, fn (Builder $query, string $slugLikeQuery) => $query->orWhere('slug', 'like', $slugLikeQuery));
    }

    protected function resolveLandingContentBlocks(?LandingPage $landing, bool $includeVisualBlocks = false): array
    {
        if (! $landing || $landing->isHtmlMode()) {
            return [];
        }

        return collect($this->resolveLandingBlocks($landing))
            ->filter(fn (array $block) => (bool) ($block['is_enabled'] ?? true))
            ->when(! $includeVisualBlocks, fn (Collection $blocks) => $blocks->reject(fn (array $block) => in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_HERO_SLIDER,
                LandingPageBlocks::TYPE_HERO_MEDIA,
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
                LandingPageBlocks::TYPE_GALLERY_SLIDER,
                LandingPageBlocks::TYPE_GALLERY_MEDIA,
            ], true)))
            ->map(function (array $block) use ($landing): array {
                return match ($block['type'] ?? null) {
                    LandingPageBlocks::TYPE_HERO_SLIDER, LandingPageBlocks::TYPE_HERO_MEDIA => array_merge($block, [
                        'rendered_hero' => $this->resolveLandingHeroBlock($landing, $block),
                    ]),
                    LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE => array_merge($block, $this->resolveLandingHeroDemoBlock($landing, $block)),
                    LandingPageBlocks::TYPE_GALLERY_SLIDER, LandingPageBlocks::TYPE_GALLERY_MEDIA => array_merge($block, [
                        'rendered_gallery' => $this->resolveLandingGalleryBlock($landing, $block),
                    ]),
                    LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS => $this->resolveRegionTaxonomyTabsBlock($block),
                    LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS => $this->resolveTourTaxonomyTabsBlock($block),
                    LandingPageBlocks::TYPE_GEO_ANSWER => array_merge($block, [
                        'geo' => $this->geoPresenter->forLandingBlock($block, $landing),
                    ]),
                    LandingPageBlocks::TYPE_TOUR_LIST => array_merge($block, [
                        'items' => $this->queryLandingTours($block),
                    ]),
                    LandingPageBlocks::TYPE_BLOG_LIST => array_merge($block, [
                        'items' => $this->queryLandingBlogs($block),
                    ]),
                    LandingPageBlocks::TYPE_REGION_RAIL => array_merge($block, [
                        'items' => $this->queryLandingRegions($block),
                    ]),
                    LandingPageBlocks::TYPE_TOPIC_RAIL => array_merge($block, [
                        'items' => $this->queryLandingTourCategories($block),
                    ]),
                    LandingPageBlocks::TYPE_HTML_WIDGET => array_merge($block, [
                        'rendered_html' => (string) ($block['html'] ?? ''),
                    ]),
                    LandingPageBlocks::TYPE_RICH_TEXT => array_merge($block, [
                        'rendered_body' => RichText::render((string) ($block['body'] ?? '')),
                    ]),
                    default => $block,
                };
            })
            ->values()
            ->all();
    }

    protected function resolveLandingHtmlWidgetBlocks(?LandingPage $landing): array
    {
        if (! $landing || $landing->isHtmlMode()) {
            return [];
        }

        return collect($this->resolveLandingBlocks($landing))
            ->filter(fn (array $block) => (bool) ($block['is_enabled'] ?? true))
            ->filter(fn (array $block) => ($block['type'] ?? null) === LandingPageBlocks::TYPE_HTML_WIDGET)
            ->map(fn (array $block) => array_merge($block, [
                'rendered_html' => (string) ($block['html'] ?? ''),
            ]))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function resolveHomeSlotBlocks(?LandingPage $landing): array
    {
        if (! $landing || $landing->isHtmlMode()) {
            return [];
        }

        return collect($this->resolveLandingContentBlocks($landing, true))
            ->filter(fn (array $block) => (bool) ($block['is_enabled'] ?? true))
            ->reject(fn (array $block) => in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_HERO_SLIDER,
                LandingPageBlocks::TYPE_HERO_MEDIA,
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
            ], true))
            ->filter(fn (array $block) => LandingPageBlocks::homePositionForBlock($block) !== LandingPageBlocks::HOME_POSITION_DEFAULT)
            ->groupBy(fn (array $block) => LandingPageBlocks::homePositionForBlock($block))
            ->map(fn (Collection $blocks) => $blocks->values()->all())
            ->all();
    }

    protected function queryLandingBlogs(array $block): Collection
    {
        $limit = FrontsiteCardGrid::normalizeLimit($block['limit'] ?? null);
        $query = BlogPost::query()
            ->published()
            ->with(['category.parent', 'media', 'category.media', 'category.parent.media', 'countryDestination', 'destination.country']);

        if (filled($block['category_slug'] ?? null)) {
            $query->whereHas('category', fn (Builder $category) => $category->where('slug', $block['category_slug']));
        }

        if ((bool) ($block['featured'] ?? false)) {
            $query->where('is_featured', true);
        }

        match ((string) ($block['sort'] ?? 'latest')) {
            'oldest' => $query->oldest('published_at'),
            'featured' => $query->orderByDesc('is_featured')->latest('published_at'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            default => $query->latest('published_at'),
        };

        return $query
            ->limit($limit)
            ->get();
    }

    protected function resolveTourTaxonomyTabsBlock(array $block): array
    {
        $block['limit'] = FrontsiteCardGrid::normalizeLimit(
            $block['limit'] ?? null,
            LandingPageBlocks::TOUR_TAXONOMY_TABS_DEFAULT_LIMIT,
        );
        $tabs = collect($this->queryLandingTourTaxonomyTabs($block))->values();
        $defaultTabId = (string) ($tabs->first(fn (array $tab) => collect($tab['items'] ?? [])->isNotEmpty())['uuid'] ?? $tabs->first()['uuid'] ?? '');

        return array_merge($block, [
            'default_tab_id' => $defaultTabId,
            'tabs' => $tabs->all(),
        ]);
    }

    protected function resolveRegionTaxonomyTabsBlock(array $block): array
    {
        $block['card_source_type'] = array_key_exists(
            (string) ($block['card_source_type'] ?? ''),
            LandingPageBlocks::regionTaxonomyCardTypes(),
        )
            ? (string) $block['card_source_type']
            : 'destination';
        $block['limit'] = min(8, FrontsiteCardGrid::normalizeLimit(
            $block['limit'] ?? null,
            LandingPageBlocks::REGION_TAXONOMY_TABS_DEFAULT_LIMIT,
        ));
        $block['tab_limit'] = min(8, FrontsiteCardGrid::normalizeLimit(
            $block['tab_limit'] ?? null,
            LandingPageBlocks::REGION_TAXONOMY_TABS_DEFAULT_TAB_LIMIT,
        ));
        $tabs = collect($this->queryLandingRegionTaxonomyTabs($block))->values();
        $defaultTabId = (string) ($tabs->first(fn (array $tab) => collect($tab['items'] ?? [])->isNotEmpty())['uuid'] ?? $tabs->first()['uuid'] ?? '');

        return array_merge($block, [
            'default_tab_id' => $defaultTabId,
            'tabs' => $tabs->all(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function queryLandingTourTaxonomyTabs(array $block): array
    {
        $scope = filled($block['scope'] ?? null)
            ? TourScope::tryFrom((string) $block['scope'])
            : null;

        return collect($block['tabs'] ?? [])
            ->filter(fn ($tab) => is_array($tab) && filled($tab['source_slug'] ?? null))
            ->map(function (array $tab) use ($block, $scope): ?array {
                $sourceType = (string) ($tab['source_type'] ?? 'region');
                $sourceSlug = trim((string) ($tab['source_slug'] ?? ''));

                if ($sourceSlug === '' || ! array_key_exists($sourceType, LandingPageBlocks::tourTaxonomyTabTypes())) {
                    return null;
                }

                $source = match ($sourceType) {
                    'destination' => Destination::query()
                        ->published()
                        ->regularDestinations()
                        ->where('slug', $sourceSlug)
                        ->whereHas('tours', function (Builder $tourQuery) use ($scope): void {
                            $tourQuery->published();

                            if ($scope) {
                                $tourQuery->forScope($scope);
                            }
                        })
                        ->first(),
                    'tour_category' => TourCategory::query()
                        ->published()
                        ->where('slug', $sourceSlug)
                        ->whereHas('tours', function (Builder $tourQuery) use ($scope): void {
                            $tourQuery->published();

                            if ($scope) {
                                $tourQuery->forScope($scope);
                            }
                        })
                        ->first(),
                    default => Region::query()
                        ->published()
                        ->where('slug', $sourceSlug)
                        ->whereHas('tours', function (Builder $tourQuery) use ($scope): void {
                            $tourQuery->published();

                            if ($scope) {
                                $tourQuery->forScope($scope);
                            }
                        })
                        ->first(),
                };

                if (! $source) {
                    return null;
                }

                $items = $this->queryLandingTours(match ($sourceType) {
                    'destination' => array_merge($block, ['destination_slug' => $sourceSlug, 'region_slug' => '', 'category_slug' => '']),
                    'tour_category' => array_merge($block, ['category_slug' => $sourceSlug, 'destination_slug' => '', 'region_slug' => '']),
                    default => array_merge($block, ['region_slug' => $sourceSlug, 'category_slug' => '', 'destination_slug' => '']),
                });

                $sourceName = trim((string) data_get($source, 'name'));
                $description = trim((string) ($tab['description'] ?? ''));
                $sourceDescription = RichText::normalizePlain((string) data_get($source, 'excerpt'));

                return array_merge($tab, [
                    'description' => $description !== '' ? $description : ($sourceDescription !== '' ? $sourceDescription : $this->tourTaxonomyTabFallbackDescription($sourceType, $sourceName)),
                    'items' => $items,
                    'label' => trim((string) ($tab['label'] ?? '')) ?: $sourceName,
                    'source_label' => LandingPageBlocks::tourTaxonomyTabTypes()[$sourceType] ?? $sourceType,
                    'title' => trim((string) ($tab['title'] ?? '')) ?: $sourceName,
                    'url' => match ($sourceType) {
                        'destination' => route('destinations.show', $source),
                        'tour_category' => route('tour-categories.show', $source),
                        default => route('regions.show', $source),
                    },
                ]);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function queryLandingRegionTaxonomyTabs(array $block): array
    {
        $scope = filled($block['scope'] ?? null)
            ? TourScope::tryFrom((string) $block['scope'])
            : null;
        $cardSourceType = array_key_exists(
            (string) ($block['card_source_type'] ?? ''),
            LandingPageBlocks::regionTaxonomyCardTypes(),
        )
            ? (string) $block['card_source_type']
            : 'destination';
        $regions = Region::query()
            ->published()
            ->when($scope, fn (Builder $query) => $query->where('scope', $scope->value))
            ->when((bool) ($block['featured'] ?? false), fn (Builder $query) => $query->where('is_featured', true))
            ->where(function (Builder $regionQuery) use ($cardSourceType, $scope): void {
                if ($cardSourceType === 'tour_category') {
                    $regionQuery->whereHas('tours', function (Builder $tourQuery) use ($scope): void {
                        $tourQuery
                            ->published()
                            ->when($scope, fn (Builder $query) => $query->forScope($scope))
                            ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->published());
                    });

                    return;
                }

                $regionQuery
                    ->whereHas('destinations', function (Builder $destinationQuery) use ($scope): void {
                        $destinationQuery
                            ->published()
                            ->whereHas('tours', function (Builder $tourQuery) use ($scope): void {
                                $tourQuery
                                    ->published()
                                    ->when($scope, fn (Builder $query) => $query->forScope($scope));
                            });
                    })
                    ->orWhereHas('tours', function (Builder $tourQuery) use ($scope): void {
                        $tourQuery
                            ->published()
                            ->when($scope, fn (Builder $query) => $query->forScope($scope))
                            ->whereHas('destinations', fn (Builder $destinationQuery) => $destinationQuery->published()->countryRoots());
                    });
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit((int) ($block['tab_limit'] ?? LandingPageBlocks::REGION_TAXONOMY_TABS_DEFAULT_TAB_LIMIT))
            ->get();

        return $regions
            ->map(function (Region $region) use ($block, $cardSourceType, $scope): ?array {
                $items = $this->queryLandingRegionTaxonomyCards($region, $block, $scope, $cardSourceType);

                if ($items->isEmpty()) {
                    return null;
                }

                $regionName = trim((string) $region->name);
                $description = RichText::normalizePlain((string) $region->excerpt);

                return [
                    'description' => $description !== '' ? $description : $this->regionTaxonomyTabFallbackDescription($cardSourceType, $regionName),
                    'items' => $items,
                    'label' => $regionName,
                    'source_label' => LandingPageBlocks::tourTaxonomyTabTypes()['region'],
                    'title' => $regionName,
                    'url' => route('regions.show', $region),
                    'uuid' => 'region-taxonomy-tab-'.$region->getKey(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function queryLandingRegionTaxonomyCards(
        Region $region,
        array $block,
        ?TourScope $scope,
        string $cardSourceType,
    ): Collection {
        $limit = min(8, FrontsiteCardGrid::normalizeLimit(
            $block['limit'] ?? null,
            LandingPageBlocks::REGION_TAXONOMY_TABS_DEFAULT_LIMIT,
        ));
        $fetchLimit = min(24, max($limit, $limit * 3));

        if ($cardSourceType === 'tour_category') {
            return $this->prioritizeTaxonomyItemsWithImages(
                TourCategory::query()
                ->published()
                ->with('media')
                ->whereHas('tours', function (Builder $tourQuery) use ($region, $scope): void {
                    $tourQuery
                        ->published()
                        ->when($scope, fn (Builder $query) => $query->forScope($scope))
                        ->whereHas('regions', fn (Builder $regionQuery) => $regionQuery->whereKey($region->getKey()));
                })
                ->withCount([
                    'tours' => function (Builder $tourQuery) use ($region, $scope): void {
                        $tourQuery
                            ->published()
                            ->when($scope, fn (Builder $query) => $query->forScope($scope))
                            ->whereHas('regions', fn (Builder $regionQuery) => $regionQuery->whereKey($region->getKey()));
                    },
                ])
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit($fetchLimit)
                ->get(),
                $limit,
            );
        }

        return $this->prioritizeTaxonomyItemsWithImages(
            Destination::query()
                ->published()
                ->with('media')
                ->where(function (Builder $destinationQuery) use ($region, $scope): void {
                    $destinationQuery
                        ->where(function (Builder $regularDestination) use ($region, $scope): void {
                            $regularDestination
                                ->regularDestinations()
                                ->where('region_id', $region->getKey())
                                ->whereHas('tours', function (Builder $tourQuery) use ($region, $scope): void {
                                    $this->applyRegionScopedPublishedTourConstraint($tourQuery, $region, $scope);
                                });
                        })
                        ->orWhere(function (Builder $countryRoot) use ($region, $scope): void {
                            $countryRoot
                                ->countryRoots()
                                ->where(function (Builder $countryRootTourContext) use ($region, $scope): void {
                                    $countryRootTourContext
                                        ->whereHas('tours', function (Builder $tourQuery) use ($region, $scope): void {
                                            $this->applyRegionScopedPublishedTourConstraint($tourQuery, $region, $scope);
                                        })
                                        ->orWhereHas('childDestinations', function (Builder $childDestinationQuery) use ($region, $scope): void {
                                            $childDestinationQuery
                                                ->published()
                                                ->where('region_id', $region->getKey())
                                                ->whereHas('tours', function (Builder $tourQuery) use ($region, $scope): void {
                                                    $this->applyRegionScopedPublishedTourConstraint($tourQuery, $region, $scope);
                                                });
                                        });
                                });
                        });
                })
                ->withCount([
                    'tours' => function (Builder $tourQuery) use ($region, $scope): void {
                        $this->applyRegionScopedPublishedTourConstraint($tourQuery, $region, $scope);
                    },
                ])
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit($fetchLimit)
                ->get(),
            $limit,
        );
    }

    protected function applyRegionScopedPublishedTourConstraint(Builder $tourQuery, Region $region, ?TourScope $scope): void
    {
        $tourQuery
            ->published()
            ->when($scope, fn (Builder $query) => $query->forScope($scope))
            ->whereHas('regions', fn (Builder $regionQuery) => $regionQuery->whereKey($region->getKey()));
    }

    protected function resolveLandingHeroDemoBlock(LandingPage $landing, array $block): array
    {
        $limit = FrontsiteCardGrid::normalizeLimit($block['limit'] ?? null, 3);
        $baseBlock = array_merge($block, ['limit' => $limit]);
        $scopeTours = collect(TourScope::cases())
            ->mapWithKeys(fn (TourScope $scope) => [
                $scope->value => $this->queryLandingTours(array_merge($baseBlock, ['scope' => $scope->value])),
            ]);
        $allTours = $scopeTours
            ->flatMap(fn (Collection $tours) => $tours)
            ->unique(fn (Tour $tour) => $tour->getKey())
            ->values();
        $preferredScope = filled($block['scope'] ?? null)
            ? TourScope::tryFrom((string) $block['scope'])
            : null;
        $preferredTours = $preferredScope
            ? ($scopeTours->get($preferredScope->value) ?? collect())
            : $allTours;
        $heroTour = $preferredTours->first() ?: $allTours->first();

        return [
            'hero_tour' => $heroTour,
            'items' => $allTours->take(FrontsiteCardGrid::MAX_ITEMS)->values(),
            'scope_cards' => collect(TourScope::cases())
                ->map(fn (TourScope $scope) => [
                    'count' => ($scopeTours->get($scope->value) ?? collect())->count(),
                    'label' => 'Xem '.mb_strtolower($scope->label()),
                    'url' => route($scope->routeName()),
                ])
                ->values()
                ->all(),
            ...$this->resolveLandingHeroDemoMedia($landing, $block),
        ];
    }

    protected function resolveLandingHeroDemoVisual(LandingPage $landing, array $heroBlock): array
    {
        $heroDemo = $this->resolveLandingHeroDemoBlock($landing, $heroBlock);
        $heroTour = data_get($heroDemo, 'hero_tour');
        $blockHeroMedia = data_get($heroDemo, 'hero_cover_media');
        $heroMedia = [
            FrontsiteMedia::SIZE_FULL => null,
            FrontsiteMedia::SIZE_MEDIUM => null,
            FrontsiteMedia::SIZE_SMALL => null,
        ];

        if (is_array($blockHeroMedia) && collect($blockHeroMedia)->filter()->isNotEmpty()) {
            $heroMedia = $blockHeroMedia;
        } elseif ($heroTour instanceof Tour) {
            $heroMedia = FrontsiteMedia::responsiveUrls($heroTour, 'cover', 'cover_image_url');
        }

        $mediaUrl = $heroMedia[FrontsiteMedia::SIZE_FULL] ?: '';

        return [
            'autoplay_delay' => null,
            'enabled' => true,
            'hero_tour' => $heroTour,
            'managed' => true,
            'media_alt' => trim((string) data_get($heroDemo, 'hero_cover_alt'))
                ?: ($heroTour instanceof Tour ? trim((string) ($heroTour->cover_alt ?: $heroTour->title)) : trim((string) ($heroBlock['title'] ?? $landing->title))),
            'media_medium_url' => $heroMedia[FrontsiteMedia::SIZE_MEDIUM] ?: '',
            'media_small_url' => $heroMedia[FrontsiteMedia::SIZE_SMALL] ?: '',
            'media_url' => $mediaUrl,
            'mode' => $mediaUrl !== '' ? LandingPageVisuals::SOURCE_MEDIA : 'default',
            'slides' => [],
        ];
    }

    protected function resolveHomeHeroDemoBlock(?LandingPage $landing): ?array
    {
        if (! $landing || $landing->isHtmlMode()) {
            return null;
        }

        $block = collect(LandingPageBlocks::normalize($landing->blocks ?? []))
            ->first(fn (array $block) => ($block['type'] ?? null) === LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE
                && (bool) ($block['is_enabled'] ?? true));

        return is_array($block)
            ? array_merge($block, $this->resolveLandingHeroDemoMedia($landing, $block))
            : null;
    }

    protected function resolveLandingHeroDemoMedia(LandingPage $landing, array $heroBlock): array
    {
        $collection = LandingPageBlocks::mediaCollection((string) ($heroBlock['uuid'] ?? ''));
        $heroMedia = FrontsiteMedia::responsiveUrls($landing, $collection, null);

        if (collect($heroMedia)->filter()->isEmpty()) {
            return [
                'hero_cover_alt' => '',
                'hero_cover_media' => [],
            ];
        }

        return [
            'hero_cover_alt' => trim((string) ($heroBlock['media_alt'] ?? $heroBlock['title'] ?? $landing->title)),
            'hero_cover_media' => $heroMedia,
        ];
    }

    protected function queryLandingTours(array $block): Collection
    {
        $limit = FrontsiteCardGrid::normalizeLimit($block['limit'] ?? null);
        $query = Tour::query()
            ->published()
            ->with([
                'departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date'),
                'destination.media',
                'media',
                'primaryCategory.media',
                'region.media',
            ]);

        if (filled($block['category_slug'] ?? null)) {
            $query->whereHas('categories', fn (Builder $category) => $category->where('slug', $block['category_slug']));
        }

        if (filled($block['destination_slug'] ?? null)) {
            $query->whereHas('destinations', fn (Builder $destination) => $destination->where('slug', $block['destination_slug']));
        }

        if (filled($block['region_slug'] ?? null)) {
            $query->whereHas('regions', fn (Builder $region) => $region->where('slug', $block['region_slug']));
        }

        if (filled($block['scope'] ?? null) && ($scope = TourScope::tryFrom((string) $block['scope']))) {
            $query->forScope($scope);
        }

        if ((bool) ($block['featured'] ?? false)) {
            $query->where('is_featured', true);
        }

        match ((string) ($block['sort'] ?? 'featured')) {
            'latest' => $query->latest('published_at'),
            'price_asc' => $query->orderByRaw('coalesce(sale_price, base_price, 999999999) asc'),
            'price_desc' => $query->orderByRaw('coalesce(sale_price, base_price, 0) desc'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->latest('updated_at'),
        };

        return $query
            ->limit($limit)
            ->get();
    }

    protected function queryLandingRegions(array $block): Collection
    {
        $limit = min(8, FrontsiteCardGrid::normalizeLimit($block['limit'] ?? null, 8));
        $query = Region::query()
            ->published()
            ->with('media')
            ->whereHas('tours', fn (Builder $tourQuery) => $tourQuery->published())
            ->withCount(['tours' => fn (Builder $tourQuery) => $tourQuery->published()]);

        if (filled($block['scope'] ?? null) && ($scope = TourScope::tryFrom((string) $block['scope']))) {
            $query->where('scope', $scope->value);
        }

        if ((bool) ($block['featured'] ?? false)) {
            $query->where('is_featured', true);
        }

        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    protected function queryLandingTourCategories(array $block): Collection
    {
        $limit = min(8, FrontsiteCardGrid::normalizeLimit($block['limit'] ?? null, 8));

        return TourCategory::query()
            ->published()
            ->where('is_featured', true)
            ->with('media')
            ->withCount(['tours' => fn (Builder $tourQuery) => $tourQuery->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    protected function tourListingSearchCategories(TourScope $scope): array
    {
        return TourCategory::query()
            ->published()
            ->whereHas('tours', fn (Builder $tourQuery) => $tourQuery->published()->forScope($scope))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->mapWithKeys(fn (TourCategory $category) => [$category->slug => $category->name])
            ->all();
    }

    protected function tourScopeSearchOptions(): array
    {
        return collect(TourScope::cases())
            ->mapWithKeys(fn (TourScope $scope) => [$scope->value => $scope->label()])
            ->all();
    }

    protected function tourTaxonomyTabFallbackDescription(string $sourceType, string $sourceName): string
    {
        return match ($sourceType) {
            'destination' => 'Ưu tiên các tour đi '.$sourceName.' đang có hành trình hoạt động để bạn theo dõi ngày khởi hành, thời lượng và mức giá thuận tiện hơn.',
            'tour_category' => 'Tập hợp các tour thuộc chủ đề '.$sourceName.' đang có hành trình hoạt động để bạn so sánh nhanh trước khi chốt nhu cầu phù hợp.',
            default => 'Tập trung các tour theo vùng '.$sourceName.' để bạn khoanh nhanh khu vực phù hợp trước khi đi sâu vào từng điểm đến cụ thể.',
        };
    }

    protected function regionTaxonomyTabFallbackDescription(string $cardSourceType, string $regionName): string
    {
        return match ($cardSourceType) {
            'tour_category' => 'Chọn '.$regionName.' để xem nhanh các chủ đề tour đang có hành trình hoạt động trong khu vực này trước khi đi sâu vào từng chương trình cụ thể.',
            default => 'Chọn '.$regionName.' để mở nhanh các điểm đến đang có tour hoạt động và khoanh vùng hướng đi phù hợp hơn cho hành trình của bạn.',
        };
    }

    protected function resolveHomeRegionTaxonomyTabsBlock(?LandingPage $landing): ?array
    {
        $configuredBlock = collect($this->resolveLandingBlocks($landing))
            ->first(fn (array $block) => ($block['type'] ?? null) === LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS);

        if (is_array($configuredBlock)) {
            if (! (bool) ($configuredBlock['is_enabled'] ?? true)) {
                return null;
            }

            $resolvedBlock = $this->resolveRegionTaxonomyTabsBlock($configuredBlock);

            return collect($resolvedBlock['tabs'] ?? [])->isNotEmpty() ? $resolvedBlock : null;
        }

        $resolvedBlock = $this->resolveRegionTaxonomyTabsBlock(
            LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS),
        );

        return collect($resolvedBlock['tabs'] ?? [])->isNotEmpty() ? $resolvedBlock : null;
    }

    protected function resolveHomeTourTaxonomyTabsBlock(?LandingPage $landing): ?array
    {
        $configuredBlock = collect($this->resolveLandingBlocks($landing))
            ->first(fn (array $block) => ($block['type'] ?? null) === LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);

        if (is_array($configuredBlock)) {
            if (! (bool) ($configuredBlock['is_enabled'] ?? true)) {
                return null;
            }

            $resolvedBlock = $this->resolveTourTaxonomyTabsBlock($configuredBlock);

            return collect($resolvedBlock['tabs'] ?? [])->isNotEmpty() ? $resolvedBlock : null;
        }

        $fallbackBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $fallbackBlock['tabs'] = $this->defaultHomeTourTaxonomyTabs();
        $resolvedBlock = $this->resolveTourTaxonomyTabsBlock($fallbackBlock);

        return collect($resolvedBlock['tabs'] ?? [])->isNotEmpty() ? $resolvedBlock : null;
    }

    protected function landingVisuals(?LandingPage $landing): array
    {
        return [
            'landingGallery' => $this->resolveLandingGallery($landing),
            'landingHero' => $this->resolveLandingHero($landing),
        ];
    }

    protected function resolveLandingGallery(?LandingPage $landing): array
    {
        if ($landing?->isHtmlMode()) {
            return [
                'enabled' => false,
                'items' => [],
                'managed' => false,
                'mode' => LandingPageVisuals::SOURCE_NONE,
            ];
        }

        $galleryBlocks = collect($this->resolveLandingBlocks($landing))
            ->filter(fn (array $block) => in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_GALLERY_SLIDER,
                LandingPageBlocks::TYPE_GALLERY_MEDIA,
            ], true));
        $galleryBlock = $galleryBlocks
            ->first(fn (array $block) => (bool) ($block['is_enabled'] ?? true));

        if ($galleryBlocks->isNotEmpty() && ! is_array($galleryBlock)) {
            return [
                'description' => '',
                'enabled' => false,
                'eyebrow' => '',
                'items' => [],
                'managed' => true,
                'mode' => LandingPageVisuals::SOURCE_NONE,
                'title' => '',
                'uuid' => '',
                'variant' => LandingPageBlocks::GALLERY_VARIANT_STANDARD,
            ];
        }

        if ($landing && is_array($galleryBlock)) {
            return $this->resolveLandingGalleryBlock($landing, $galleryBlock);
        }

        $config = LandingPageVisuals::prepare($landing?->visual_config);
        $galleryEnabled = (bool) data_get($config, 'gallery.enabled');
        $gallerySource = (string) data_get($config, 'gallery.source', LandingPageVisuals::SOURCE_NONE);
        $galleryItems = [];
        $slider = null;

        if ($landing && $galleryEnabled && $gallerySource === LandingPageVisuals::SOURCE_SLIDER) {
            $slider = $this->resolveLandingSlider($landing, 'gallery', data_get($config, 'gallery.slider_id'));
            $galleryItems = $this->resolveSliderGalleryItems($slider);
        }

        if ($landing && $galleryEnabled && $gallerySource === LandingPageVisuals::SOURCE_MEDIA) {
            $galleryItems = collect(data_get($config, 'gallery.items', []))
                ->map(function (array $item) use ($landing): array {
                    $collection = LandingPageVisuals::galleryCollection((string) $item['uuid']);
                    $fullImageUrl = FrontsiteMedia::modelUrl($landing, $collection, FrontsiteMedia::SIZE_FULL, null);
                    $imageUrl = FrontsiteMedia::modelUrl($landing, $collection, FrontsiteMedia::SIZE_SMALL, null);

                    if (! $fullImageUrl) {
                        $fullImageUrl = trim((string) ($item['image_url'] ?? '')) ?: null;
                    }

                    $imageUrl = $imageUrl ?: $fullImageUrl;

                    return [
                        'description' => '',
                        'embed_url' => null,
                        'full_image_url' => $fullImageUrl ?: null,
                        'image_alt' => trim((string) ($item['image_alt'] ?? $item['title'] ?? $landing->title)),
                        'image_url' => $imageUrl ?: null,
                        'kind' => 'image',
                        'subtitle' => trim((string) ($item['subtitle'] ?? '')),
                        'tab_label' => trim((string) ($item['tab_label'] ?? '')),
                        'thumbnail_url' => $imageUrl ?: $fullImageUrl ?: null,
                        'tile_size' => trim((string) ($item['tile_size'] ?? LandingPageBlocks::GALLERY_TILE_STANDARD)),
                        'title' => trim((string) ($item['title'] ?? '')),
                        'url' => trim((string) ($item['url'] ?? '')),
                        'video_url' => null,
                    ];
                })
                ->filter(fn (array $item) => filled($item['image_url']))
                ->values()
                ->all();
        }

        return [
            'description' => trim((string) data_get($config, 'gallery.description')),
            'enabled' => $galleryEnabled,
            'eyebrow' => trim((string) data_get($config, 'gallery.eyebrow')),
            'items' => $galleryItems,
            'managed' => false,
            'mode' => $gallerySource,
            'title' => trim((string) data_get($config, 'gallery.title')),
            'uuid' => '',
            'variant' => LandingPageBlocks::GALLERY_VARIANT_STANDARD,
        ];
    }

    protected function resolveLandingGalleryBlock(LandingPage $landing, array $galleryBlock): array
    {
        if (($galleryBlock['type'] ?? null) === LandingPageBlocks::TYPE_GALLERY_SLIDER) {
            $slider = $this->resolveLandingSlider($landing, 'gallery', data_get($galleryBlock, 'slider_id'));

            return [
                'description' => trim((string) ($galleryBlock['description'] ?? '')),
                'enabled' => true,
                'eyebrow' => trim((string) ($galleryBlock['eyebrow'] ?? '')),
                'items' => $this->resolveSliderGalleryItems($slider),
                'managed' => true,
                'mode' => LandingPageVisuals::SOURCE_SLIDER,
                'title' => trim((string) ($galleryBlock['title'] ?? '')),
                'uuid' => (string) ($galleryBlock['uuid'] ?? ''),
                'variant' => LandingPageBlocks::GALLERY_VARIANT_STANDARD,
            ];
        }

        $items = collect($galleryBlock['items'] ?? [])
            ->map(function (array $item) use ($landing, $galleryBlock): array {
                $collection = LandingPageBlocks::galleryItemCollection(
                    (string) ($galleryBlock['uuid'] ?? ''),
                    (string) ($item['uuid'] ?? ''),
                );
                $fullImageUrl = FrontsiteMedia::modelUrl($landing, $collection, FrontsiteMedia::SIZE_FULL, null);
                $imageUrl = FrontsiteMedia::modelUrl($landing, $collection, FrontsiteMedia::SIZE_SMALL, null);

                if (! $fullImageUrl) {
                    $fullImageUrl = trim((string) ($item['image_url'] ?? '')) ?: null;
                }

                $imageUrl = $imageUrl ?: $fullImageUrl;

                return [
                    'description' => trim((string) ($item['description'] ?? '')),
                    'embed_url' => null,
                    'full_image_url' => $fullImageUrl ?: null,
                    'image_alt' => trim((string) ($item['image_alt'] ?? $item['title'] ?? $landing->title)),
                    'image_url' => $imageUrl ?: null,
                    'kind' => 'image',
                    'subtitle' => trim((string) ($item['subtitle'] ?? '')),
                    'tab_label' => trim((string) ($item['tab_label'] ?? '')),
                    'thumbnail_url' => $imageUrl ?: $fullImageUrl ?: null,
                    'tile_size' => trim((string) ($item['tile_size'] ?? LandingPageBlocks::GALLERY_TILE_STANDARD)),
                    'title' => trim((string) ($item['title'] ?? '')),
                    'url' => trim((string) ($item['url'] ?? '')),
                    'video_url' => null,
                ];
            })
            ->filter(fn (array $item) => filled($item['image_url']))
            ->values()
            ->all();

        return [
            'description' => trim((string) ($galleryBlock['description'] ?? '')),
            'enabled' => $items !== [],
            'eyebrow' => trim((string) ($galleryBlock['eyebrow'] ?? '')),
            'items' => $items,
            'managed' => true,
            'mode' => LandingPageVisuals::SOURCE_MEDIA,
            'title' => trim((string) ($galleryBlock['title'] ?? '')),
            'uuid' => (string) ($galleryBlock['uuid'] ?? ''),
            'variant' => trim((string) ($galleryBlock['variant'] ?? LandingPageBlocks::GALLERY_VARIANT_STANDARD)),
        ];
    }

    protected function resolveLandingHero(?LandingPage $landing): array
    {
        if ($landing?->isHtmlMode()) {
            return [
                'enabled' => false,
                'managed' => false,
                'media_medium_url' => '',
                'media_small_url' => '',
                'media_url' => '',
                'mode' => LandingPageVisuals::SOURCE_NONE,
                'slides' => [],
            ];
        }

        $heroBlocks = collect($this->resolveLandingBlocks($landing))
            ->filter(fn (array $block) => in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_HERO_SLIDER,
                LandingPageBlocks::TYPE_HERO_MEDIA,
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
            ], true));
        $heroBlock = $heroBlocks
            ->first(fn (array $block) => (bool) ($block['is_enabled'] ?? true));

        if ($heroBlocks->isNotEmpty() && ! is_array($heroBlock)) {
            return [
                'autoplay_delay' => null,
                'enabled' => false,
                'managed' => true,
                'media_alt' => '',
                'media_medium_url' => '',
                'media_small_url' => '',
                'media_url' => '',
                'mode' => LandingPageVisuals::SOURCE_NONE,
                'slides' => [],
            ];
        }

        if ($landing && is_array($heroBlock) && ($heroBlock['type'] ?? null) === LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE) {
            return $this->resolveLandingHeroDemoVisual($landing, $heroBlock);
        }

        if ($landing && is_array($heroBlock)) {
            return $this->resolveLandingHeroBlock($landing, $heroBlock);
        }

        $config = LandingPageVisuals::prepare($landing?->visual_config);
        $heroEnabled = (bool) data_get($config, 'hero.enabled');
        $heroSource = (string) data_get($config, 'hero.source', LandingPageVisuals::SOURCE_NONE);
        $slider = null;
        $slides = [];

        if ($landing && $heroEnabled && $heroSource === LandingPageVisuals::SOURCE_SLIDER) {
            $slider = $this->resolveLandingSlider($landing, 'hero', data_get($config, 'hero.slider_id'));
            $slides = $this->resolveSliderHeroSlides($slider, $landing);
        }

        if ($slides !== []) {
            return [
                'autoplay_delay' => $slider?->autoplay_delay ?: 5500,
                'enabled' => true,
                'managed' => false,
                'media_alt' => '',
                'media_medium_url' => '',
                'media_small_url' => '',
                'media_url' => '',
                'mode' => LandingPageVisuals::SOURCE_SLIDER,
                'slides' => $slides,
            ];
        }

        $heroMedia = $landing && $heroEnabled && $heroSource === LandingPageVisuals::SOURCE_MEDIA
            ? FrontsiteMedia::responsiveUrls($landing, LandingPageVisuals::heroCollection(), null)
            : [
                FrontsiteMedia::SIZE_FULL => null,
                FrontsiteMedia::SIZE_MEDIUM => null,
                FrontsiteMedia::SIZE_SMALL => null,
            ];

        return [
            'autoplay_delay' => null,
            'enabled' => $heroEnabled,
            'managed' => false,
            'media_alt' => trim((string) data_get($config, 'hero.media_alt', $landing?->hero_title ?: $landing?->title)),
            'media_medium_url' => $heroMedia[FrontsiteMedia::SIZE_MEDIUM] ?: '',
            'media_small_url' => $heroMedia[FrontsiteMedia::SIZE_SMALL] ?: '',
            'media_url' => $heroMedia[FrontsiteMedia::SIZE_FULL] ?: '',
            'mode' => $heroMedia[FrontsiteMedia::SIZE_FULL] ? LandingPageVisuals::SOURCE_MEDIA : 'default',
            'slides' => [],
        ];
    }

    protected function resolveLandingHeroBlock(LandingPage $landing, array $heroBlock): array
    {
        if (($heroBlock['type'] ?? null) === LandingPageBlocks::TYPE_HERO_SLIDER) {
            $slider = $this->resolveLandingSlider($landing, 'hero', data_get($heroBlock, 'slider_id'));
            $slides = $this->resolveSliderHeroSlides($slider, $landing, $heroBlock);

            if ($slides !== []) {
                return [
                    'autoplay_delay' => $slider?->autoplay_delay ?: 5500,
                    'enabled' => true,
                    'managed' => true,
                    'media_alt' => '',
                    'media_medium_url' => '',
                    'media_small_url' => '',
                    'media_url' => '',
                    'mode' => LandingPageVisuals::SOURCE_SLIDER,
                    'slides' => $slides,
                ];
            }
        }

        $collection = LandingPageBlocks::mediaCollection((string) ($heroBlock['uuid'] ?? ''));
        $heroMedia = FrontsiteMedia::responsiveUrls($landing, $collection, null);

        return [
            'autoplay_delay' => null,
            'enabled' => true,
            'managed' => true,
            'media_alt' => trim((string) ($heroBlock['media_alt'] ?? $heroBlock['title'] ?? $landing->title)),
            'media_medium_url' => $heroMedia[FrontsiteMedia::SIZE_MEDIUM] ?: '',
            'media_small_url' => $heroMedia[FrontsiteMedia::SIZE_SMALL] ?: '',
            'media_url' => $heroMedia[FrontsiteMedia::SIZE_FULL] ?: '',
            'mode' => $heroMedia[FrontsiteMedia::SIZE_FULL] ? LandingPageVisuals::SOURCE_MEDIA : 'default',
            'slides' => [],
        ];
    }

    protected function resolveLandingSlider(?LandingPage $landing, string $slot, mixed $sliderId): ?Slider
    {
        if (! $landing) {
            return null;
        }

        $explicitSliderId = is_numeric($sliderId) ? (int) $sliderId : null;
        $baseQuery = Slider::query()
            ->where('is_active', true)
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('order')]);

        if ($explicitSliderId) {
            $explicitSlider = (clone $baseQuery)->whereKey($explicitSliderId)->first();

            if ($explicitSlider && $explicitSlider->items->isNotEmpty()) {
                return $explicitSlider;
            }
        }

        return (clone $baseQuery)
            ->where('location', $this->landingSliderLocation($landing, $slot))
            ->first();
    }

    protected function resolveHomePopupSlider(?LandingPage $landing): ?array
    {
        $slider = Slider::query()
            ->where('is_active', true)
            ->where('location', SliderLocations::HOME_POPUP)
            ->whereHas('items', fn (Builder $query) => $query->where('is_active', true))
            ->with(['items' => fn ($query) => $query
                ->where('is_active', true)
                ->with('media')
                ->orderBy('order')])
            ->latest('updated_at')
            ->latest('id')
            ->first();

        $slides = $this->resolveSliderHeroSlides($slider, $landing);

        if ($slides === []) {
            return null;
        }

        $sliderVersion = $slider?->updated_at?->timestamp ?? 0;
        $itemVersion = $slider?->items
            ->map(fn (SliderItem $item) => $item->updated_at?->timestamp ?? 0)
            ->max() ?? 0;

        return [
            'autoplay_delay' => $slider?->autoplay_delay,
            'id' => $slider?->getKey(),
            'slides' => $slides,
            'version' => ($slider?->getKey() ?: 'home-popup').'-'.max($sliderVersion, $itemVersion),
        ];
    }

    protected function resolveSliderGalleryItems(?Slider $slider): array
    {
        if (! $slider) {
            return [];
        }

        return $slider->items
            ->map(fn (SliderItem $item) => $this->resolveSliderGalleryItem($item))
            ->filter(fn (array $item) => filled($item['image_url'])
                || filled($item['mobile_image_url'])
                || filled($item['inner_image_url'])
                || filled($item['thumbnail_url'])
                || filled($item['embed_url'])
                || filled($item['video_url']))
            ->values()
            ->all();
    }

    protected function resolveSliderGalleryItem(SliderItem $item): array
    {
        $videoPayload = $this->resolveSliderVideoPayload($item->video_url);
        $desktopMedia = FrontsiteMedia::responsiveUrls($item, 'image', null);
        $mobileMedia = FrontsiteMedia::responsiveUrls($item, 'mobile_image', null);
        $innerMedia = FrontsiteMedia::responsiveUrls($item, 'inner_image', null);
        $thumbnailUrl = $videoPayload['thumbnail_url']
            ?: $innerMedia[FrontsiteMedia::SIZE_SMALL]
            ?: $desktopMedia[FrontsiteMedia::SIZE_SMALL]
            ?: $mobileMedia[FrontsiteMedia::SIZE_SMALL];

        return [
            'description' => trim((string) $item->description),
            'effect' => trim((string) ($item->effect ?: 'animate__fadeInUp')),
            'embed_url' => $videoPayload['embed_url'],
            'full_image_url' => $desktopMedia[FrontsiteMedia::SIZE_FULL],
            'full_inner_image_url' => $innerMedia[FrontsiteMedia::SIZE_FULL],
            'full_mobile_image_url' => $mobileMedia[FrontsiteMedia::SIZE_FULL],
            'image_alt' => trim((string) ($item->image_alt ?: $item->title ?: 'Slider item')),
            'image_url' => $desktopMedia[FrontsiteMedia::SIZE_MEDIUM],
            'inner_image_url' => $innerMedia[FrontsiteMedia::SIZE_MEDIUM],
            'kind' => $videoPayload['kind'] ?: 'image',
            'mobile_image_url' => $mobileMedia[FrontsiteMedia::SIZE_MEDIUM],
            'show_overlay' => (bool) $item->show_overlay,
            'subtitle' => trim((string) $item->subtitle),
            'thumbnail_url' => $thumbnailUrl,
            'title' => trim((string) $item->title),
            'url' => trim((string) ($item->image_link ?: $item->primary_url ?: $item->cta_url ?: '')),
            'video_url' => $videoPayload['video_url'],
        ];
    }

    protected function resolveSliderHeroSlides(?Slider $slider, ?LandingPage $landing, ?array $fallbackBlock = null): array
    {
        if (! $slider) {
            return [];
        }

        return $slider->items
            ->map(function (SliderItem $item) use ($landing, $fallbackBlock): array {
                $videoPayload = $this->resolveSliderVideoPayload($item->video_url);
                $desktopMedia = FrontsiteMedia::responsiveUrls($item, 'image', null);
                $mobileMedia = FrontsiteMedia::responsiveUrls($item, 'mobile_image', null);
                $innerMedia = FrontsiteMedia::responsiveUrls($item, 'inner_image', null);
                $thumbnailUrl = $videoPayload['thumbnail_url']
                    ?: $desktopMedia[FrontsiteMedia::SIZE_SMALL]
                    ?: $mobileMedia[FrontsiteMedia::SIZE_SMALL]
                    ?: $desktopMedia[FrontsiteMedia::SIZE_MEDIUM]
                    ?: $desktopMedia[FrontsiteMedia::SIZE_FULL];

                return [
                    'description' => trim((string) $item->description),
                    'effect' => trim((string) ($item->effect ?: 'animate__fadeInUp')),
                    'embed_url' => $videoPayload['embed_url'],
                    'image_alt' => trim((string) ($item->image_alt ?: $item->title ?: data_get($fallbackBlock, 'title') ?: $landing?->hero_title ?: $landing?->title)),
                    'image_url' => $desktopMedia[FrontsiteMedia::SIZE_MEDIUM],
                    'full_image_url' => $desktopMedia[FrontsiteMedia::SIZE_FULL],
                    'inner_image_url' => $innerMedia[FrontsiteMedia::SIZE_MEDIUM],
                    'full_inner_image_url' => $innerMedia[FrontsiteMedia::SIZE_FULL],
                    'mobile_image_url' => $mobileMedia[FrontsiteMedia::SIZE_MEDIUM],
                    'full_mobile_image_url' => $mobileMedia[FrontsiteMedia::SIZE_FULL],
                    'og_image_url' => $desktopMedia[FrontsiteMedia::SIZE_SMALL] ?: $mobileMedia[FrontsiteMedia::SIZE_SMALL] ?: $thumbnailUrl,
                    'primary_label' => trim((string) ($item->primary_label ?: $item->cta_label ?: '')),
                    'primary_url' => trim((string) ($item->primary_url ?: $item->cta_url ?: '')),
                    'secondary_label' => trim((string) ($item->secondary_label ?: '')),
                    'secondary_url' => trim((string) ($item->secondary_url ?: '')),
                    'show_inner_media' => (bool) $item->show_inner_media,
                    'show_overlay' => (bool) $item->show_overlay,
                    'subtitle' => trim((string) $item->subtitle),
                    'thumbnail_url' => $thumbnailUrl,
                    'title' => trim((string) $item->title),
                    'video_kind' => $videoPayload['kind'],
                    'video_url' => $videoPayload['video_url'],
                ];
            })
            ->filter(fn (array $slide) => filled($slide['title'])
                || filled($slide['subtitle'])
                || filled($slide['description'])
                || filled($slide['image_url'])
                || filled($slide['mobile_image_url'])
                || ((bool) ($slide['show_inner_media'] ?? true) && filled($slide['inner_image_url']))
                || filled($slide['thumbnail_url'])
                || filled($slide['embed_url'])
                || filled($slide['video_url'])
                || (filled($slide['primary_label']) && filled($slide['primary_url']))
                || (filled($slide['secondary_label']) && filled($slide['secondary_url'])))
            ->values()
            ->all();
    }

    protected function landingSliderLocation(LandingPage $landing, string $slot): string
    {
        if (filled($landing->page_key)) {
            return LandingPageVisuals::sliderLocation($landing->page_key, $slot);
        }

        return Str::slug((string) ($landing->slug ?: $landing->title ?: 'landing')).'-'.$slot;
    }

    protected function resolveSliderVideoPayload(?string $value): array
    {
        $url = trim((string) $value);

        if ($url === '') {
            return [
                'embed_url' => null,
                'kind' => null,
                'thumbnail_url' => null,
                'video_url' => null,
            ];
        }

        $youtubeId = $this->youtubeId($url);

        if ($youtubeId) {
            return [
                'embed_url' => 'https://www.youtube.com/embed/'.$youtubeId.'?rel=0',
                'kind' => 'youtube',
                'thumbnail_url' => 'https://i.ytimg.com/vi/'.$youtubeId.'/hqdefault.jpg',
                'video_url' => $url,
            ];
        }

        return [
            'embed_url' => null,
            'kind' => Str::endsWith(Str::lower($url), '.mp4') ? 'mp4' : 'mp4',
            'thumbnail_url' => null,
            'video_url' => $url,
        ];
    }

    protected function youtubeId(string $value): ?string
    {
        $parts = parse_url($value);
        $host = Str::lower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($host === 'youtu.be' && $path !== '') {
            $candidate = Str::before($path, '/');

            return preg_match('/^[A-Za-z0-9_-]{6,}$/', $candidate) ? $candidate : null;
        }

        if (! str_contains($host, 'youtube.com')) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        if (! empty($query['v']) && preg_match('/^[A-Za-z0-9_-]{6,}$/', (string) $query['v'])) {
            return (string) $query['v'];
        }

        foreach (['embed/', 'shorts/', 'live/'] as $segment) {
            if (str_starts_with($path, $segment)) {
                $candidate = Str::before(Str::after($path, $segment), '/');

                return preg_match('/^[A-Za-z0-9_-]{6,}$/', $candidate) ? $candidate : null;
            }
        }

        return null;
    }

    protected function landing(string $pageKey): ?LandingPage
    {
        return LandingPage::query()
            ->where('page_key', $pageKey)
            ->where('is_active', true)
            ->first();
    }

    protected function theme(string $view): string
    {
        return 'themes.'.$this->site->activeTheme().'.'.$view;
    }

    protected function seo(array $overrides): array
    {
        $settings = $this->site->current();

        $seo = array_merge([
            'title' => $settings->seo_title ?: $settings->site_name,
            'description' => $settings->seo_description,
            'canonical' => url()->current(),
            'robots' => $settings->seo_robots ?: 'index,follow',
            'type' => 'website',
            'og_title' => null,
            'og_description' => null,
            'og_image' => $this->siteMediaUrl('og_image', FrontsiteMedia::SIZE_SMALL),
            'schema' => null,
        ], $overrides);

        $seo['canonical'] = FrontsiteUrls::canonicalUrl($seo['canonical'] ?? url()->current());

        return $seo;
    }

    protected function organizationSchema(): array
    {
        $settings = $this->site->current();
        $sameAs = collect([
            $settings->facebook_url,
            $settings->instagram_url,
            $settings->youtube_url,
            $settings->linkedin_url,
            $settings->tiktok_url,
            $settings->zalo_url,
            $settings->messenger_url,
        ])->filter()->values()->all();

        return $this->schemaNode([
            '@id' => $this->organizationId(),
            '@type' => 'Organization',
            'additionalType' => 'https://schema.org/TravelAgency',
            'address' => $this->organizationAddressSchema(),
            'contactPoint' => $this->organizationContactPointSchema(),
            'description' => $settings->site_description ?: $settings->seo_description,
            'identifier' => $this->organizationIdentifierSchema(),
            'image' => $this->imageObjectSchema($this->organizationImageUrl(), $settings->company_name ?: $settings->site_name),
            'legalName' => $this->companyLegalValue('legal_name') ?: $settings->company_name,
            'name' => $settings->company_name ?: $settings->site_name,
            'url' => FrontsiteUrls::canonicalUrl(route('home')),
            'telephone' => $settings->phone ?: $settings->hotline,
            'taxID' => $this->companyLegalValue('tax_code'),
            'email' => $settings->primary_email,
            'logo' => $this->imageObjectSchema($this->siteMediaUrl('logo'), 'Logo '.($settings->company_name ?: $settings->site_name)),
            'sameAs' => $sameAs,
        ]);
    }

    protected function companyLegalValue(string $key): ?string
    {
        $value = trim((string) data_get($this->site->current()->structured_data, 'company.'.$key));

        if ($value !== '') {
            return $value;
        }

        if ($key === 'international_travel_license') {
            $fallback = trim((string) config('frontsite_seo.company.international_travel_license_fallback'));

            return $fallback !== '' ? $fallback : null;
        }

        return null;
    }

    protected function organizationIdentifierSchema(): ?array
    {
        $items = collect([
            ['name' => 'Giấy phép lữ hành quốc tế', 'value' => $this->companyLegalValue('international_travel_license')],
            ['name' => 'Giấy phép đăng ký kinh doanh', 'value' => $this->companyLegalValue('business_license')],
            ['name' => 'Mã số thuế', 'value' => $this->companyLegalValue('tax_code')],
        ])
            ->filter(fn (array $item): bool => filled($item['value']))
            ->map(fn (array $item): array => $this->schemaNode([
                '@type' => 'PropertyValue',
                'name' => $item['name'],
                'value' => $item['value'],
            ]))
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return $items->count() === 1 ? $items->first() : $items->all();
    }

    protected function organizationId(): string
    {
        return FrontsiteUrls::canonicalUrl(route('home')).'#organization';
    }

    protected function organizationReference(): array
    {
        return ['@id' => $this->organizationId()];
    }

    protected function websiteId(): string
    {
        return route('home').'#website';
    }

    protected function websiteSchema(): array
    {
        $settings = $this->site->current();

        return $this->schemaNode([
            '@id' => $this->websiteId(),
            '@type' => 'WebSite',
            'description' => $settings->seo_description ?: $settings->site_description,
            'name' => $settings->site_name,
            'publisher' => $this->organizationReference(),
            'url' => FrontsiteUrls::canonicalUrl(route('home')),
        ]);
    }

    protected function organizationSummarySchema(): ?array
    {
        $name = trim((string) ($this->site->current()->company_name ?: $this->site->current()->site_name));

        if ($name === '') {
            return null;
        }

        return $this->schemaNode([
            '@type' => 'Organization',
            'name' => $name,
        ]);
    }

    protected function articleAuthorSchema(?string $authorName = null): array
    {
        $name = trim((string) $authorName);
        $name = $name !== '' ? $name : 'Nguyễn Ngọc';

        return $this->schemaNode([
            '@type' => 'Person',
            'name' => $name,
            'url' => $this->articleAuthorUrl($name),
        ]);
    }

    protected function articleAuthorUrl(string $authorName): string
    {
        $slug = Str::slug($authorName);

        return url('/tac-gia/'.($slug !== '' ? $slug : 'nguyen-ngoc'));
    }

    protected function blogSchema(): array
    {
        $settings = $this->site->current();

        return $this->schemaNode([
            '@id' => $this->blogSchemaId(),
            '@type' => 'Blog',
            'description' => $settings->seo_description ?: $settings->site_description,
            'name' => 'Blog',
            'publisher' => $this->organizationReference(),
            'url' => route('blog.index'),
        ]);
    }

    protected function blogSchemaId(): string
    {
        return route('blog.index').'#blog';
    }

    protected function blogArticlePostingSchema(BlogPost $post, Collection $blogCategoryTrail, string $articleUrl, bool $hasToc): array
    {
        return $this->schemaNode([
            '@id' => $articleUrl.'#article',
            '@type' => 'BlogPosting',
            'articleSection' => $post->category?->name ?: $blogCategoryTrail->last()?->name,
            'about' => $this->blogArticleAboutSchema($post),
            'associatedMedia' => $this->blogArticleAssociatedMediaSchema($post),
            'author' => ['@id' => $this->blogArticleAuthorId($post)],
            'dateModified' => optional($post->updated_at)->toAtomString() ?: optional($post->published_at)->toAtomString(),
            'datePublished' => optional($post->published_at)->toAtomString(),
            'description' => $this->blogArticleDescription($post),
            'headline' => $post->title,
            'image' => $this->blogArticleImageSchema($post),
            'isPartOf' => ['@id' => $this->blogSchemaId()],
            'keywords' => $this->blogArticleKeywords($post),
            'mainEntityOfPage' => $this->blogArticleWebPageSchema($articleUrl, $hasToc),
            'publisher' => $this->organizationReference(),
            'url' => $articleUrl,
            'wordCount' => $this->blogArticleWordCount($post),
        ]);
    }

    protected function blogArticleAuthorSchema(BlogPost $post): array
    {
        $authorId = $this->blogArticleAuthorId($post);

        return $this->schemaNode([
            '@id' => $authorId,
            '@type' => 'Person',
            'affiliation' => $this->organizationReference(),
            'description' => $this->blogArticleAuthorDescription($post),
            'jobTitle' => 'Travel Content Writer',
            'knowsAbout' => [
                'Kinh nghiệm du lịch',
                'Checklist chuẩn bị chuyến đi',
                'Du lịch Phú Quốc',
            ],
            'name' => $this->blogArticleAuthorName($post),
            'url' => $authorId,
            'worksFor' => $this->organizationReference(),
        ]);
    }

    protected function blogArticleAuthorId(BlogPost $post): string
    {
        return $this->articleAuthorUrl($this->blogArticleAuthorName($post));
    }

    protected function blogArticleAuthorName(BlogPost $post): string
    {
        $name = trim((string) $post->author_name);

        return $name !== '' ? $name : 'Nguyễn Ngọc';
    }

    protected function blogArticleAuthorDescription(BlogPost $post): string
    {
        return $this->blogArticleAuthorName($post).' là tác giả nội dung du lịch của Hải Đăng Travel, tập trung vào kinh nghiệm chuẩn bị chuyến đi, gợi ý lịch trình và các lưu ý thực tế cho du khách.';
    }

    protected function blogArticleDescription(BlogPost $post): ?string
    {
        foreach ([$post->meta_description, $post->excerpt, $post->content] as $candidate) {
            $description = RichText::normalizePlain((string) $candidate);

            if ($description !== '') {
                return Str::limit($description, 300, '');
            }
        }

        return null;
    }

    protected function blogArticleImageSchema(BlogPost $post): ?array
    {
        $imageUrl = $this->articleImageUrl($post, FrontsiteMedia::SIZE_SMALL)
            ?: 'https://haidangtravel.com/storage/580/conversions/san-bay-pq-small.webp';

        if (! filled($imageUrl)) {
            return null;
        }

        return $this->schemaNode([
            '@type' => 'ImageObject',
            'url' => $imageUrl,
            'contentUrl' => $imageUrl,
            'width' => 1200,
            'height' => 630,
            'caption' => trim((string) ($post->cover_alt ?: $post->title)),
        ]);
    }

    protected function blogArticleAssociatedMediaSchema(BlogPost $post): ?array
    {
        return $this->imageObjectListSchema($this->blogArticleImageCandidates($post));
    }

    /**
     * @return array<int, array{url: ?string, caption: ?string}>
     */
    protected function blogArticleImageCandidates(BlogPost $post): array
    {
        $caption = trim((string) ($post->cover_alt ?: $post->title));
        $candidates = [
            [
                'url' => $this->modelMediaUrl($post, 'cover'),
                'caption' => $caption !== '' ? $caption : null,
            ],
        ];

        foreach ($this->richTextImageCandidates((string) $post->content, $caption !== '' ? $caption : $post->title) as $candidate) {
            $candidates[] = $candidate;
        }

        return $candidates;
    }

    protected function blogArticleWebPageSchema(string $articleUrl, bool $hasToc): array
    {
        return $this->schemaNode([
            '@type' => 'WebPage',
            '@id' => $articleUrl,
            'hasPart' => $hasToc ? [
                '@type' => 'WebPageElement',
                'name' => 'Mục lục bài viết',
                'cssSelector' => '.toc-container',
            ] : null,
        ]);
    }

    protected function blogArticleKeywords(BlogPost $post): array
    {
        $schemaKeywords = $this->blogArticleSchemaKeywords($post->schema);

        if ($schemaKeywords !== []) {
            return $schemaKeywords;
        }

        $keywords = collect([
            $post->category?->name,
            $post->countryDestination?->name,
            $post->destination?->name,
            $post->title,
        ])
            ->filter()
            ->map(fn (string $keyword): string => RichText::normalizePlain($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($keywords !== []) {
            return $keywords;
        }

        return [
            'đi phú quốc nên mang theo gì',
            'checklist du lịch phú quốc',
            'kinh nghiệm du lịch phú quốc',
        ];
    }

    protected function blogArticleAboutSchema(BlogPost $post): ?array
    {
        $items = collect([
            $post->countryDestination instanceof Destination ? $this->schemaNode([
                '@id' => $this->countrySchemaId($post->countryDestination),
                '@type' => 'Country',
                'name' => $post->countryDestination->name,
                'url' => route('countries.show', ['slug' => $post->countryDestination->slug]),
            ]) : null,
            $post->destination instanceof Destination ? $this->schemaNode([
                '@id' => $this->destinationSchemaId($post->destination),
                '@type' => 'TouristDestination',
                'containedInPlace' => $post->destination->country instanceof Destination
                    ? ['@id' => $this->countrySchemaId($post->destination->country)]
                    : null,
                'name' => $post->destination->name,
                'url' => route('destinations.show', $post->destination),
            ]) : null,
        ])->filter()->values()->all();

        return $items !== [] ? $items : null;
    }

    protected function blogArticleSchemaKeywords(?array $schema): array
    {
        if (! is_array($schema) || $schema === []) {
            return [];
        }

        $candidates = [
            data_get($schema, 'keywords'),
            data_get($schema, '@graph.0.keywords'),
        ];

        foreach ($schema as $node) {
            if (is_array($node) && data_get($node, '@type') === 'BlogPosting') {
                $candidates[] = data_get($node, 'keywords');
            }
        }

        foreach ((array) data_get($schema, '@graph', []) as $node) {
            if (is_array($node) && data_get($node, '@type') === 'BlogPosting') {
                $candidates[] = data_get($node, 'keywords');
            }
        }

        foreach ($candidates as $candidate) {
            $keywords = $this->normalizeSchemaKeywordList($candidate);

            if ($keywords !== []) {
                return $keywords;
            }
        }

        return [];
    }

    protected function normalizeSchemaKeywordList(mixed $keywords): array
    {
        if (is_string($keywords)) {
            $keywords = preg_split('/[,;\n]+/u', $keywords) ?: [];
        }

        if (! is_array($keywords)) {
            return [];
        }

        return collect($keywords)
            ->map(fn (mixed $keyword): string => RichText::normalizePlain((string) $keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function blogArticleWordCount(BlogPost $post): string
    {
        $content = RichText::normalizePlain((string) $post->content);

        if ($content !== '' && preg_match_all('/[\p{L}\p{N}]+/u', $content, $matches) !== false) {
            $count = count($matches[0] ?? []);

            if ($count > 0) {
                return (string) $count;
            }
        }

        return '2500';
    }

    protected function organizationContactPointSchema(): ?array
    {
        $settings = $this->site->current();
        $telephone = trim((string) ($settings->phone ?: $settings->hotline));
        $email = trim((string) $settings->primary_email);

        if ($telephone === '' && $email === '') {
            return null;
        }

        return $this->schemaNode([
            '@type' => 'ContactPoint',
            'contactType' => 'customer support',
            'email' => $email,
            'telephone' => $telephone,
        ]);
    }

    protected function articleImageUrl(BlogPost $post, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        $coverImage = $this->modelMediaUrl($post, 'cover', $size);

        if (filled($coverImage)) {
            return $coverImage;
        }

        $contentImage = $this->firstRichTextImageUrl((string) $post->content);

        if (filled($contentImage)) {
            return $contentImage;
        }

        return $this->siteMediaUrl('og_image', $size) ?: $this->organizationImageUrl();
    }

    /**
     * @return array{small: ?string, medium: ?string, full: ?string}
     */
    protected function articleResponsiveImageUrls(BlogPost $post): array
    {
        $coverMedia = FrontsiteMedia::responsiveUrls($post, 'cover', 'cover_image_url');

        if (filled($coverMedia[FrontsiteMedia::SIZE_FULL] ?? null)) {
            return $coverMedia;
        }

        $contentImage = $this->firstRichTextImageUrl((string) $post->content);

        if (filled($contentImage)) {
            return [
                FrontsiteMedia::SIZE_SMALL => $contentImage,
                FrontsiteMedia::SIZE_MEDIUM => $contentImage,
                FrontsiteMedia::SIZE_FULL => $contentImage,
            ];
        }

        return [
            FrontsiteMedia::SIZE_SMALL => null,
            FrontsiteMedia::SIZE_MEDIUM => null,
            FrontsiteMedia::SIZE_FULL => null,
        ];
    }

    protected function organizationAddressSchema(): array|string|null
    {
        $settings = $this->site->current();
        $streetAddress = trim((string) (data_get($settings->structured_data, 'address.street_address') ?: $settings->address));
        $addressLocality = trim((string) data_get($settings->structured_data, 'address.address_locality'));
        $addressRegion = trim((string) data_get($settings->structured_data, 'address.address_region'));
        $postalCode = trim((string) data_get($settings->structured_data, 'address.postal_code'));
        $rawAddressCountry = data_get($settings->structured_data, 'address.address_country');
        $hasAddressFields = $streetAddress !== '' || $addressLocality !== '' || $addressRegion !== '' || $postalCode !== '';
        $addressCountry = $this->organizationAddressCountryCode($rawAddressCountry, $hasAddressFields);

        if (! $hasAddressFields && $addressCountry === null) {
            return null;
        }

        return $this->schemaNode([
            '@type' => 'PostalAddress',
            'streetAddress' => $streetAddress,
            'addressLocality' => $addressLocality,
            'addressRegion' => $addressRegion,
            'postalCode' => $postalCode,
            'addressCountry' => $addressCountry,
        ]);
    }

    protected function organizationAddressCountryCode(mixed $value, bool $defaultToVietnam): ?string
    {
        $country = $this->organizationAddressCountryText($value);

        if ($country === '') {
            return $defaultToVietnam ? 'VN' : null;
        }

        $upperCountry = Str::upper($country);

        if ($upperCountry === 'VN' || $upperCountry === 'VNM') {
            return 'VN';
        }

        if (preg_match('/^[A-Za-z]{2}$/', $country) === 1) {
            return $upperCountry;
        }

        $normalizedCountry = Str::of($country)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        if ($normalizedCountry === 'viet nam' || str_replace(' ', '', $normalizedCountry) === 'vietnam') {
            return 'VN';
        }

        return $defaultToVietnam ? 'VN' : null;
    }

    protected function organizationAddressCountryText(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            foreach (['addressCountry', 'countryCode', 'code', 'identifier', 'name', 'value'] as $key) {
                $candidate = data_get($value, $key);

                if (is_scalar($candidate) || $candidate instanceof \Stringable) {
                    $candidate = trim((string) $candidate);

                    if ($candidate !== '') {
                        return $candidate;
                    }
                }
            }

            return '';
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return trim((string) $value);
        }

        return '';
    }

    protected function organizationImageUrl(): ?string
    {
        $configuredImage = trim((string) data_get($this->site->current()->structured_data, 'organization.image_url'));

        if ($configuredImage !== '') {
            return $configuredImage;
        }

        return $this->siteMediaUrl('og_image') ?: $this->siteMediaUrl('logo');
    }

    protected function localBusinessSchema(): ?array
    {
        $settings = $this->site->current();
        $priceRange = trim((string) data_get($settings->structured_data, 'local_business.price_range'));
        $address = $this->organizationAddressSchema();

        if ($priceRange === '' && $address === null) {
            return null;
        }

        return $this->schemaNode([
            '@id' => route('home').'#local-business',
            '@type' => 'LocalBusiness',
            'address' => $address,
            'email' => $settings->primary_email,
            'image' => $this->imageObjectSchema($this->organizationImageUrl(), $settings->company_name ?: $settings->site_name),
            'logo' => $this->imageObjectSchema($this->siteMediaUrl('logo'), 'Logo '.($settings->company_name ?: $settings->site_name)),
            'name' => $settings->company_name ?: $settings->site_name,
            'parentOrganization' => $this->organizationReference(),
            'priceRange' => $priceRange,
            'telephone' => $settings->phone ?: $settings->hotline,
            'url' => FrontsiteUrls::canonicalUrl(route('home')),
        ]);
    }

    protected function schemaNode(array $properties): array
    {
        $compacted = $this->compactSchemaValue($properties);

        return is_array($compacted) ? $compacted : [];
    }

    protected function compactSchemaValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $compacted = [];

        foreach ($value as $key => $item) {
            $normalized = $this->compactSchemaValue($item);

            if ($normalized === null || $normalized === '' || $normalized === []) {
                continue;
            }

            if ($isList) {
                $compacted[] = $normalized;
            } else {
                $compacted[$key] = $normalized;
            }
        }

        return $compacted;
    }

    protected function imageObjectSchema(?string $url, ?string $caption = null, ?string $id = null): ?array
    {
        if (! filled($url)) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $id,
            '@type' => 'ImageObject',
            'url' => $url,
            'contentUrl' => $url,
            'caption' => $caption,
        ]);
    }

    /**
     * @param  iterable<int, array{url?: ?string, caption?: ?string, id?: ?string}|string|null>  $images
     * @return array<int, array<string, mixed>>|null
     */
    protected function imageObjectListSchema(iterable $images, ?string $fallbackCaption = null): ?array
    {
        $items = collect($images)
            ->map(function (mixed $image) use ($fallbackCaption): ?array {
                $url = is_array($image) ? trim((string) ($image['url'] ?? '')) : trim((string) $image);
                $caption = is_array($image) ? trim((string) ($image['caption'] ?? '')) : '';
                $id = is_array($image) ? trim((string) ($image['id'] ?? '')) : '';

                return $this->imageObjectSchema(
                    $url,
                    $caption !== '' ? $caption : $fallbackCaption,
                    $id !== '' ? $id : null,
                );
            })
            ->filter()
            ->unique(fn (array $image): string => (string) ($image['url'] ?? $image['contentUrl'] ?? md5(json_encode($image))))
            ->values()
            ->all();

        return $items !== [] ? $items : null;
    }

    protected function landingImageSchema(?LandingPage $landing): ?array
    {
        return $this->imageObjectSchema(
            $this->landingPrimaryImageUrl($landing),
            $this->landingImageCaption($landing),
        );
    }

    protected function landingImageCaption(?LandingPage $landing): ?string
    {
        if (! $landing) {
            return null;
        }

        $hero = $this->resolveLandingHero($landing);
        $caption = trim((string) (
            data_get($hero, 'media_alt')
            ?: data_get($hero, 'slides.0.image_alt')
            ?: $landing->hero_title
            ?: $landing->title
        ));

        return $caption !== '' ? $caption : null;
    }

    protected function tourImageSchema(Tour $tour): ?array
    {
        $caption = trim((string) (data_get($tour, 'cover_alt') ?: $tour->title));

        return $this->imageObjectSchema(
            $this->tourImageUrl($tour),
            $caption !== '' ? $caption : null,
        );
    }

    protected function modelImageSchema(
        object $model,
        string $collection,
        ?string $caption = null,
        string $size = FrontsiteMedia::SIZE_FULL,
        array|string|null $directUrlAttributes = 'cover_image_url',
    ): ?array {
        $resolvedCaption = trim((string) (
            $caption
            ?: data_get($model, 'cover_alt')
            ?: data_get($model, 'name')
            ?: data_get($model, 'title')
        ));

        return $this->imageObjectSchema(
            $this->modelMediaUrl($model, $collection, $size, $directUrlAttributes),
            $resolvedCaption !== '' ? $resolvedCaption : null,
        );
    }

    protected function firstRichTextImageUrl(?string $content): ?string
    {
        return FrontsiteMedia::firstRichTextImageUrl($content);
    }

    /**
     * @return array<int, array{url: string, caption: ?string}>
     */
    protected function richTextImageCandidates(?string $content, ?string $fallbackCaption = null): array
    {
        $html = RichText::sanitize($content);

        if ($html === '' || ! str_contains($html, '<img')) {
            return [];
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $candidates = [];

        foreach ($document->getElementsByTagName('img') as $image) {
            if (! $image instanceof \DOMElement) {
                continue;
            }

            $src = trim((string) $image->getAttribute('src'));

            if ($src === '' || Str::startsWith($src, '#')) {
                continue;
            }

            if (Str::startsWith($src, '//')) {
                $src = 'https:'.$src;
            } elseif (Str::startsWith($src, '/')) {
                $src = url($src);
            }

            $url = FrontsiteMedia::validatedUrl($src);

            if (! filled($url)) {
                continue;
            }

            $caption = trim((string) ($image->getAttribute('alt') ?: $image->getAttribute('title')));
            $candidates[] = [
                'url' => $url,
                'caption' => $caption !== '' ? $caption : $fallbackCaption,
            ];
        }

        return collect($candidates)
            ->unique('url')
            ->values()
            ->all();
    }

    protected function tourImageUrl(Tour $tour, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        $media = $this->tourResponsiveImageUrls($tour);

        return $media[$size]
            ?? $media[FrontsiteMedia::SIZE_FULL]
            ?? $media[FrontsiteMedia::SIZE_MEDIUM]
            ?? $media[FrontsiteMedia::SIZE_SMALL]
            ?? null;
    }

    /**
     * @return array{small: ?string, medium: ?string, full: ?string}
     */
    protected function tourResponsiveImageUrls(Tour $tour): array
    {
        $coverMedia = FrontsiteMedia::responsiveUrls($tour, 'cover', 'cover_image_url');

        if (filled($coverMedia[FrontsiteMedia::SIZE_FULL] ?? null)) {
            return $coverMedia;
        }

        $galleryMedia = $this->firstTourGalleryResponsiveImageUrls($tour);

        if (filled($galleryMedia[FrontsiteMedia::SIZE_FULL] ?? null)) {
            return $galleryMedia;
        }

        $contentImage = $this->firstRichTextImageUrl((string) $tour->content);

        if (filled($contentImage)) {
            return [
                FrontsiteMedia::SIZE_SMALL => $contentImage,
                FrontsiteMedia::SIZE_MEDIUM => $contentImage,
                FrontsiteMedia::SIZE_FULL => $contentImage,
            ];
        }

        return [
            FrontsiteMedia::SIZE_SMALL => null,
            FrontsiteMedia::SIZE_MEDIUM => null,
            FrontsiteMedia::SIZE_FULL => null,
        ];
    }

    /**
     * @return array{small: ?string, medium: ?string, full: ?string}
     */
    protected function firstTourGalleryResponsiveImageUrls(Tour $tour): array
    {
        foreach (FrontsiteGalleryData::tour($tour) as $item) {
            if (($item['lightbox_kind'] ?? null) !== ContentGallery::TYPE_IMAGE) {
                continue;
            }

            $full = trim((string) (
                ($item['full_image_url'] ?? '')
                ?: ($item['lightbox_src'] ?? '')
                ?: ($item['stage_src'] ?? '')
                ?: ($item['image_url'] ?? '')
            ));
            $medium = trim((string) (
                ($item['image_url'] ?? '')
                ?: ($item['stage_src'] ?? '')
                ?: $full
            ));
            $small = trim((string) (
                ($item['small_image_url'] ?? '')
                ?: ($item['thumbnail_url'] ?? '')
                ?: ($item['thumbnail_image'] ?? '')
                ?: $medium
                ?: $full
            ));

            if ($full !== '' || $medium !== '' || $small !== '') {
                return [
                    FrontsiteMedia::SIZE_SMALL => $small !== '' ? $small : ($medium !== '' ? $medium : $full),
                    FrontsiteMedia::SIZE_MEDIUM => $medium !== '' ? $medium : ($full !== '' ? $full : $small),
                    FrontsiteMedia::SIZE_FULL => $full !== '' ? $full : ($medium !== '' ? $medium : $small),
                ];
            }
        }

        return [
            FrontsiteMedia::SIZE_SMALL => null,
            FrontsiteMedia::SIZE_MEDIUM => null,
            FrontsiteMedia::SIZE_FULL => null,
        ];
    }

    protected function tourSchemaId(Tour $tour): string
    {
        return route('tours.show', $tour).'#tour';
    }

    protected function tourDetailEntitySchema(
        Tour $tour,
        string $tourUrl,
        ?array $primaryOffer,
        array $reviewItems,
        ?array $reviewSummary,
        ?array $itinerarySchema,
    ): array {
        $aggregateRating = ReviewContent::aggregateRatingSchema($reviewSummary);
        $review = $this->travelReviewsEnabled() ? $this->tourReviewSchema($reviewItems) : null;

        if ($this->hasProductRichResultProperty($primaryOffer, $aggregateRating, $review)) {
            return $this->tourProductSchema($tour, $tourUrl, $primaryOffer, $review, $aggregateRating, $itinerarySchema);
        }

        return $this->tourTouristTripSchema($tour, $tourUrl, $primaryOffer, $itinerarySchema, $this->tourSchemaId($tour));
    }

    protected function tourProductSchema(
        Tour $tour,
        string $tourUrl,
        ?array $primaryOffer,
        array|object|null $review,
        ?array $aggregateRating,
        ?array $itinerarySchema,
    ): array {
        return $this->schemaNode([
            '@id' => $this->tourSchemaId($tour),
            '@type' => 'Product',
            'additionalProperty' => $this->tourAdditionalPropertySchema($tour),
            'aggregateRating' => $aggregateRating,
            'brand' => $this->tourBrandSchema(),
            'category' => $this->tourProductCategory($tour),
            'description' => $this->tourProductDescription($tour),
            'image' => $this->tourImageSchema($tour),
            'keywords' => $this->tourProductKeywords($tour),
            'mainEntityOfPage' => $tourUrl,
            'name' => $tour->title,
            'offers' => $primaryOffer,
            'productID' => (string) $tour->getKey(),
            'review' => $review,
            'sku' => $this->tourProductSku($tour),
            'slogan' => $this->tourProductSlogan(),
            'subjectOf' => $itinerarySchema ? ['@id' => $itinerarySchema['@id']] : null,
            'url' => $tourUrl,
        ]);
    }

    protected function tourTouristTripSchema(
        Tour $tour,
        string $tourUrl,
        ?array $offerRef = null,
        ?array $itinerarySchema = null,
        ?string $schemaId = null,
    ): array {
        return $this->schemaNode([
            '@id' => $schemaId ?: $this->touristTripSchemaId($tour),
            '@type' => 'TouristTrip',
            'additionalProperty' => $this->tourAdditionalPropertySchema($tour),
            'description' => $this->tourProductDescription($tour),
            'image' => $this->tourImageSchema($tour),
            'mainEntityOfPage' => $tourUrl,
            'name' => $tour->title,
            'offers' => $offerRef,
            'provider' => $this->organizationSummarySchema(),
            'subjectOf' => $itinerarySchema ? ['@id' => $itinerarySchema['@id']] : null,
            'tripOrigin' => $this->departurePlaceSchema($tour),
            'url' => $tourUrl,
        ]);
    }

    protected function hasProductRichResultProperty(
        ?array $offers,
        ?array $aggregateRating = null,
        array|object|null $review = null,
    ): bool {
        return $this->schemaValueIsPresent($offers)
            || $this->schemaValueIsPresent($aggregateRating)
            || $this->schemaValueIsPresent($review);
    }

    protected function schemaValueIsPresent(mixed $value): bool
    {
        if (is_array($value)) {
            return $this->compactSchemaValue($value) !== [];
        }

        return $value !== null && $value !== '';
    }

    protected function tourBrandSchema(): ?array
    {
        $brandName = trim((string) ($this->site->current()->company_name ?: $this->site->current()->site_name));

        if ($brandName === '') {
            return null;
        }

        return $this->schemaNode([
            '@type' => 'Brand',
            'name' => $brandName,
        ]);
    }

    protected function tourProductCategory(Tour $tour): ?string
    {
        $category = trim((string) ($tour->scope?->label() ?: $tour->primaryCategory?->name));

        return $category !== '' ? $category : null;
    }

    protected function tourProductKeywords(Tour $tour): ?string
    {
        $keywords = collect([
            $this->tourProductCategory($tour),
            $tour->scope?->label(),
            $tour->destination?->name,
            $tour->region?->name,
            $tour->transport,
        ])
            ->filter(fn (mixed $value) => filled($value))
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->unique()
            ->implode(', ');

        return $keywords !== '' ? $keywords : null;
    }

    protected function tourProductDescription(Tour $tour): string
    {
        $description = collect([
            $tour->excerpt,
            $tour->meta_description,
            $tour->content,
        ])
            ->map(fn (mixed $value) => RichText::normalizePlain((string) $value))
            ->first(fn (string $value) => $value !== '');

        $fallback = $this->tourGeneratedProductDescription($tour);

        if (! is_string($description) || $description === '') {
            $description = $fallback;
        } elseif (mb_strlen($description) < 50 && $fallback !== '' && $fallback !== $description) {
            $description = trim($description, ". \t\n\r\0\x0B").'. '.$fallback;
        }

        return Str::limit($description, 500, '');
    }

    protected function tourGeneratedProductDescription(Tour $tour): string
    {
        $title = RichText::normalizePlain((string) $tour->title) ?: 'Tour du lịch';
        $duration = $this->tourDurationLabel($tour);
        $brandName = trim((string) ($this->site->current()->company_name ?: $this->site->current()->site_name));
        $facts = collect([
            $this->tourProductCategory($tour),
            filled($tour->destination?->name) ? 'điểm đến '.$tour->destination->name : null,
            filled($tour->region?->name) ? 'khu vực '.$tour->region->name : null,
            filled($tour->departure_location) ? 'khởi hành từ '.$tour->departure_location : null,
            filled($tour->transport) ? 'di chuyển bằng '.$tour->transport : null,
            $duration ? 'thời lượng '.$duration : null,
        ])
            ->filter(fn (mixed $value) => filled($value))
            ->map(fn (mixed $value) => trim((string) $value))
            ->unique()
            ->implode(', ');

        if ($facts !== '') {
            return $title.' - '.$facts.'.';
        }

        return $title.' được '.($brandName !== '' ? $brandName : 'Haidangtravel').' tư vấn và tổ chức theo nhu cầu khách hàng.';
    }

    protected function tourProductSku(Tour $tour): ?string
    {
        $apiTourCode = $this->tourApiTourCode($tour);

        if ($apiTourCode !== null) {
            return $apiTourCode;
        }

        if (! $tour->getKey()) {
            return null;
        }

        $year = $tour->published_at?->format('Y')
            ?: $tour->created_at?->format('Y')
            ?: now(config('app.timezone'))->format('Y');

        return 'HD'.$year.$tour->getKey();
    }

    protected function tourApiTourCode(Tour $tour): ?string
    {
        if ($tour->relationLoaded('agencySyncStates')) {
            $syncState = $tour->agencySyncStates
                ->filter(fn (mixed $state): bool => filled($state->tour_code ?? null))
                ->sortByDesc(function (mixed $state): int {
                    return $state->last_synced_at?->getTimestamp()
                        ?? $state->updated_at?->getTimestamp()
                        ?? 0;
                })
                ->first();

            $tourCode = trim((string) ($syncState->tour_code ?? ''));

            return $tourCode !== '' ? $tourCode : null;
        }

        if (! $tour->getKey()) {
            return null;
        }

        $tourCode = trim((string) $tour->agencySyncStates()
            ->whereNotNull('tour_code')
            ->where('tour_code', '<>', '')
            ->latest('last_synced_at')
            ->latest('updated_at')
            ->value('tour_code'));

        return $tourCode !== '' ? $tourCode : null;
    }

    protected function tourProductSlogan(): ?string
    {
        $slogan = trim((string) $this->site->current()->site_tagline);

        return $slogan !== '' ? $slogan : null;
    }

    protected function tourReviewSchema(array $reviewItems): ?array
    {
        if (! $this->travelReviewsEnabled()) {
            return null;
        }

        $reviews = ReviewContent::reviewSchema($reviewItems);

        if (! is_array($reviews) || $reviews === []) {
            return null;
        }

        return array_is_list($reviews) && count($reviews) === 1
            ? $reviews[0]
            : $reviews;
    }

    protected function tourAdditionalPropertySchema(Tour $tour): ?array
    {
        $items = collect([
            ['label' => 'Phạm vi tour', 'value' => $tour->scope?->label()],
            ['label' => 'Chủ đề tour', 'value' => $tour->primaryCategory?->name],
            ['label' => 'Điểm khởi hành', 'value' => $tour->departure_location],
//            ['label' => 'Quốc gia', 'value' => $tour->destination?->country?->name],
            ['label' => 'Điểm đến', 'value' => $tour->destination?->name],
            ['label' => 'Vùng miền', 'value' => $tour->region?->name],
            ['label' => 'Phương tiện', 'value' => $tour->transport],
            ['label' => 'Thời lượng', 'value' => $this->tourDurationLabel($tour)],
            ['label' => 'Tiêu chuẩn', 'value' => $tour->standard_label],
        ])
            ->map(fn (array $item) => $this->schemaNode([
                '@type' => 'PropertyValue',
                'name' => $item['label'],
                'value' => filled($item['value']) ? trim((string) $item['value']) : null,
            ]))
            ->filter()
            ->values()
            ->all();

        return $items !== [] ? $items : null;
    }

    protected function tourDurationLabel(Tour $tour): ?string
    {
        $duration = collect([
            filled($tour->duration_days) ? $tour->duration_days.' ngày' : null,
            filled($tour->duration_nights) ? $tour->duration_nights.' đêm' : null,
        ])->filter()->implode(' ');

        return $duration !== '' ? $duration : null;
    }

    protected function destinationSchemaId(Destination $destination): string
    {
        return route('destinations.show', $destination).'#destination';
    }

    protected function countrySchemaId(Destination $country): string
    {
        return route('countries.show', ['slug' => $country->slug]).'#country';
    }

    protected function findPublishedDestinationBySlug(string $slug): ?Destination
    {
        $relations = ['country', 'region'];

        if ($this->travelReviewsEnabled()) {
            $relations['publishedReviews'] = fn ($reviewQuery) => $reviewQuery->limit(6);
        }

        return Destination::query()
            ->published()
            ->with($relations)
            ->where('slug', $slug)
            ->first();
    }

    protected function regionSchemaId(Region $region): string
    {
        return route('regions.show', $region).'#region';
    }

    protected function departurePlaceSchema(Tour $tour): ?array
    {
        $departurePlace = trim((string) ($tour->departure_location ?: data_get($tour->departures->first(), 'departure_location')));

        if ($departurePlace === '') {
            return null;
        }

        return [
            '@type' => 'Place',
            'name' => $departurePlace,
        ];
    }

    protected function tourPrimaryDestinationReference(Tour $tour): ?array
    {
        return match (true) {
            $tour->destination instanceof Destination => ['@id' => $this->destinationSchemaId($tour->destination)],
            $tour->region instanceof Region => ['@id' => $this->regionSchemaId($tour->region)],
            default => null,
        };
    }

    protected function tourPlaceSchemas(Tour $tour): array
    {
        $nodes = [];
        $country = $tour->destination instanceof Destination && $tour->destination->relationLoaded('country')
            ? $tour->destination->country
            : null;

        if ($country instanceof Destination) {
            $nodes[] = $this->schemaNode([
                '@id' => $this->countrySchemaId($country),
                '@type' => 'Country',
                'description' => $country->excerpt ?: strip_tags((string) $country->content),
                'image' => $this->modelImageSchema($country, 'avatar', $country->name),
                'mainEntityOfPage' => route('countries.show', ['slug' => $country->slug]),
                'name' => $country->name,
                'url' => route('countries.show', ['slug' => $country->slug]),
            ]);
        }

        if ($tour->region instanceof Region) {
            $nodes[] = $this->schemaNode([
                '@id' => $this->regionSchemaId($tour->region),
                '@type' => 'AdministrativeArea',
                'containedInPlace' => $country instanceof Destination ? ['@id' => $this->countrySchemaId($country)] : null,
                'description' => $tour->region->excerpt ?: strip_tags((string) $tour->region->content),
                'image' => $this->modelImageSchema($tour->region, 'avatar', $tour->region->name),
                'mainEntityOfPage' => route('regions.show', $tour->region),
                'name' => $tour->region->name,
                'url' => route('regions.show', $tour->region),
            ]);
        }

        if ($tour->destination instanceof Destination) {
            $nodes[] = $this->schemaNode([
                '@id' => $this->destinationSchemaId($tour->destination),
                '@type' => 'TouristDestination',
                'containedInPlace' => collect([
                    $tour->region instanceof Region ? ['@id' => $this->regionSchemaId($tour->region)] : null,
                    $country instanceof Destination ? ['@id' => $this->countrySchemaId($country)] : null,
                ])->filter()->values()->all() ?: null,
                'description' => $tour->destination->excerpt ?: strip_tags((string) $tour->destination->content),
                'image' => $this->modelImageSchema($tour->destination, 'avatar', $tour->destination->name),
                'mainEntityOfPage' => route('destinations.show', $tour->destination),
                'name' => $tour->destination->name,
                'tourBookingPage' => route('destinations.show', $tour->destination),
                'url' => route('destinations.show', $tour->destination),
            ]);
        }

        return array_values(array_filter($nodes));
    }

    protected function tourItinerarySchema(Tour $tour, string $tourUrl): ?array
    {
        $items = collect($tour->itinerary ?? [])
            ->map(function (array $item, int $index): ?array {
                $title = RichText::normalizePlain((string) data_get($item, 'title'));
                $content = RichText::normalizePlain((string) data_get($item, 'content'));
                $label = trim($title !== '' ? 'Ngày '.($index + 1).' - '.$title : 'Ngày '.($index + 1));

                if ($label === '' && $content === '') {
                    return null;
                }

                return $this->schemaNode([
                    '@type' => 'ListItem',
                    'item' => $this->schemaNode([
                        '@type' => 'Thing',
                        'description' => $content,
                        'name' => $label,
                    ]),
                    'name' => $label,
                    'position' => $index + 1,
                ]);
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $tourUrl.'#itinerary',
            '@type' => 'ItemList',
            'itemListElement' => $items->all(),
            'name' => 'Lịch trình tour',
            'numberOfItems' => $items->count(),
        ]);
    }

    protected function listingPrimaryEntityGraph(
        array $page,
        string $listingUrl,
        Collection $tours,
        array $pageReviewItems,
        ?array $pageReviewSummary,
    ): array {
        $destination = data_get($page, 'fixed_destination');

        if (! $destination instanceof Destination) {
            return ['ref' => null, 'nodes' => []];
        }

        $schemaId = $listingUrl.'#product';
        $review = $this->tourReviewSchema($pageReviewItems);

        return [
            'ref' => ['@id' => $schemaId],
            'nodes' => [$this->schemaNode([
                '@id' => $schemaId,
                '@type' => 'Product',
                'aggregateRating' => ReviewContent::aggregateRatingSchema($pageReviewSummary),
                'description' => data_get($page, 'page_description'),
                'image' => $this->imageObjectSchema($this->listingCollectionPageImageUrl($page), $destination->name),
                'mainEntityOfPage' => $listingUrl,
                'name' => $destination->name,
                'offers' => $this->listingAggregateOfferSchema($tours, $listingUrl),
                'review' => $review,
                'url' => $listingUrl,
            ])],
        ];
    }

    protected function listingAggregateOfferSchema(Collection $tours, string $listingUrl): ?array
    {
        $prices = $tours
            ->filter(fn (mixed $tour): bool => $tour instanceof Tour)
            ->map(fn (Tour $tour) => FrontsiteCardData::tour($tour)['price_value'] ?? null)
            ->filter(fn (mixed $price): bool => is_numeric($price) && (int) $price > 0)
            ->map(fn (mixed $price): int => (int) $price)
            ->values();

        if ($prices->isEmpty()) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $listingUrl.'#offers',
            '@type' => 'AggregateOffer',
            'availability' => $this->offerAvailabilityValue(),
            'highPrice' => $prices->max(),
            'lowPrice' => $prices->min(),
            'offerCount' => $prices->count(),
            'priceCurrency' => 'VND',
            'url' => $listingUrl,
        ]);
    }

    protected function landingPageSchemaGraph(LandingPage $landing, array $blocks, string $landingUrl): array
    {
        $listNodes = [];
        $graphNodes = [];
        $primaryListRef = null;

        foreach ($blocks as $index => $block) {
            $name = trim((string) data_get($block, 'title'));
            $description = trim((string) data_get($block, 'description'));
            $resolvedGraphs = match ($block['type'] ?? null) {
                LandingPageBlocks::TYPE_TOUR_LIST => array_filter([
                    $this->landingTourBlockSchemaGraph(
                        collect(data_get($block, 'items', [])),
                        $landingUrl.'#tour-list-'.($index + 1),
                        $name !== '' ? $name : null,
                        $description !== '' ? $description : null,
                    ),
                ]),
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE => array_filter([
                    $this->landingTourBlockSchemaGraph(
                        collect(data_get($block, 'items', [])),
                        $landingUrl.'#hero-demo-'.($index + 1),
                        $name !== '' ? $name : 'Tour nổi bật',
                        $description !== '' ? $description : null,
                    ),
                ]),
                LandingPageBlocks::TYPE_BLOG_LIST => array_filter([
                    [
                        'list' => $this->blogItemListSchema(
                            collect(data_get($block, 'items', [])),
                            $landingUrl.'#blog-list-'.($index + 1),
                            $name !== '' ? $name : null,
                            $description !== '' ? $description : null,
                        ),
                        'nodes' => [],
                    ],
                ]),
                LandingPageBlocks::TYPE_TOPIC_RAIL => array_filter([
                    [
                        'list' => $this->taxonomyItemListSchema(
                            collect(data_get($block, 'items', [])),
                            $landingUrl.'#topic-rail-'.($index + 1),
                            'tour_category',
                            $name !== '' ? $name : null,
                            $description !== '' ? $description : null,
                        ),
                        'nodes' => [],
                    ],
                ]),
                LandingPageBlocks::TYPE_REGION_RAIL => array_filter([
                    [
                        'list' => $this->taxonomyItemListSchema(
                            collect(data_get($block, 'items', [])),
                            $landingUrl.'#region-rail-'.($index + 1),
                            'region',
                            $name !== '' ? $name : 'Vùng miền nổi bật',
                            $description !== '' ? $description : 'Lướt nhanh các hub vùng miền đang có tour hoạt động để khoanh vùng khu vực phù hợp trước khi đi sâu vào từng điểm đến cụ thể.',
                        ),
                        'nodes' => [],
                    ],
                ]),
                LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS => $this->regionTaxonomyTabSchemaGraphs(
                    is_array($block) ? $block : null,
                    $landingUrl,
                    'region-taxonomy-tab-'.($index + 1),
                ),
                LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS => $this->tourTaxonomyTabSchemaGraphs(
                    is_array($block) ? $block : null,
                    $landingUrl,
                    'tour-taxonomy-tab-'.($index + 1),
                ),
                default => [],
            };

            foreach ($resolvedGraphs as $graph) {
                $listNode = data_get($graph, 'list');

                if (is_array($listNode) && $listNode !== []) {
                    $primaryListRef ??= ['@id' => $listNode['@id']];
                    $listNodes[] = $listNode;
                }

                foreach ((array) data_get($graph, 'nodes', []) as $node) {
                    if (is_array($node) && $node !== []) {
                        $graphNodes[] = $node;
                    }
                }
            }
        }

        $faqItems = collect($blocks)
            ->where('type', LandingPageBlocks::TYPE_FAQ)
            ->pluck('items')
            ->flatten(1)
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->all();
        $pageNode = $this->schemaNode([
            '@id' => $landingUrl.'#webpage',
            '@type' => $primaryListRef ? 'CollectionPage' : 'WebPage',
            'description' => $this->landingSummaryText($landing),
            'image' => $this->landingImageSchema($landing),
            'mainEntity' => $primaryListRef,
            'mainEntityOfPage' => $landingUrl,
            'name' => $landing->title,
            'primaryImageOfPage' => $this->landingPrimaryImageSchema($landing, $landingUrl),
            'url' => $landingUrl,
        ]);

        return array_values(array_filter([
            $pageNode,
            ...$listNodes,
            ...$this->uniqueSchemaNodes($graphNodes),
            $this->faqSchema($faqItems),
        ]));
    }

    protected function landingPrimaryImageSchema(?LandingPage $landing, string $landingUrl): ?array
    {
        if (! $landing) {
            return null;
        }

        return $this->imageObjectSchema(
            $this->landingPrimaryImageUrl($landing),
            $this->landingImageCaption($landing),
            $landingUrl.'#primary-image',
        );
    }

    protected function landingPrimaryImageUrl(?LandingPage $landing, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        if (! $landing) {
            return null;
        }

        $hero = $this->resolveLandingHero($landing);
        $candidatePaths = match ($size) {
            FrontsiteMedia::SIZE_SMALL => [
                'media_small_url',
                'slides.0.og_image_url',
                'media_url',
                'slides.0.image_url',
                'slides.0.mobile_image_url',
                'slides.0.thumbnail_url',
            ],
            default => [
                'media_url',
                'slides.0.image_url',
                'slides.0.mobile_image_url',
                'slides.0.thumbnail_url',
                'media_small_url',
                'slides.0.og_image_url',
            ],
        };
        $imageUrl = collect($candidatePaths)
            ->map(fn (string $path) => trim((string) data_get($hero, $path)))
            ->first(fn (string $url) => $url !== '');

        return is_string($imageUrl) && $imageUrl !== '' ? $imageUrl : null;
    }

    protected function breadcrumbSchema(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $current
     * @return array<int, array{label: string, url?: string}>
     */
    protected function tourHierarchyBreadcrumbs(
        TourScope|string|null $scope,
        ?Region $region = null,
        ?Destination $destination = null,
        ?array $current = null,
    ): array {
        $tourScope = $this->resolveTourBreadcrumbScope($scope, $region, $destination);
        $items = [
            ['label' => 'Trang chủ', 'url' => route('home')],
        ];

        if ($tourScope) {
            $items[] = ['label' => $tourScope->label(), 'url' => route($tourScope->routeName())];
        }

        if ($region instanceof Region) {
            $items[] = ['label' => $region->name, 'url' => route('regions.show', $region)];
        }

        if ($destination instanceof Destination) {
            $items[] = ['label' => $destination->name, 'url' => route('destinations.show', $destination)];
        }

        if (is_array($current) && filled($current['label'] ?? null)) {
            $currentItem = ['label' => trim((string) $current['label'])];
            $currentUrl = trim((string) ($current['url'] ?? ''));

            if ($currentUrl !== '') {
                $currentItem['url'] = $currentUrl;
            }

            $items[] = $currentItem;
        }

        return $this->uniqueBreadcrumbItems($items);
    }

    protected function resolveTourBreadcrumbScope(
        TourScope|string|null $scope,
        ?Region $region = null,
        ?Destination $destination = null,
    ): ?TourScope {
        if ($scope instanceof TourScope) {
            return $scope;
        }

        foreach ([$scope, $destination?->scope, $region?->scope] as $candidate) {
            $value = trim((string) $candidate);

            if ($value !== '' && ($tourScope = TourScope::tryFrom($value))) {
                return $tourScope;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function uniqueBreadcrumbItems(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => filled($item['label'] ?? null))
            ->unique(function (array $item): string {
                $url = trim((string) ($item['url'] ?? ''));

                if ($url !== '') {
                    return 'url:'.$url;
                }

                return 'label:'.Str::lower(trim((string) $item['label']));
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{name: string, url: string}>
     */
    protected function breadcrumbSchemaItems(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => filled($item['label'] ?? null) && filled($item['url'] ?? null))
            ->map(fn (array $item): array => [
                'name' => trim((string) $item['label']),
                'url' => trim((string) $item['url']),
            ])
            ->values()
            ->all();
    }

    protected function faqSchema(array $faqItems): ?array
    {
        $mainEntity = collect($faqItems)
            ->map(function (array $item): ?array {
                $question = RichText::normalizePlain((string) data_get($item, 'question'));
                $answer = RichText::normalizePlain((string) data_get($item, 'answer'));

                if ($question === '' || $answer === '') {
                    return null;
                }

                return [
                    '@type' => 'Question',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer,
                    ],
                    'name' => $question,
                ];
            })
            ->filter()
            ->values();

        if ($mainEntity->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity->all(),
        ];
    }

    protected function graphSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($items)),
        ];
    }

    protected function homepageSchemaGraph(
        ?LandingPage $landing,
        Collection $featuredTours,
        Collection $homeTourCategories,
        Collection $homeDestinations,
        Collection $homeBlogPosts,
        Collection $homeLayoutBlocks,
        ?string $homeBlogListName,
        ?string $homeBlogListDescription,
        array $homeFaqItems,
        string $homeUrl,
    ): array {
        $featuredTourList = $this->homepageFeaturedTourItemListSchema($featuredTours, $homeUrl.'#featured-tour-list');
        $topicList = $featuredTourList ? null : $this->homepageTourCategoryItemListSchema($homeTourCategories, $homeUrl.'#tour-topic-list');
        $fallbackList = $featuredTourList
            ?: $topicList
            ?: $this->homepageDestinationItemListSchema($homeDestinations, $homeUrl.'#destination-list');
        $blogPreviewList = $this->homepageBlogPreviewItemListSchema(
            $homeBlogPosts,
            $homeUrl.'#blog-preview-list',
            $homeBlogListName,
            $homeBlogListDescription,
        );
        $blogWidgetLists = $this->homepageBlogWidgetItemListSchemas($homeLayoutBlocks, $homeUrl);
        $blogListRefs = collect([$blogPreviewList, ...$blogWidgetLists])
            ->filter(fn (mixed $listNode): bool => is_array($listNode) && filled($listNode['@id'] ?? null))
            ->map(fn (array $listNode): array => ['@id' => $listNode['@id']])
            ->values()
            ->all();
        $mainEntityRef = $fallbackList ? ['@id' => $fallbackList['@id']] : null;
        $pageNode = $this->schemaNode([
            '@id' => $homeUrl.'#webpage',
            '@type' => 'WebPage',
            'description' => $landing?->meta_description ?: $this->site->current()->seo_description,
            'hasPart' => $blogListRefs,
            'image' => $this->landingImageSchema($landing),
            'isPartOf' => ['@id' => $this->websiteId()],
            'mainEntity' => $mainEntityRef,
            'mainEntityOfPage' => $homeUrl,
            'name' => $landing?->hero_title ?: $this->site->current()->site_name,
            'primaryImageOfPage' => $this->landingPrimaryImageSchema($landing, $homeUrl),
            'url' => $homeUrl,
        ]);

        return array_values(array_filter([
            $this->breadcrumbSchema([
                [
                    'name' => 'Trang chủ',
                    'url' => $homeUrl,
                ],
            ]),
            $pageNode,
            $fallbackList,
            $blogPreviewList,
            ...$blogWidgetLists,
            $this->faqSchema($homeFaqItems),
        ]));
    }

    protected function homepageFeaturedTourItemListSchema(Collection $tours, string $listId): ?array
    {
        return $this->tourItemListSchema(
            $tours
                ->filter(fn (mixed $tour) => $tour instanceof Tour)
                ->unique(fn (Tour $tour) => $tour->getKey())
                ->take(12)
                ->values(),
            $listId,
            'Tour nổi bật trên trang chủ',
            'Danh sách tour nổi bật đang hiển thị trên homepage để người xem so sánh nhanh ngày khởi hành, thời lượng, giá và đánh giá trước khi mở trang chi tiết.',
            true,
        );
    }

    protected function homepageFaqItems(?LandingPage $landing): array
    {
        $faqItems = collect($landing?->faq_items ?? [])
            ->filter(fn (array $item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->take(4)
            ->values()
            ->all();

        if ($faqItems !== []) {
            return $faqItems;
        }

        return [
            [
                'question' => 'Làm sao chọn tour phù hợp khi chưa chốt được điểm đến?',
                'answer' => 'Bạn có thể bắt đầu từ nhóm tour trong nước, nước ngoài hoặc tour đoàn. Nếu vẫn chưa chắc phương án nào phù hợp, hãy gửi yêu cầu để đội ngũ tư vấn theo ngân sách, thời gian và số lượng khách.',
            ],
            [
                'question' => 'Trang có hỗ trợ tour đoàn và hành trình thiết kế riêng không?',
                'answer' => 'Có. Hải Đăng Travel có nhóm tour đoàn và luồng nhận yêu cầu riêng cho nhu cầu công ty, gia đình đông người, MICE hoặc chương trình cần thiết kế theo lịch trình riêng.',
            ],
            [
                'question' => 'Ngoài tour trọn gói, tôi có thể yêu cầu thêm dịch vụ nào?',
                'answer' => 'Bạn có thể hỏi thêm về visa, vé máy bay, thuê xe, sim du lịch và các hỗ trợ trước chuyến đi ngay trong cùng một yêu cầu tư vấn.',
            ],
            [
                'question' => 'Nếu tour chưa có giá hoặc lịch khởi hành phù hợp thì sao?',
                'answer' => 'Một số tour sẽ hiển thị trạng thái liên hệ thay vì báo giá cố định. Khi đó bạn chỉ cần gửi yêu cầu để nhận tư vấn theo ngày đi, điểm khởi hành và quy mô đoàn thực tế.',
            ],
        ];
    }

    protected function homepageTourCategoryItemListSchema(Collection $categories, string $listId): ?array
    {
        return $this->taxonomyItemListSchema(
            $categories->take(8),
            $listId,
            'tour_category',
            'Chủ đề tour nổi bật',
            'Lướt nhanh các chủ đề tour đang có hành trình hoạt động để khoanh vùng nhu cầu phù hợp trước khi so sánh điểm đến, ngày đi và mức giá.',
        );
    }

    protected function homepageDestinationItemListSchema(Collection $destinations, string $listId): ?array
    {
        return $this->taxonomyItemListSchema(
            $destinations->take(8),
            $listId,
            'destination',
            'Điểm đến nổi bật',
            'Lướt nhanh các hub điểm đến đang có tour hoạt động để chọn hướng đi phù hợp trước khi xem sâu hơn ở phần Điểm đến yêu thích.',
        );
    }

    protected function homepageBlogPreviewItemListSchema(
        Collection $posts,
        string $listId,
        ?string $name = null,
        ?string $description = null,
    ): ?array {
        return $this->blogItemListSchema(
            $posts
                ->filter(fn (mixed $post): bool => $post instanceof BlogPost)
                ->unique(fn (BlogPost $post): int => (int) $post->getKey())
                ->take(12)
                ->values(),
            $listId,
            trim((string) $name) !== '' ? trim((string) $name) : 'Cẩm nang du lịch',
            $description,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function homepageBlogWidgetItemListSchemas(Collection $blocks, string $homeUrl): array
    {
        return $blocks
            ->values()
            ->filter(fn (mixed $block): bool => is_array($block) && ($block['type'] ?? null) === LandingPageBlocks::TYPE_BLOG_LIST)
            ->map(function (array $block, int $index) use ($homeUrl): ?array {
                $name = trim((string) data_get($block, 'title'));
                $description = trim((string) data_get($block, 'description'));
                $uuid = trim((string) data_get($block, 'uuid'));
                $listId = $uuid !== ''
                    ? $homeUrl.'#home-blog-list-'.$uuid
                    : $homeUrl.'#home-blog-list-'.($index + 1);

                return $this->blogItemListSchema(
                    collect(data_get($block, 'items', [])),
                    $listId,
                    $name !== '' ? $name : 'Bài viết nổi bật',
                    $description !== '' ? $description : null,
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{list: array|null, nodes: array<int, array<string, mixed>>}>
     */
    protected function tourTaxonomyTabSchemaGraphs(?array $block, string $pageUrl, string $listKey): array
    {
        if (! is_array($block)) {
            return [];
        }

        return collect(data_get($block, 'tabs', []))
            ->values()
            ->map(function (array $tab, int $tabIndex) use ($pageUrl, $listKey): ?array {
                $tabName = $this->tourTaxonomyTabListName($tab);
                $tabDescription = trim((string) data_get($tab, 'description'));

                return $this->landingTourBlockSchemaGraph(
                    collect(data_get($tab, 'items', [])),
                    $pageUrl.'#'.$listKey.'-'.($tabIndex + 1),
                    $tabName,
                    $tabDescription !== '' ? $tabDescription : null,
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{list: array|null, nodes: array<int, array<string, mixed>>}>
     */
    protected function regionTaxonomyTabSchemaGraphs(?array $block, string $pageUrl, string $listKey): array
    {
        if (! is_array($block)) {
            return [];
        }

        $taxonomyType = ((string) data_get($block, 'card_source_type')) === 'tour_category'
            ? 'tour_category'
            : 'destination';

        return collect(data_get($block, 'tabs', []))
            ->values()
            ->map(function (array $tab, int $tabIndex) use ($pageUrl, $listKey, $taxonomyType): ?array {
                $tabName = $this->regionTaxonomyTabListName($tab, $taxonomyType);
                $tabDescription = trim((string) data_get($tab, 'description'));
                $listNode = $this->taxonomyItemListSchema(
                    collect(data_get($tab, 'items', [])),
                    $pageUrl.'#'.$listKey.'-'.($tabIndex + 1),
                    $taxonomyType,
                    $tabName,
                    $tabDescription !== '' ? $tabDescription : null,
                );

                if (! $listNode) {
                    return null;
                }

                return [
                    'list' => $listNode,
                    'nodes' => [],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function tourTaxonomyTabListName(array $tab): string
    {
        $title = trim((string) data_get($tab, 'title'));

        if ($title !== '') {
            return $title;
        }

        $label = trim((string) data_get($tab, 'label'));

        if ($label !== '') {
            return $label;
        }

        $sourceLabel = trim((string) data_get($tab, 'source_label'));

        return $sourceLabel !== '' ? $sourceLabel : 'Nhóm tour theo taxonomy';
    }

    protected function regionTaxonomyTabListName(array $tab, string $taxonomyType): string
    {
        $title = trim((string) data_get($tab, 'title'));

        if ($title !== '') {
            return $title;
        }

        $label = trim((string) data_get($tab, 'label'));

        if ($label !== '') {
            return $label;
        }

        return $taxonomyType === 'tour_category'
            ? 'Chủ đề tour theo vùng miền'
            : 'Điểm đến theo vùng miền';
    }

    /**
     * @return array{list: array|null, nodes: array<int, array<string, mixed>>}|null
     */
    protected function landingTourBlockSchemaGraph(
        Collection $tours,
        string $listId,
        ?string $name = null,
        ?string $description = null,
    ): ?array {
        $graphNodes = [];
        $items = $tours
            ->filter(fn (mixed $tour) => $tour instanceof Tour)
            ->values()
            ->map(function (Tour $tour, int $index) use (&$graphNodes): array {
                $tourUrl = route('tours.show', $tour);
                $offerGraph = $this->tourOfferGraph($tour, $tourUrl);
                $offerRef = is_array($offerGraph['primary'] ?? null) && filled(data_get($offerGraph['primary'], '@id'))
                    ? ['@id' => data_get($offerGraph['primary'], '@id')]
                    : null;
                $aggregateRating = $this->tourAggregateRatingSchema($tour);

                if ($this->hasProductRichResultProperty($offerRef, $aggregateRating)) {
                    $graphNodes[] = $this->landingTourProductSchema($tour, $tourUrl, $offerRef);
                    $graphNodes[] = $this->landingTouristTripSchema($tour, $tourUrl, $offerRef);
                } else {
                    $graphNodes[] = $this->tourTouristTripSchema($tour, $tourUrl, null, null, $this->tourSchemaId($tour));
                }

                if (is_array($offerGraph['primary'] ?? null) && ($offerGraph['primary'] ?? []) !== []) {
                    $graphNodes[] = $offerGraph['primary'];
                }

                foreach ((array) ($offerGraph['nodes'] ?? []) as $offerNode) {
                    if (is_array($offerNode) && $offerNode !== []) {
                        $graphNodes[] = $offerNode;
                    }
                }

                foreach ($this->tourPlaceSchemas($tour) as $placeNode) {
                    if (is_array($placeNode) && $placeNode !== []) {
                        $graphNodes[] = $placeNode;
                    }
                }

                return $this->schemaNode([
                    '@type' => 'ListItem',
                    'item' => ['@id' => $this->tourSchemaId($tour)],
                    'position' => $index + 1,
                ]);
            });

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'list' => $this->schemaNode([
                '@id' => $listId,
                '@type' => 'ItemList',
                'description' => $description,
                'itemListElement' => $items->all(),
                'name' => $this->normalizeSchemaListName($name, 'Danh sách tour'),
                'numberOfItems' => $items->count(),
            ]),
            'nodes' => $this->uniqueSchemaNodes($graphNodes),
        ];
    }

    protected function collectionPageSchema(
        string $pageUrl,
        string $name,
        ?string $description = null,
        ?array $mainEntity = null,
        ?array $about = null,
        ?string $imageUrl = null,
        ?string $partOfPageId = null,
        ?array $associatedMedia = null,
    ): array {
        $pageId = $pageUrl.'#webpage';

        return $this->schemaNode([
            '@id' => $pageId,
            '@type' => 'CollectionPage',
            'about' => $about,
            'associatedMedia' => $associatedMedia,
            'description' => $description,
            'image' => $this->imageObjectSchema($imageUrl, $name),
            'isPartOf' => $partOfPageId ? ['@id' => $partOfPageId] : null,
            'mainEntity' => $mainEntity,
            'mainEntityOfPage' => $pageUrl,
            'name' => $name,
            'primaryImageOfPage' => $this->imageObjectSchema($imageUrl, $name, $pageId.'#primary-image'),
            'url' => $pageUrl,
        ]);
    }

    protected function listingCollectionPageImageUrl(array $page, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        $pageOwner = data_get($page, 'fixed_category')
            ?: data_get($page, 'fixed_country')
            ?: data_get($page, 'fixed_destination')
            ?: data_get($page, 'fixed_region');

        if (is_object($pageOwner)) {
            return $this->modelMediaUrl($pageOwner, 'avatar', $size);
        }

        return $this->landingPrimaryImageUrl(data_get($page, 'landing'), $size);
    }

    protected function shouldUseDetailedListingCollectionPage(array $page): bool
    {
        return data_get($page, 'fixed_category') instanceof TourCategory
            || data_get($page, 'fixed_country') instanceof Destination
            || data_get($page, 'fixed_destination') instanceof Destination
            || data_get($page, 'fixed_region') instanceof Region;
    }

    protected function landingTourProductSchema(Tour $tour, string $tourUrl, ?array $offerRef = null): array
    {
        $aggregateRating = $this->tourAggregateRatingSchema($tour);

        return $this->schemaNode([
            '@id' => $this->tourSchemaId($tour),
            '@type' => 'Product',
            'additionalProperty' => $this->tourAdditionalPropertySchema($tour),
            'aggregateRating' => $aggregateRating,
            'brand' => $this->tourBrandSchema(),
            'category' => $this->tourProductCategory($tour),
            'description' => $this->tourProductDescription($tour),
            'image' => $this->tourImageSchema($tour),
            'isRelatedTo' => ['@id' => $this->touristTripSchemaId($tour)],
            'keywords' => $this->tourProductKeywords($tour),
            'mainEntityOfPage' => $tourUrl,
            'name' => $tour->title,
            'offers' => $offerRef,
            'productID' => (string) $tour->getKey(),
            'sku' => $this->tourProductSku($tour),
            'slogan' => $this->tourProductSlogan(),
            'url' => $tourUrl,
        ]);
    }

    protected function landingTouristTripSchema(Tour $tour, string $tourUrl, ?array $offerRef = null): array
    {
        return $this->tourTouristTripSchema($tour, $tourUrl, $offerRef, null, $this->touristTripSchemaId($tour));
    }

    protected function touristTripSchemaId(Tour $tour): string
    {
        return route('tours.show', $tour).'#trip';
    }

    protected function tourAggregateRatingSchema(Tour $tour): ?array
    {
        if (! filled($tour->rating_average) || ! filled($tour->rating_count)) {
            return null;
        }

        return ReviewContent::aggregateRatingSchema([
            'average_value' => (float) $tour->rating_average,
            'best_rating' => 5,
            'count' => (int) $tour->rating_count,
            'worst_rating' => 1,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    protected function uniqueSchemaNodes(array $nodes): array
    {
        return collect($nodes)
            ->filter(fn (mixed $node) => is_array($node) && $node !== [])
            ->reverse()
            ->unique(fn (array $node) => data_get($node, '@id') ?: md5(json_encode($node)))
            ->reverse()
            ->values()
            ->all();
    }

    protected function tourItemListSchema(
        Collection $tours,
        string $listId,
        ?string $name = null,
        ?string $description = null,
        bool $includeAggregateRating = false,
    ): ?array {
        $items = $tours
            ->values()
            ->map(function (Tour $tour, int $index) use ($includeAggregateRating): array {
                $tourUrl = route('tours.show', $tour);

                return $this->schemaNode([
                    '@type' => 'ListItem',
                    'item' => $this->tourListItemEntitySchema($tour, $tourUrl, $includeAggregateRating),
                    'position' => $index + 1,
                ]);
            });

        if ($items->isEmpty()) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $listId,
            '@type' => 'ItemList',
            'description' => $description,
            'itemListElement' => $items->all(),
            'name' => $this->normalizeSchemaListName($name, 'Danh sách tour'),
            'numberOfItems' => $items->count(),
        ]);
    }

    protected function tourListItemEntitySchema(Tour $tour, string $tourUrl, bool $includeAggregateRating = false): array
    {
        $offer = $this->tourOfferSchema($tour, $tourUrl);
        $aggregateRating = $includeAggregateRating ? $this->tourAggregateRatingSchema($tour) : null;

        if (! $this->hasProductRichResultProperty($offer, $aggregateRating)) {
            return $this->tourTouristTripSchema($tour, $tourUrl, null, null, $this->tourSchemaId($tour));
        }

        return $this->schemaNode([
            '@id' => $this->tourSchemaId($tour),
            '@type' => 'Product',
            'aggregateRating' => $aggregateRating,
            'brand' => $this->tourBrandSchema(),
            'category' => $this->tourProductCategory($tour),
            'description' => $this->tourProductDescription($tour),
            'image' => $this->tourImageSchema($tour),
            'keywords' => $this->tourProductKeywords($tour),
            'name' => $tour->title,
            'offers' => $offer,
            'productID' => (string) $tour->getKey(),
            'sku' => $this->tourProductSku($tour),
            'url' => $tourUrl,
        ]);
    }

    protected function taxonomyItemListSchema(
        Collection $items,
        string $listId,
        string $taxonomyType,
        ?string $name = null,
        ?string $description = null,
    ): ?array {
        $listItems = $items
            ->values()
            ->map(function (mixed $item, int $index) use ($taxonomyType): ?array {
                $collectionPage = $this->taxonomyCollectionPageSchema($item, $taxonomyType);

                if (! $collectionPage) {
                    return null;
                }

                return $this->schemaNode([
                    '@type' => 'ListItem',
                    'item' => $collectionPage,
                    'position' => $index + 1,
                ]);
            })
            ->filter()
            ->values();

        if ($listItems->isEmpty()) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $listId,
            '@type' => 'ItemList',
            'description' => $description,
            'itemListElement' => $listItems->all(),
            'name' => $this->normalizeSchemaListName($name, $this->defaultTaxonomyListName($taxonomyType)),
            'numberOfItems' => $listItems->count(),
        ]);
    }

    protected function normalizeSchemaListName(?string $name, string $fallback): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $name : $fallback;
    }

    protected function defaultTaxonomyListName(string $taxonomyType): string
    {
        return match ($taxonomyType) {
            'tour_category' => 'Danh sách chủ đề tour',
            'country' => 'Danh sách quốc gia',
            'region' => 'Danh sách vùng miền',
            default => 'Danh sách điểm đến',
        };
    }

    protected function taxonomyCollectionPageSchema(mixed $item, string $taxonomyType): ?array
    {
        $pageUrl = $this->taxonomyPageUrl($item, $taxonomyType);
        $name = trim((string) data_get($item, 'name'));

        if (! is_object($item) || $pageUrl === null || $name === '') {
            return null;
        }

        $description = RichText::normalizePlain((string) (data_get($item, 'excerpt') ?: data_get($item, 'content')));

        return $this->collectionPageSchema(
            $pageUrl,
            $name,
            $description !== '' ? $description : null,
            null,
            null,
            $this->modelMediaUrl($item, 'avatar'),
        );
    }

    protected function taxonomyPageUrl(mixed $item, string $taxonomyType): ?string
    {
        if (! is_object($item)) {
            return null;
        }

        return match ($taxonomyType) {
            'tour_category' => $item instanceof TourCategory ? route('tour-categories.show', $item) : null,
            'country' => $item instanceof Destination ? route('countries.show', ['slug' => $item->slug]) : null,
            'destination' => $item instanceof Destination ? route('destinations.show', $item) : null,
            'region' => $item instanceof Region ? route('regions.show', $item) : null,
            default => null,
        };
    }

    protected function serviceListingImageUrl(
        ?ContentCategory $selectedServiceCategory,
        ?LandingPage $landing,
        string $size = FrontsiteMedia::SIZE_FULL,
    ): ?string
    {
        return ($selectedServiceCategory ? $this->modelMediaUrl($selectedServiceCategory, 'avatar', $size) : null)
            ?: $this->landingPrimaryImageUrl($landing, $size)
            ?: $this->siteMediaUrl('og_image', $size);
    }

    protected function blogListingImageUrl(
        ?ContentCategory $selectedBlogCategory,
        ?LandingPage $landing,
        string $size = FrontsiteMedia::SIZE_FULL,
    ): ?string
    {
        return ($selectedBlogCategory ? $this->modelMediaUrl($selectedBlogCategory, 'avatar', $size) : null)
            ?: $this->landingPrimaryImageUrl($landing, $size)
            ?: $this->siteMediaUrl('og_image', $size);
    }

    protected function blogListingAssociatedMediaSchema(
        ?ContentCategory $selectedBlogCategory,
        ?LandingPage $landing,
        Collection $posts,
    ): ?array {
        $candidates = [];
        $fallbackCaption = trim((string) ($selectedBlogCategory?->name ?: $landing?->title ?: 'Blog'));

        if ($selectedBlogCategory) {
            $candidates[] = [
                'url' => $this->modelMediaUrl($selectedBlogCategory, 'avatar', FrontsiteMedia::SIZE_FULL, null),
                'caption' => $selectedBlogCategory->name,
            ];

            if ($selectedBlogCategory->parent instanceof ContentCategory) {
                $candidates[] = [
                    'url' => $this->modelMediaUrl($selectedBlogCategory->parent, 'avatar', FrontsiteMedia::SIZE_FULL, null),
                    'caption' => $selectedBlogCategory->parent->name,
                ];
            }
        } elseif ($landing) {
            $candidates[] = [
                'url' => $this->landingPrimaryImageUrl($landing),
                'caption' => $this->landingImageCaption($landing),
            ];
        }

        foreach ($posts as $post) {
            if (! $post instanceof BlogPost) {
                continue;
            }

            foreach ($this->blogArticleImageCandidates($post) as $candidate) {
                $candidates[] = $candidate;
            }
        }

        $images = $this->imageObjectListSchema($candidates, $fallbackCaption !== '' ? $fallbackCaption : null);

        return $images !== null ? array_slice($images, 0, 24) : null;
    }

    protected function serviceItemListSchema(
        Collection $services,
        string $listId,
        ?string $name = null,
        ?string $description = null,
    ): ?array
    {
        $items = $services
            ->values()
            ->map(function (Service $service, int $index): array {
                $serviceUrl = route('services.show', $service);

                return $this->schemaNode([
                    '@type' => 'ListItem',
                    'item' => $this->schemaNode([
                        '@id' => $serviceUrl.'#service',
                        '@type' => 'Service',
                        'category' => $service->category?->name,
                        'description' => $service->excerpt ?: null,
                        'image' => $this->modelImageSchema($service, 'cover', trim((string) (data_get($service, 'cover_alt') ?: $service->title))),
                        'name' => $service->title,
                        'provider' => $this->organizationSummarySchema(),
                        'serviceType' => $service->category?->name ?: $service->title,
                        'url' => $serviceUrl,
                    ]),
                    'position' => $index + 1,
                ]);
            });

        if ($items->isEmpty()) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $listId,
            '@type' => 'ItemList',
            'description' => $description,
            'itemListElement' => $items->all(),
            'name' => $this->normalizeSchemaListName($name, 'Danh sách dịch vụ'),
            'numberOfItems' => $items->count(),
        ]);
    }

    protected function blogItemListSchema(
        Collection $posts,
        string $listId,
        ?string $name = null,
        ?string $description = null,
    ): ?array {
        $items = $posts
            ->filter(fn (mixed $post): bool => $post instanceof BlogPost)
            ->values()
            ->map(function (BlogPost $post, int $index): array {
                $postUrl = FrontsiteUrls::canonicalBlogPost($post);

                return $this->schemaNode([
                    '@type' => 'ListItem',
                    'item' => $this->schemaNode([
                        '@id' => $postUrl.'#article',
                        '@type' => 'BlogPosting',
                        'articleSection' => $post->category?->name,
                        'author' => $this->articleAuthorSchema($post->author_name),
                        'dateModified' => optional($post->updated_at)->toAtomString() ?: optional($post->published_at)->toAtomString(),
                        'datePublished' => optional($post->published_at)->toAtomString(),
                        'associatedMedia' => $this->blogArticleAssociatedMediaSchema($post),
                        'description' => $post->excerpt ?: null,
                        'headline' => $post->title,
                        'image' => $this->blogArticleImageSchema($post),
                        'mainEntityOfPage' => $this->schemaNode([
                            '@type' => 'WebPage',
                            '@id' => $postUrl,
                        ]),
                        'publisher' => $this->organizationReference(),
                        'url' => $postUrl,
                    ]),
                    'position' => $index + 1,
                ]);
            });

        if ($items->isEmpty()) {
            return null;
        }

        return $this->schemaNode([
            '@id' => $listId,
            '@type' => 'ItemList',
            'description' => $description,
            'itemListElement' => $items->all(),
            'name' => $this->normalizeSchemaListName($name, 'Danh sách bài viết'),
            'numberOfItems' => $items->count(),
        ]);
    }

    protected function currentPublicDepartures(Tour $tour): Collection
    {
        $today = \Illuminate\Support\Carbon::today(config('app.timezone'));

        return $tour->departures
            ->filter(function (TourDeparture $departure) use ($today): bool {
                if (! in_array($departure->status, ['scheduled', 'published'], true)) {
                    return false;
                }

                if (! $departure->departure_date) {
                    return true;
                }

                $departureDate = $departure->departure_date instanceof \Carbon\CarbonInterface
                    ? $departure->departure_date->copy()->startOfDay()
                    : \Illuminate\Support\Carbon::parse($departure->departure_date)->startOfDay();

                return $departureDate->greaterThanOrEqualTo($today);
            })
            ->values();
    }

    protected function tourOfferSchema(Tour $tour, string $tourUrl): ?array
    {
        $pricedDepartures = $this->currentPublicDepartures($tour)
            ->filter(fn (TourDeparture $departure) => in_array($departure->status, ['scheduled', 'published'], true))
            ->filter(function (TourDeparture $departure): bool {
                $price = $departure->sale_price ?: $departure->base_price;

                return is_numeric($price) && (int) $price > 0;
            })
            ->values();
        $prices = $pricedDepartures
            ->map(fn (TourDeparture $departure) => (int) ($departure->sale_price ?: $departure->base_price))
            ->values();

        if ($prices->isNotEmpty()) {
            if ($prices->count() === 1) {
                /** @var TourDeparture|null $departure */
                $departure = $pricedDepartures->first();

                return [
                    '@id' => $tourUrl.'#offer',
                    '@type' => 'Offer',
                    'availability' => $this->offerAvailabilityValue(),
                    'hasMerchantReturnPolicy' => $this->tourMerchantReturnPolicySchema(),
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'price' => $prices->first(),
                    'priceCurrency' => 'VND',
                    'shippingDetails' => $this->tourShippingDetailsSchema(),
                    'url' => $tourUrl,
                ];
            }

            return [
                '@id' => $tourUrl.'#offers',
                '@type' => 'AggregateOffer',
                'availability' => $this->aggregateOfferAvailabilityValue($pricedDepartures),
                'hasMerchantReturnPolicy' => $this->tourMerchantReturnPolicySchema(),
                'highPrice' => $prices->max(),
                'lowPrice' => $prices->min(),
                'offerCount' => $prices->count(),
                'priceCurrency' => 'VND',
                'shippingDetails' => $this->tourShippingDetailsSchema(),
                'url' => $tourUrl,
            ];
        }

        $fallbackPrice = $tour->sale_price ?: $tour->base_price;

        if (! is_numeric($fallbackPrice) || (int) $fallbackPrice <= 0) {
            return null;
        }

        return [
            '@id' => $tourUrl.'#offer',
            '@type' => 'Offer',
            'availability' => $this->offerAvailabilityValue(),
            'hasMerchantReturnPolicy' => $this->tourMerchantReturnPolicySchema(),
            'itemCondition' => 'https://schema.org/NewCondition',
            'price' => (int) $fallbackPrice,
            'priceCurrency' => 'VND',
            'shippingDetails' => $this->tourShippingDetailsSchema(),
            'url' => $tourUrl,
        ];
    }

    protected function tourOfferGraph(Tour $tour, string $tourUrl): array
    {
        $tourReference = ['@id' => $this->tourSchemaId($tour)];
        $offerNodes = $this->currentPublicDepartures($tour)
            ->filter(fn (TourDeparture $departure) => in_array($departure->status, ['scheduled', 'published'], true))
            ->map(fn (TourDeparture $departure) => $this->tourDepartureOfferSchema($tour, $departure, $tourUrl, $tourReference))
            ->filter()
            ->values();

        if ($offerNodes->count() === 1) {
            $offerNode = $offerNodes->first();

            return [
                'nodes' => [],
                'primary' => $offerNode,
            ];
        }

        if ($offerNodes->count() > 1) {
            $aggregateNode = $this->schemaNode([
                '@id' => $tourUrl.'#offers',
                '@type' => 'AggregateOffer',
                'availability' => $this->aggregateOfferAvailabilityFromNodes($offerNodes),
                'hasMerchantReturnPolicy' => $this->tourMerchantReturnPolicySchema(),
                'highPrice' => $offerNodes->max('price'),
                'itemOffered' => $tourReference,
                'lowPrice' => $offerNodes->min('price'),
                'offerCount' => $offerNodes->count(),
                'offers' => $offerNodes
                    ->map(fn (array $offer) => ['@id' => $offer['@id']])
                    ->all(),
                'priceCurrency' => 'VND',
                'seller' => $this->organizationSummarySchema(),
                'shippingDetails' => $this->tourShippingDetailsSchema(),
                'url' => $tourUrl.'#tour-departures',
            ]);

            return [
                'nodes' => $offerNodes->all(),
                'primary' => $aggregateNode,
            ];
        }

        $fallbackOffer = $this->tourOfferSchema($tour, $tourUrl);

        if (! $fallbackOffer) {
            return ['nodes' => [], 'primary' => null];
        }

        $fallbackOffer = $this->schemaNode(array_merge($fallbackOffer, [
            'itemOffered' => $tourReference,
            'seller' => $this->organizationSummarySchema(),
        ]));

        return [
            'nodes' => [],
            'primary' => $fallbackOffer,
        ];
    }

    protected function tourDepartureOfferSchema(
        Tour $tour,
        TourDeparture $departure,
        string $tourUrl,
        array $tourReference,
    ): ?array {
        $price = $departure->sale_price ?: $departure->base_price;

        if (! is_numeric($price) || (int) $price <= 0) {
            return null;
        }

        $dateLabel = $departure->departure_date?->format('d/m/Y');
        $availability = $this->offerAvailabilityValue($departure->status, $departure->available_slots);

        return $this->schemaNode([
            '@id' => $tourUrl.'#offer-'.$departure->getKey(),
            '@type' => 'Offer',
            'availability' => $availability,
            'description' => collect([
                $departure->departure_location ?: $tour->departure_location,
                $dateLabel ? 'Khởi hành '.$dateLabel : null,
                $departure->standard_label ?: $tour->standard_label,
            ])->filter()->implode(' · '),
            'hasMerchantReturnPolicy' => $this->tourMerchantReturnPolicySchema(),
            'itemCondition' => 'https://schema.org/NewCondition',
            'itemOffered' => $tourReference,
            'name' => trim($tour->title.' - '.($dateLabel ?: 'Liên hệ')),
            'price' => (int) $price,
            'priceCurrency' => 'VND',
            'seller' => $this->organizationSummarySchema(),
            'shippingDetails' => $this->tourShippingDetailsSchema(),
            'url' => $tourUrl.'#tour-departures',
        ]);
    }

    protected function tourMerchantReturnPolicySchema(): array
    {
        return $this->schemaNode([
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => 'VN',
            'merchantReturnLink' => FrontsiteUrls::canonicalUrl('/chinh-sach-dat-tour-huy-doi-hoan-tien'),
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
        ]);
    }

    protected function tourShippingDetailsSchema(): array
    {
        return $this->schemaNode([
            '@type' => 'OfferShippingDetails',
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => $this->zeroDayShippingTimeSchema(),
                'transitTime' => $this->zeroDayShippingTimeSchema(),
            ],
            'description' => 'Tour là dịch vụ du lịch; xác nhận đặt tour được xử lý qua kênh tư vấn, không phát sinh phí vận chuyển.',
            'name' => 'Không phát sinh phí vận chuyển',
            'shippingDestination' => [
                '@type' => 'DefinedRegion',
                'addressCountry' => 'VN',
            ],
            'shippingRate' => [
                '@type' => 'MonetaryAmount',
                'currency' => 'VND',
                'value' => 0,
            ],
        ]);
    }

    protected function zeroDayShippingTimeSchema(): array
    {
        return $this->schemaNode([
            '@type' => 'QuantitativeValue',
            'maxValue' => 0,
            'minValue' => 0,
            'unitCode' => 'DAY',
        ]);
    }

    protected function offerAvailabilityValue(?string $status = null, mixed $availableSlots = null): string
    {
        return 'https://schema.org/InStock';
    }

    protected function aggregateOfferAvailabilityValue(Collection $departures): ?string
    {
        $availabilityValues = $departures
            ->map(fn (TourDeparture $departure) => $this->offerAvailabilityValue($departure->status, $departure->available_slots))
            ->filter()
            ->values();

        return $this->resolveAggregateAvailabilityValue($availabilityValues);
    }

    protected function aggregateOfferAvailabilityFromNodes(Collection $offerNodes): ?string
    {
        $availabilityValues = $offerNodes
            ->pluck('availability')
            ->filter()
            ->values();

        return $this->resolveAggregateAvailabilityValue($availabilityValues);
    }

    protected function resolveAggregateAvailabilityValue(Collection $availabilityValues): ?string
    {
        return $availabilityValues->isNotEmpty() ? 'https://schema.org/InStock' : null;
    }

    protected function modelMediaUrl(
        object $model,
        string $collection,
        string $size = FrontsiteMedia::SIZE_FULL,
        array|string|null $directUrlAttributes = 'cover_image_url',
    ): ?string
    {
        return FrontsiteMedia::modelUrl($model, $collection, $size, $directUrlAttributes);
    }

    /**
     * @param  array<string, mixed>  $homeConfig
     * @return array<string, mixed>
     */
    protected function resolveHomepageConfigMedia(array $homeConfig): array
    {
        $processCards = collect(data_get($homeConfig, 'process.cards', []))
            ->filter(fn ($card) => is_array($card))
            ->values();

        if ($processCards->isEmpty()) {
            return $homeConfig;
        }

        $mediaById = Media::query()
            ->whereIn(
                'id',
                $processCards
                    ->pluck('source_library_media_id')
                    ->filter(fn ($mediaId) => is_numeric($mediaId))
                    ->map(fn ($mediaId) => (int) $mediaId)
                    ->unique()
                    ->values(),
            )
            ->get()
            ->keyBy(fn (Media $media) => (int) $media->getKey());

        $resolvedCards = $processCards
            ->map(function (array $card) use ($mediaById): array {
                $fallbackImageUrl = trim((string) ($card['image_url'] ?? ''));
                $mediaId = is_numeric($card['source_library_media_id'] ?? null)
                    ? (int) $card['source_library_media_id']
                    : null;
                $media = $mediaId ? $mediaById->get($mediaId) : null;
                $safeFallbackImageUrl = FrontsiteMedia::validatedUrl($fallbackImageUrl);
                $fullImageUrl = FrontsiteMedia::mediaUrl($media, FrontsiteMedia::SIZE_FULL) ?: $safeFallbackImageUrl;
                $imageUrl = FrontsiteMedia::mediaUrl($media, FrontsiteMedia::SIZE_SMALL) ?: $fullImageUrl;
                $thumbnailUrl = FrontsiteMedia::mediaUrl($media, FrontsiteMedia::SIZE_SMALL) ?: $imageUrl;

                return [
                    ...$card,
                    'full_image_url' => $fullImageUrl,
                    'image_url' => $imageUrl,
                    'thumbnail_url' => $thumbnailUrl,
                ];
            })
            ->all();

        data_set($homeConfig, 'process.cards', $resolvedCards);

        return $homeConfig;
    }

    protected function siteMediaUrl(string $collection, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        $settings = $this->site->current();

        return FrontsiteMedia::modelUrl(
            $settings,
            $collection,
            $size,
            match ($collection) {
                'logo' => 'logo_url',
                'favicon' => 'favicon_url',
                default => 'og_image_url',
            },
        );
    }

    protected function homepageFeaturedTourCategory(string $configuredSlug = ''): ?TourCategory
    {
        return TourCategory::query()
            ->when(
                $configuredSlug !== '',
                fn (Builder $query) => $query->where('slug', $configuredSlug),
                fn (Builder $query) => $query->where(function (Builder $categoryQuery): void {
                    $categoryQuery
                        ->where('slug', Str::slug('Tour Nổi Bật'))
                        ->orWhere('slug', 'noi-bat')
                        ->orWhere('name', 'Tour Nổi Bật')
                        ->orWhere('name', 'Nổi Bật');
                }),
            )
            ->first();
    }

    protected function homepageFeaturedDestinations(array $configuredSlugs = [], int $limit = 8): Collection
    {
        $fetchLimit = min(24, max($limit, $limit * 3));
        $baseQuery = Destination::query()
            ->published()
            ->regularDestinations()
            ->with('media')
            ->whereHas('tours', fn (Builder $query) => $query->published())
            ->withCount(['tours' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($configuredSlugs !== []) {
            $destinations = (clone $baseQuery)
                ->whereIn('slug', $configuredSlugs)
                ->get();

            return $this->prioritizeTaxonomyItemsWithImages(
                $this->sortModelsBySlug($destinations, $configuredSlugs),
                $limit,
            );
        }

        $featuredDestinations = (clone $baseQuery)
            ->where('is_featured', true)
            ->limit($fetchLimit)
            ->get();

        if ($featuredDestinations->isNotEmpty()) {
            return $this->prioritizeTaxonomyItemsWithImages($featuredDestinations, $limit);
        }

        return $this->prioritizeTaxonomyItemsWithImages(
            $baseQuery
                ->limit($fetchLimit)
                ->get(),
            $limit,
        );
    }

    protected function prioritizeTaxonomyItemsWithImages(Collection $items, int $limit): Collection
    {
        return $items
            ->sortByDesc(fn ($item) => filled(FrontsiteMedia::taxonomyAvatarUrl(
                $item,
                FrontsiteMedia::SIZE_SMALL,
                'cover_image_url',
                false,
            )))
            ->take($limit)
            ->values();
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function defaultHomeTourTaxonomyTabs(): array
    {
        $scope = TourScope::Domestic;
        $region = Region::query()
            ->published()
            ->where('scope', $scope->value)
            ->whereHas('tours', fn (Builder $tourQuery) => $tourQuery->published()->forScope($scope))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
        $destination = Destination::query()
            ->published()
            ->regularDestinations()
            ->whereHas('tours', fn (Builder $tourQuery) => $tourQuery->published()->forScope($scope))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
        $category = TourCategory::query()
            ->published()
            ->whereHas('tours', fn (Builder $tourQuery) => $tourQuery->published()->forScope($scope))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();

        return collect([
            $region
                ? LandingPageBlocks::defaultTourTaxonomyTab('region', $region->slug, $region->name, $region->name)
                : null,
            $destination
                ? LandingPageBlocks::defaultTourTaxonomyTab('destination', $destination->slug, $destination->name, $destination->name)
                : null,
            $category
                ? LandingPageBlocks::defaultTourTaxonomyTab('tour_category', $category->slug, $category->name, $category->name)
                : null,
        ])
            ->filter()
            ->values()
            ->all();
    }

    protected function queryHomepageFeaturedTours(TourScope $scope, int $limit, ?TourCategory $featuredTourCategory = null): Collection
    {
        return Tour::query()
            ->published()
            ->forScope($scope)
            ->with([
                'media',
                'departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date'),
                'destination.media',
                'primaryCategory.media',
                'region.media',
            ])
            ->when(
                $featuredTourCategory,
                fn (Builder $query) => $query->whereHas('categories', fn (Builder $taxonomyQuery) => $taxonomyQuery->whereKey($featuredTourCategory->getKey())),
                fn (Builder $query) => $query->where('is_featured', true),
            )
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    protected function sortModelsBySlug(Collection $items, array $slugs): Collection
    {
        $positions = array_flip($slugs);

        return $items
            ->sortBy(fn ($item) => $positions[data_get($item, 'slug')] ?? PHP_INT_MAX)
            ->values();
    }

    protected function applyBudgetAmountConstraint(Builder $query, array $columns, int $min, ?int $max): void
    {
        $query->where(function (Builder $amountQuery) use ($columns, $max, $min): void {
            foreach (array_values($columns) as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $amountQuery->{$method}(function (Builder $columnQuery) use ($column, $max, $min): void {
                    $columnQuery
                        ->whereNotNull($column)
                        ->where($column, '>=', $min);

                    if ($max !== null) {
                        $columnQuery->where($column, '<=', $max);
                    }
                });
            }
        });
    }

    protected function tourBudgetOptions(): array
    {
        return [
            ['value' => 'under-5m', 'label' => 'Dưới 5 triệu'],
            ['value' => '5m-10m', 'label' => 'Từ 5 đến 10 triệu'],
            ['value' => '10m-20m', 'label' => 'Từ 10 đến 20 triệu'],
            ['value' => '20m-plus', 'label' => 'Trên 20 triệu'],
        ];
    }

    protected function tourBudgetRange(string $value): array
    {
        return match ($value) {
            'under-5m' => [0, 5000000],
            '5m-10m' => [5000000, 10000000],
            '10m-20m' => [10000000, 20000000],
            '20m-plus' => [20000000, null],
            default => [null, null],
        };
    }
}
