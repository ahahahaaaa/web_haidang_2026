<?php

namespace App\Providers;

use App\Services\Frontsite\FrontsiteCache;
use App\Services\Frontsite\FrontsiteCacheInvalidator;
use App\Services\Cms\SiteSettingsManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TravelReview;

class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettingsManager::class);
    }

    public function boot(): void
    {
        $this->registerFrontsiteCacheInvalidation();

        View::composer('themes.*', function ($view): void {
            $site = $this->app->make(SiteSettingsManager::class);
            $frontsiteCache = $this->app->make(FrontsiteCache::class);
            $activeTheme = $site->activeTheme();
            $settings = $site->current();
            $headerMenuDefinition = $this->frontsiteMenuDefinition('header', $frontsiteCache);
            $footerMenuDefinition = $this->frontsiteMenuDefinition('footer', $frontsiteCache);
            $footerSecondaryMenuDefinition = $this->frontsiteMenuDefinition('footer_secondary', $frontsiteCache);
            $sharedHeaderTaxonomies = $this->frontsiteHeaderTaxonomies($frontsiteCache);

            if ($settings->mail_from_address) {
                config([
                    'mail.from.address' => $settings->mail_from_address,
                    'mail.from.name' => $settings->mail_from_name ?: $settings->site_name,
                ]);
            }

            $footerMenuGroups = collect([
                [
                    'items' => $footerMenuDefinition?->items ?? collect(),
                    'location' => 'footer',
                    'title' => filled($footerMenuDefinition?->description) ? $footerMenuDefinition->description : 'Đi nhanh',
                ],
                [
                    'items' => $footerSecondaryMenuDefinition?->items ?? collect(),
                    'location' => 'footer_secondary',
                    'title' => filled($footerSecondaryMenuDefinition?->description) ? $footerSecondaryMenuDefinition->description : 'Thông tin',
                ],
            ])->filter(fn (array $group) => $group['items']->isNotEmpty())->values();

            $view->with([
                'activeTheme' => $activeTheme,
                'footerMenu' => $footerMenuDefinition?->items ?? collect(),
                'footerMenuGroups' => $footerMenuGroups,
                'frontsiteHeaderTaxonomies' => $sharedHeaderTaxonomies,
                'headerMenu' => $headerMenuDefinition?->items ?? collect(),
                'siteSettings' => $settings,
                'themeViewBase' => 'themes.'.$activeTheme,
            ]);
        });
    }

    protected function frontsiteHeaderTaxonomies(FrontsiteCache $cache): array
    {
        $data = $cache->remember(
            'chrome:header-taxonomies',
            ['chrome', 'taxonomies'],
            function (): array {
                $taxonomies = [
                    'blogCategories' => [],
                    'serviceCategories' => [],
                    'tourDestinations' => [],
                    'tourRegions' => [],
                ];

                if (Schema::hasTable('content_categories')) {
                    $taxonomies['blogCategories'] = ContentCategory::query()
                        ->forTaxonomy('blog')
                        ->orderBy('sort_order')
                        ->get(['name', 'slug'])
                        ->map(fn (ContentCategory $category): array => $category->only(['name', 'slug']))
                        ->all();
                    $taxonomies['serviceCategories'] = ContentCategory::query()
                        ->forTaxonomy('service')
                        ->whereHas('services', fn ($query) => $query->published())
                        ->orderBy('sort_order')
                        ->get(['name', 'slug'])
                        ->map(fn (ContentCategory $category): array => $category->only(['name', 'slug']))
                        ->all();
                }

                if (Schema::hasTable('destinations')) {
                    $taxonomies['tourDestinations'] = Destination::query()
                        ->published()
                        ->orderBy('sort_order')
                        ->get(['name', 'slug'])
                        ->map(fn (Destination $destination): array => $destination->only(['name', 'slug']))
                        ->all();
                } elseif (Schema::hasTable('content_categories')) {
                    $taxonomies['tourDestinations'] = ContentCategory::query()
                        ->forTaxonomy('tour_destination')
                        ->orderBy('sort_order')
                        ->get(['name', 'slug'])
                        ->map(fn (ContentCategory $category): array => $category->only(['name', 'slug']))
                        ->all();
                }

                if (Schema::hasTable('regions')) {
                    $taxonomies['tourRegions'] = Region::query()
                        ->published()
                        ->orderBy('sort_order')
                        ->get(['name', 'slug'])
                        ->map(fn (Region $region): array => $region->only(['name', 'slug']))
                        ->all();
                } elseif (Schema::hasTable('content_categories')) {
                    $taxonomies['tourRegions'] = ContentCategory::query()
                        ->forTaxonomy('tour_region')
                        ->orderBy('sort_order')
                        ->get(['name', 'slug'])
                        ->map(fn (ContentCategory $category): array => $category->only(['name', 'slug']))
                        ->all();
                }

                return $taxonomies;
            },
            $cache->ttl('chrome'),
        );

        return collect($data)
            ->map(fn (array $items) => collect($items))
            ->all();
    }

    protected function frontsiteMenuDefinition(string $location, FrontsiteCache $cache): ?Menu
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_items')) {
            return null;
        }

        $data = $cache->remember(
            'chrome:menu:'.$location,
            ['chrome', 'menus'],
            function () use ($location): ?array {
                $menu = Menu::query()
                    ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('order')])
                    ->where('location', $location)
                    ->first();

                if (! $menu) {
                    return null;
                }

                return [
                    'attributes' => $menu->getAttributes(),
                    'items' => $menu->items
                        ->map(fn (MenuItem $item): array => $item->getAttributes())
                        ->all(),
                ];
            },
            $cache->ttl('chrome'),
        );

        if (! is_array($data)) {
            return null;
        }

        $menu = (new Menu)->newFromBuilder($data['attributes'] ?? []);
        $items = collect($data['items'] ?? [])
            ->map(fn (array $attributes): MenuItem => (new MenuItem)->newFromBuilder($attributes));

        $menu->setRelation('items', new EloquentCollection($items->all()));

        return $menu;
    }

    protected function registerFrontsiteCacheInvalidation(): void
    {
        $modelClasses = [
            BlogPost::class,
            ContentCategory::class,
            Destination::class,
            LandingPage::class,
            Menu::class,
            MenuItem::class,
            Region::class,
            Service::class,
            SiteSetting::class,
            Slider::class,
            SliderItem::class,
            Tour::class,
            TourCategory::class,
            TourDeparture::class,
            TravelReview::class,
        ];

        foreach ($modelClasses as $modelClass) {
            $modelClass::saved(fn (Model $model) => $this->app->make(FrontsiteCacheInvalidator::class)->modelChanged($model));
            $modelClass::deleted(fn (Model $model) => $this->app->make(FrontsiteCacheInvalidator::class)->modelChanged($model));
        }

        Media::saved(fn (Media $media) => $this->app->make(FrontsiteCacheInvalidator::class)->mediaChanged($media));
        Media::deleted(fn (Media $media) => $this->app->make(FrontsiteCacheInvalidator::class)->mediaChanged($media));
    }
}
