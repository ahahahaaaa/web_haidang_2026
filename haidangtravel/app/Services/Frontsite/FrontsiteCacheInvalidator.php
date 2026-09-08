<?php

namespace App\Services\Frontsite;

use Illuminate\Database\Eloquent\Model;
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

class FrontsiteCacheInvalidator
{
    public function __construct(protected FrontsiteCache $cache)
    {
    }

    public function modelChanged(Model $model): void
    {
        $groups = $this->groupsFor($model);

        if ($groups === []) {
            return;
        }

        $this->cache->forgetGroups($groups);
    }

    public function mediaChanged(Media $media): void
    {
        $owner = rescue(fn () => $media->model, report: false);

        if ($owner instanceof Model) {
            $this->modelChanged($owner);
        }
    }

    public function groupsFor(Model $model): array
    {
        if ($model instanceof SiteSetting) {
            return ['chrome', 'settings'];
        }

        if ($model instanceof Menu || $model instanceof MenuItem) {
            return ['chrome', 'menus'];
        }

        if ($model instanceof Slider || $model instanceof SliderItem) {
            return ['sliders', 'home', 'landing'];
        }

        if ($model instanceof LandingPage) {
            return array_filter([
                'landing',
                'landing:'.$model->getKey(),
                filled($model->page_key) ? 'landing-page-key:'.$model->page_key : null,
                $model->page_key === 'home' ? 'home' : null,
                'sitemap',
            ]);
        }

        if ($model instanceof Tour) {
            $scope = $model->scope instanceof \BackedEnum ? $model->scope->value : $model->scope;

            return array_filter([
                'home',
                'sitemap',
                'taxonomies',
                'tours',
                'tour:'.$model->getKey(),
                filled($scope) ? 'tour-scope:'.$scope : null,
                filled($model->tour_category_id) ? 'tour-category:'.$model->tour_category_id : null,
                filled($model->destination_id) ? 'destination:'.$model->destination_id : null,
                filled($model->region_id) ? 'region:'.$model->region_id : null,
            ]);
        }

        if ($model instanceof TourDeparture) {
            return array_filter([
                'home',
                'sitemap',
                'tours',
                filled($model->tour_id) ? 'tour:'.$model->tour_id : null,
            ]);
        }

        if ($model instanceof TourCategory) {
            return [
                'home',
                'sitemap',
                'taxonomies',
                'tour-categories',
                'tour-category:'.$model->getKey(),
                'tours',
            ];
        }

        if ($model instanceof Destination) {
            return [
                'home',
                'sitemap',
                'taxonomies',
                'destinations',
                'destination:'.$model->getKey(),
                'tours',
            ];
        }

        if ($model instanceof Region) {
            return [
                'home',
                'sitemap',
                'taxonomies',
                'regions',
                'region:'.$model->getKey(),
                'tours',
            ];
        }

        if ($model instanceof Service) {
            return [
                'home',
                'sitemap',
                'services',
                'service:'.$model->getKey(),
                filled($model->content_category_id) ? 'service-category:'.$model->content_category_id : null,
            ];
        }

        if ($model instanceof BlogPost) {
            return [
                'home',
                'sitemap',
                'blog',
                'blog-post:'.$model->getKey(),
                filled($model->content_category_id) ? 'blog-category:'.$model->content_category_id : null,
            ];
        }

        if ($model instanceof ContentCategory) {
            return match ($model->taxonomy) {
                'service' => ['home', 'sitemap', 'services', 'service-categories', 'service-category:'.$model->getKey()],
                'blog' => ['home', 'sitemap', 'blog', 'blog-categories', 'blog-category:'.$model->getKey()],
                default => ['taxonomies'],
            };
        }

        if ($model instanceof TravelReview) {
            return array_filter([
                'home',
                'sitemap',
                'taxonomies',
                filled($model->reviewable_type) ? 'reviews:'.$model->reviewable_type : null,
                filled($model->reviewable_id) ? 'reviews:'.$model->reviewable_type.':'.$model->reviewable_id : null,
            ]);
        }

        return [];
    }
}
