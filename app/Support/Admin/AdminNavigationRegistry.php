<?php

namespace App\Support\Admin;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminNavigationRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function cmsGroups(?Authenticatable $user = null): array
    {
        return collect(self::groups())
            ->map(function (array $group) use ($user): array {
                $group['actions'] = self::visibleActions($group['key'], $user);

                return $group;
            })
            ->filter(fn (array $group) => $group['actions'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'tours',
                'label' => 'Quản lý tour',
                'icon' => 'map',
                'actions' => [
                    self::action(
                        'admin.tours.index',
                        'Danh sách tour',
                        'Xem tour, lọc nhanh theo phạm vi và trạng thái.',
                        'admin.tours',
                        ['admin.tours', 'admin.tours.reviews.index', 'admin.tours.reviews.create', 'admin.tours.reviews.edit'],
                    ),
                    self::action(
                        'admin.tours.edit',
                        'Tạo mới tour',
                        'Tạo mới hoặc cập nhật tour và lịch khởi hành.',
                        'admin.tours.create',
                        ['admin.tours.create', 'admin.tours.edit'],
                    ),
                    self::action(
                        'admin.tours.edit',
                        'Hàng chờ API Master Data DashBoard',
                        'Theo dõi và chạy ngay các job gửi tour CMS sang API Master Data DashBoard.',
                        'admin.tours.agency-sync-queue',
                        ['admin.tours.agency-sync-queue'],
                    ),
                    self::action(
                        'admin.tours.categories.index',
                        'Chủ đề tour',
                        'Quản lý danh mục tour theo intent kinh doanh.',
                        'admin.tours.categories',
                        ['admin.tours.categories', 'admin.tours.categories.reviews.index', 'admin.tours.categories.reviews.create', 'admin.tours.categories.reviews.edit'],
                    ),
                    self::action(
                        'admin.tours.categories.edit',
                        'Tạo mới chủ đề',
                        'Biên tập landing chủ đề tour.',
                        'admin.tours.categories.create',
                        ['admin.tours.categories.create', 'admin.tours.categories.edit'],
                    ),
                    self::action(
                        'admin.tours.destinations.index',
                        'Điểm đến',
                        'Quản lý điểm đến gắn với tour và bộ lọc frontsite.',
                        'admin.tours.destinations',
                        ['admin.tours.destinations', 'admin.tours.destinations.reviews.index', 'admin.tours.destinations.reviews.create', 'admin.tours.destinations.reviews.edit'],
                    ),
                    self::action(
                        'admin.tours.destinations.edit',
                        'Tạo mới điểm đến',
                        'Biên tập landing điểm đến.',
                        'admin.tours.destinations.create',
                        ['admin.tours.destinations.create', 'admin.tours.destinations.edit'],
                    ),
                    self::action(
                        'admin.tours.regions.index',
                        'Vùng miền',
                        'Quản lý vùng miền và châu lục dùng chung cho taxonomy điểm đến.',
                        'admin.tours.regions',
                        ['admin.tours.regions'],
                    ),
                    self::action(
                        'admin.tours.regions.edit',
                        'Tạo mới vùng miền',
                        'Biên tập landing vùng miền.',
                        'admin.tours.regions.create',
                        ['admin.tours.regions.create', 'admin.tours.regions.edit'],
                    ),
                ],
            ],
            [
                'key' => 'services',
                'label' => 'Quản lý dịch vụ',
                'icon' => 'briefcase',
                'actions' => [
                    self::action(
                        'admin.services.index',
                        'Danh sách dịch vụ',
                        'Xem và lọc toàn bộ dịch vụ travel hiện có.',
                        'admin.services',
                        ['admin.services'],
                    ),
                    self::action(
                        'admin.services.edit',
                        'Tạo mới dịch vụ',
                        'Tạo mới hoặc cập nhật trang chi tiết dịch vụ.',
                        'admin.services.create',
                        ['admin.services.create', 'admin.services.edit'],
                    ),
                    self::action(
                        'admin.services.categories.index',
                        'Danh mục dịch vụ',
                        'Quản lý nhóm dịch vụ hiển thị ngoài frontsite.',
                        'admin.services.categories',
                        ['admin.services.categories'],
                    ),
                    self::action(
                        'admin.services.categories.edit',
                        'Tạo mới danh mục',
                        'Biên tập danh mục dịch vụ.',
                        'admin.services.categories.create',
                        ['admin.services.categories.create', 'admin.services.categories.edit'],
                    ),
                ],
            ],
            [
                'key' => 'blogs',
                'label' => 'Quản lý blog',
                'icon' => 'newspaper',
                'actions' => [
                    self::action(
                        'admin.blogs.index',
                        'Danh sách bài viết',
                        'Xem, lọc và truy cập nhanh từng bài viết.',
                        'admin.blogs',
                        ['admin.blogs'],
                    ),
                    self::action(
                        'admin.blogs.edit',
                        'Tạo mới bài viết',
                        'Tạo mới hoặc cập nhật chi tiết bài viết.',
                        'admin.blogs.create',
                        ['admin.blogs.create', 'admin.blogs.edit'],
                    ),
                    self::action(
                        'admin.blogs.categories.index',
                        'Danh mục blog',
                        'Quản lý cấu trúc danh mục cho blog.',
                        'admin.blogs.categories',
                        ['admin.blogs.categories'],
                    ),
                    self::action(
                        'admin.blogs.categories.edit',
                        'Tạo mới danh mục',
                        'Tạo mới hoặc cập nhật danh mục blog.',
                        'admin.blogs.categories.create',
                        ['admin.blogs.categories.create', 'admin.blogs.categories.edit'],
                    ),
                ],
            ],
            [
                'key' => 'landing-pages',
                'label' => 'Landing Pages',
                'icon' => 'rectangle-stack',
                'actions' => [
                    self::action(
                        'admin.landing-pages.index',
                        'Danh sách landing page',
                        'Xem các page hệ thống và page custom đang hoạt động.',
                        'admin.landing-pages',
                        ['admin.landing-pages'],
                    ),
                    self::action(
                        'admin.landing-pages.edit',
                        'Tạo landing page',
                        'Tạo mới hoặc biên tập landing page dạng block.',
                        'admin.landing-pages.create',
                        ['admin.landing-pages.create', 'admin.landing-pages.edit'],
                    ),
                    self::action(
                        'admin.voucher-campaigns.index',
                        'Voucher campaigns',
                        'Xem campaign voucher gắn với landing page promotion.',
                        'admin.voucher-campaigns',
                        ['admin.voucher-campaigns'],
                    ),
                    self::action(
                        'admin.voucher-campaigns.index',
                        'Mã voucher đã cấp phát',
                        'Tra cứu mã voucher đã cấp phát trên toàn bộ campaigns.',
                        'admin.voucher-codes',
                        ['admin.voucher-codes', 'admin.voucher-codes.export', 'admin.voucher-campaigns.codes', 'admin.voucher-campaigns.codes.export'],
                    ),
                    self::action(
                        'admin.voucher-campaigns.edit',
                        'Tạo voucher campaign',
                        'Tạo mới, đổi bộ mã và cấu hình thời gian áp dụng voucher.',
                        'admin.voucher-campaigns.create',
                        ['admin.voucher-campaigns.create', 'admin.voucher-campaigns.edit'],
                    ),
                ],
            ],
            [
                'key' => 'seo-optimization',
                'label' => 'SEO AI Optimize',
                'icon' => 'rectangle-stack',
                'actions' => [
                    self::action('admin.seo-optimization.index', 'Tối ưu URL', 'Brief từ khóa, kiểm tra và đề xuất cho URL CMS.', 'admin.seo-optimization.index', ['admin.seo-optimization.index', 'admin.seo-optimization.pages.show', 'admin.seo-optimization.proposals.show']),
                    self::action('admin.seo-optimization.index', 'Hàng chờ Codex', 'Theo dõi yêu cầu tạo nội dung chờ duyệt.', 'admin.seo-optimization.tasks.index', ['admin.seo-optimization.tasks.index']),
                    self::action('admin.seo-optimization.index', 'Nội dung Codex tạo', 'Theo dõi bài CMS mới, media và taxonomy chờ xác nhận.', 'admin.seo-optimization.content-creation.index', ['admin.seo-optimization.content-creation.index']),
                    self::action('admin.seo-optimization.settings', 'Kết nối và lịch chạy', 'Cấu hình MCP, lịch Codex và policy preview/publish.', 'admin.seo-optimization.settings', ['admin.seo-optimization.settings']),
                    self::action('admin.seo-optimization.audit', 'Kiểm tra SEO', 'Chạy kiểm tra SEO trong phạm vi nội dung được cấp.', 'admin.seo-optimization.index', [], false),
                    self::action('admin.seo-optimization.propose', 'Quản lý brief và đề xuất', 'Lưu brief, gửi yêu cầu tạo đề xuất nội dung.', 'admin.seo-optimization.index', [], false),
                    self::action('admin.seo-optimization.approve', 'Duyệt đề xuất SEO', 'Duyệt hoặc từ chối đề xuất; chưa thay đổi public.', 'admin.seo-optimization.index', [], false),
                    self::action('admin.seo-optimization.apply', 'Áp dụng đề xuất SEO', 'Áp dụng nội dung đã được người có quyền duyệt.', 'admin.seo-optimization.index', [], false),
                    self::action('admin.seo-optimization.rollback', 'Đề xuất hoàn tác SEO', 'Tạo đề xuất khôi phục có kiểm tra phiên bản.', 'admin.seo-optimization.index', [], false),
                ],
            ],
            [
                'key' => 'sliders',
                'label' => 'Sliders',
                'icon' => 'photo',
                'actions' => [
                    self::action(
                        'admin.sliders.index',
                        'Danh sách slider',
                        'Xem các slider active theo banner-location.',
                        'admin.sliders',
                        ['admin.sliders'],
                    ),
                    self::action(
                        'admin.sliders.edit',
                        'Tạo mới slider',
                        'Biên tập slider, item slide và CTA hai nút.',
                        'admin.sliders.create',
                        ['admin.sliders.create', 'admin.sliders.edit'],
                    ),
                ],
            ],
            [
                'key' => 'travel-inquiries',
                'label' => 'Travel Inquiries',
                'icon' => 'chat-bubble-left-right',
                'actions' => [
                    self::action(
                        'admin.travel-inquiries.index',
                        'Danh sách yêu cầu',
                        'Theo dõi yêu cầu tư vấn gửi từ frontsite.',
                        'admin.travel-inquiries',
                        ['admin.travel-inquiries'],
                    ),
                ],
            ],
            [
                'key' => 'media',
                'label' => 'Media',
                'icon' => 'photo',
                'actions' => [
                    self::action(
                        'admin.media.index',
                        'Thư viện media',
                        'Quản lý ảnh dùng chung cho slider, tour, blog và landing page.',
                        'admin.media',
                        ['admin.media', 'admin.media.browser.images'],
                    ),
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'Menus & Theme',
                'icon' => 'cog-6-tooth',
                'actions' => [
                    self::action(
                        'admin.menus.index',
                        'Menu điều hướng',
                        'Cấu hình menu header và 2 cột footer cho theme travel.',
                        'admin.menus',
                        ['admin.menus'],
                    ),
                    self::action(
                        'admin.theme-settings.index',
                        'Theme settings',
                        'Quản lý thông tin site và media nền tảng.',
                        'admin.theme-settings',
                        ['admin.theme-settings'],
                    ),
                ],
            ],
            [
                'key' => 'accounts',
                'label' => 'Tài khoản',
                'icon' => 'users',
                'actions' => [
                    self::action(
                        'admin.accounts.index',
                        'Danh sách tài khoản',
                        'Xem các tài khoản CMS và loại vai trò đang dùng.',
                        'admin.accounts',
                        ['admin.accounts'],
                    ),
                    self::action(
                        'admin.accounts.edit',
                        'Tạo mới tài khoản',
                        'Tạo tài khoản Admin hoặc Content và cấp quyền bổ sung.',
                        'admin.accounts.create',
                        ['admin.accounts.create', 'admin.accounts.edit'],
                    ),
                ],
            ],
        ];
    }

    public static function group(string $groupKey): ?array
    {
        return collect(self::groups())->firstWhere('key', $groupKey);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function visibleActions(string $groupKey, ?Authenticatable $user = null): array
    {
        if ($groupKey === 'accounts' && ! self::userHasAccountManagerRole($user)) {
            return [];
        }

        $group = self::group($groupKey);

        if (! $group) {
            return [];
        }

        return collect($group['actions'])
            ->filter(fn (array $action) => $action['navigation'] ?? true)
            ->filter(fn (array $action) => Route::has($action['route']))
            ->filter(fn (array $action) => self::userCanAccess($user, $action['permission']))
            ->values()
            ->all();
    }

    public static function actionIsCurrent(array $action, ?string $routeName): bool
    {
        if (! filled($routeName)) {
            return false;
        }

        return collect($action['active_patterns'] ?? [])
            ->contains(fn (string $pattern) => Str::is($pattern, $routeName));
    }

    public static function groupIsCurrent(string $groupKey, ?string $routeName): bool
    {
        $group = self::group($groupKey);

        if (! $group) {
            return false;
        }

        return collect($group['actions'])
            ->contains(fn (array $action) => self::actionIsCurrent($action, $routeName));
    }

    /**
     * @return array<int, string>
     */
    public static function permissionKeys(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => collect($group['actions'])->pluck('permission'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function grantableContentGroups(): array
    {
        return collect(self::groups())
            ->reject(fn (array $group) => $group['key'] === 'accounts')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function grantableContentPermissionKeys(): array
    {
        return collect(self::grantableContentGroups())
            ->flatMap(fn (array $group) => collect($group['actions'])->pluck('permission'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function legacyPermissionKeys(): array
    {
        return [
            'manage blogs',
            'manage tours',
            'manage services',
            'manage travel inquiries',
            'manage landing pages',
            'manage sliders',
            'manage menus',
            'manage theme settings',
            'manage media',
            'manage seo',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function fullAdminPermissions(): array
    {
        return array_values(array_unique([
            'access admin panel',
            ...self::permissionKeys(),
        ]));
    }

    /**
     * @return array<int, string>
     */
    public static function defaultContentPermissions(): array
    {
        return [
            'access admin panel',
            'admin.blogs.index',
            'admin.blogs.edit',
            'admin.blogs.categories.index',
            'admin.blogs.categories.edit',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function defaultSalePermissions(): array
    {
        return [
            'access admin panel',
            'admin.tours.index',
            'admin.tours.edit',
        ];
    }

    public static function permissionForRoute(?string $routeName): ?string
    {
        if (! filled($routeName)) {
            return null;
        }

        foreach (self::groups() as $group) {
            foreach ($group['actions'] as $action) {
                if (in_array($routeName, Arr::wrap($action['route_names'] ?? []), true)) {
                    return $action['permission'];
                }
            }
        }

        return null;
    }

    protected static function action(
        string $permission,
        string $label,
        string $description,
        string $route,
        array $routeNames,
        bool $navigation = true,
    ): array {
        return [
            'permission' => $permission,
            'label' => $label,
            'description' => $description,
            'route' => $route,
            'route_names' => $routeNames,
            'active_patterns' => $routeNames,
            'navigation' => $navigation,
        ];
    }

    protected static function userCanAccess(?Authenticatable $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        return method_exists($user, 'can')
            ? (bool) $user->can($permission)
            : false;
    }

    protected static function userHasAccountManagerRole(?Authenticatable $user): bool
    {
        return $user
            && method_exists($user, 'hasAnyRole')
            && (bool) $user->hasAnyRole(['admin', 'super_admin']);
    }
}
