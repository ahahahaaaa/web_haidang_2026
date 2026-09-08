<?php

namespace App\Services\Cms;

use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TravelInquiry;

class DashboardStatsService
{
    /**
     * @var array<string, bool>
     */
    protected array $tableExists = [];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cards(): array
    {
        return [
            [
                'route' => 'admin.tours',
                'title' => 'Tours',
                'text' => 'Tour trong nước, tour nước ngoài và tour đoàn',
                'count' => $this->count('tours', fn (): int => Tour::query()->count()),
                'count_label' => 'tour',
                'secondary' => $this->secondaryCount('tours', fn (): int => Tour::query()->published()->count(), 'đang publish'),
            ],
            [
                'route' => 'admin.services',
                'title' => 'Services',
                'text' => 'Visa, vé máy bay, sim du lịch và du học',
                'count' => $this->count('services', fn (): int => Service::query()->count()),
                'count_label' => 'dịch vụ',
                'secondary' => $this->secondaryCount('services', fn (): int => Service::query()->published()->count(), 'đang publish'),
            ],
            [
                'route' => 'admin.blogs',
                'title' => 'Blogs',
                'text' => 'Cẩm nang du lịch và nội dung visa, điểm đến',
                'count' => $this->count('blog_posts', fn (): int => BlogPost::query()->count()),
                'count_label' => 'bài viết',
                'secondary' => $this->secondaryCount('blog_posts', fn (): int => BlogPost::query()->published()->count(), 'đang publish'),
            ],
            [
                'route' => 'admin.travel-inquiries',
                'title' => 'Inquiries',
                'text' => 'Lead từ tour, dịch vụ và liên hệ chung',
                'count' => $this->count('travel_inquiries', fn (): int => TravelInquiry::query()->count()),
                'count_label' => 'lead',
                'secondary' => $this->secondaryCount('travel_inquiries', fn (): int => TravelInquiry::query()->where('status', 'new')->count(), 'mới'),
            ],
            [
                'route' => 'admin.landing-pages',
                'title' => 'Landing Pages',
                'text' => 'Home, about, contact, blog và các landing tour',
                'count' => $this->count('landing_pages', fn (): int => LandingPage::query()->count()),
                'count_label' => 'trang',
                'secondary' => $this->secondaryCount('landing_pages', fn (): int => LandingPage::query()->where('is_active', true)->count(), 'đang active'),
            ],
            [
                'route' => 'admin.media',
                'title' => 'Media',
                'text' => 'Thư viện ảnh và tài nguyên nội dung',
                'count' => $this->count('media', fn (): int => Media::query()->where('mime_type', 'like', 'image/%')->count()),
                'count_label' => 'ảnh',
                'secondary' => null,
            ],
            [
                'route' => 'admin.menus',
                'title' => 'Menus',
                'text' => 'Menu header và footer',
                'count' => $this->count('menus', fn (): int => Menu::query()->count()),
                'count_label' => 'menu',
                'secondary' => $this->secondaryCount('menu_items', fn (): int => MenuItem::query()->count(), 'liên kết'),
            ],
            [
                'route' => 'admin.theme-settings',
                'title' => 'Theme Settings',
                'text' => 'Thông tin thương hiệu, contact và SEO mặc định',
                'count' => $this->count('site_settings', fn (): int => SiteSetting::query()->count()),
                'count_label' => 'cấu hình',
                'secondary' => $this->activeThemeLabel(),
            ],
        ];
    }

    protected function activeThemeLabel(): ?string
    {
        $activeTheme = trim((string) $this->value('site_settings', fn (): ?string => SiteSetting::query()->value('active_theme')));

        return $activeTheme !== '' ? 'theme '.$activeTheme : null;
    }

    protected function count(string $table, callable $resolver): int
    {
        if (! $this->hasTable($table)) {
            return 0;
        }

        return (int) $resolver();
    }

    protected function hasTable(string $table): bool
    {
        if (! array_key_exists($table, $this->tableExists)) {
            $this->tableExists[$table] = Schema::hasTable($table);
        }

        return $this->tableExists[$table];
    }

    protected function secondaryCount(string $table, callable $resolver, string $suffix): string
    {
        return $this->count($table, $resolver).' '.$suffix;
    }

    protected function value(string $table, callable $resolver): mixed
    {
        if (! $this->hasTable($table)) {
            return null;
        }

        return $resolver();
    }
}
