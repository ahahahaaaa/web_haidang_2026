<?php

namespace App\Modules\LegacyMigration\Services;

use App\Support\FrontsiteUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class LegacyTargetRegistry
{
    private const SYSTEM_ROUTES = [
        'home' => 'Trang chủ',
        'about' => 'Về chúng tôi',
        'contact' => 'Liên hệ',
        'tours.domestic' => 'Danh sách tour trong nước',
        'tours.international' => 'Danh sách tour nước ngoài',
        'tours.group' => 'Danh sách tour đoàn',
        'services.index' => 'Danh sách dịch vụ',
        'blog.index' => 'Danh sách blog',
    ];

    /** @return array<string, string> */
    public function typeLabels(): array
    {
        return [
            'tour' => 'Tour',
            'tour_category' => 'Chủ đề tour',
            'destination' => 'Điểm đến / quốc gia',
            'region' => 'Vùng miền',
            'blog_post' => 'Bài viết',
            'blog_category' => 'Danh mục blog',
            'landing_page' => 'Landing page',
            'service' => 'Dịch vụ',
            'system_route' => 'Trang hệ thống',
        ];
    }

    /** @return array<string, string> */
    public function systemRoutes(): array
    {
        return self::SYSTEM_ROUTES;
    }

    /** @return array<int, array{id: string, label: string, path: string}> */
    public function options(string $type, string $search = ''): array
    {
        if ($type === 'system_route') {
            return collect(self::SYSTEM_ROUTES)
                ->filter(fn (string $label, string $route): bool => $search === '' || str_contains(mb_strtolower($label.' '.$route), mb_strtolower($search)))
                ->map(fn (string $label, string $route): array => [
                    'id' => $route,
                    'label' => $label,
                    'path' => route($route, [], false),
                ])->values()->all();
        }

        $class = $this->modelClass($type);
        $query = $class::query();
        $this->constrain($query, $type);
        $nameColumn = in_array($type, ['tour_category', 'destination', 'region', 'blog_category'], true) ? 'name' : 'title';

        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search, $nameColumn): void {
                $nested->where($nameColumn, 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        return $query->orderBy($nameColumn)->limit(100)->get(['id', $nameColumn, 'slug'])
            ->map(fn (Model $model): array => [
                'id' => (string) $model->getKey(),
                'label' => (string) $model->getAttribute($nameColumn),
                'path' => $this->path($type, $model),
            ])->all();
    }

    public function resolve(string $type, string|int $id): Model
    {
        if ($type === 'system_route') {
            throw new InvalidArgumentException('Trang hệ thống không phải model để cast.');
        }

        $class = $this->modelClass($type);
        $query = $class::query();
        $this->constrain($query, $type);

        return $query->findOrFail($id);
    }

    public function resolveBySlug(string $type, string $slug): ?Model
    {
        if ($type === 'system_route') {
            return null;
        }

        $class = $this->modelClass($type);
        $query = $class::query();
        $this->constrain($query, $type);

        return $query->where('slug', $slug)->first();
    }

    public function path(string $type, Model|string $target): string
    {
        if ($type === 'system_route') {
            $routeName = (string) $target;

            if (! array_key_exists($routeName, self::SYSTEM_ROUTES)) {
                throw new InvalidArgumentException('Route hệ thống không được hỗ trợ.');
            }

            return route($routeName, [], false);
        }

        if (! $target instanceof Model) {
            throw new InvalidArgumentException('Target model không hợp lệ.');
        }

        return match ($type) {
            'tour' => route('tours.show', ['tour' => $target], false),
            'tour_category' => route('tour-categories.show', ['category' => $target], false),
            'destination' => $target->is_country_root
                ? route('countries.show', ['slug' => $target->slug], false)
                : route('destinations.show', ['destination' => $target], false),
            'region' => route('regions.show', ['region' => $target], false),
            'blog_post' => route('blog.show', FrontsiteUrls::blogPostRouteParameters($target), false),
            'blog_category' => route('blog-categories.show', ['slug' => $target->slug], false),
            'landing_page' => route('landing.show', ['slug' => $target->slug], false),
            'service' => route('services.show', ['service' => $target], false),
            default => throw new InvalidArgumentException('Loại target không được hỗ trợ.'),
        };
    }

    public function isCompatible(string $sourceType, string $targetType): bool
    {
        if ($targetType === 'system_route') {
            return in_array($sourceType, ['homepage', 'tour_listing', 'blog_listing', 'static_page', 'config'], true);
        }

        return in_array($sourceType, match ($targetType) {
            'tour' => ['tour'],
            'tour_category' => ['subject_tour'],
            'destination' => ['destination'],
            'region' => ['region'],
            'blog_post' => ['blog'],
            'blog_category' => ['subject_blog'],
            'landing_page', 'service' => ['page', 'config'],
            default => [],
        }, true);
    }

    /** @return class-string<Model> */
    public function modelClass(string $type): string
    {
        return match ($type) {
            'tour' => Tour::class,
            'tour_category' => TourCategory::class,
            'destination' => Destination::class,
            'region' => Region::class,
            'blog_post' => BlogPost::class,
            'blog_category' => ContentCategory::class,
            'landing_page' => LandingPage::class,
            'service' => Service::class,
            default => throw new InvalidArgumentException('Loại target không được hỗ trợ.'),
        };
    }

    private function constrain(Builder $query, string $type): void
    {
        if ($type === 'blog_category') {
            $query->where('taxonomy', 'blog');
        }

        if ($type === 'landing_page') {
            $query->whereNull('page_key')->whereNotNull('slug')->where('slug', '!=', '');
        }
    }
}
