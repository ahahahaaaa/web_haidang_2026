<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;

class OptimizationAccess
{
    public const PAGE_PERMISSIONS = [
        'home' => 'admin.landing-pages', 'about' => 'admin.landing-pages',
        'contact' => 'admin.landing-pages', 'tour_scope' => 'admin.landing-pages',
        'landing' => 'admin.landing-pages', 'service_index' => 'admin.landing-pages',
        'blog_index' => 'admin.landing-pages', 'tour' => 'admin.tours',
        'tour_category' => 'admin.tours.categories', 'destination' => 'admin.tours.destinations',
        'country' => 'admin.tours.destinations', 'region' => 'admin.tours.regions',
        'service' => 'admin.services', 'service_category' => 'admin.services.categories',
        'blog_post' => 'admin.blogs', 'blog_category' => 'admin.blogs.categories',
    ];

    public function authorize(User $user, string $action, ?SeoOptimizationPage $page = null): void
    {
        $action = $action === 'read' ? 'index' : $action;
        $action = $action === 'automate' ? 'propose' : $action;
        abort_unless(config('seo_optimization.enabled'), 503, 'SEO AI Optimize đang tạm tắt.');
        abort_unless($user->is_active === true, 403, 'Tài khoản chưa được phép truy cập.');
        abort_if($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(), 403, 'Tài khoản cần xác minh email trước khi truy cập.');
        abort_unless($user->can('admin.seo-optimization.'.$action), 403, 'Bạn không có quyền thực hiện thao tác SEO này.');

        if ($page) {
            abort_unless($page->site_id === config('seo_optimization.site_id'), 403, 'Trang ngoài phạm vi website.');
            $permission = self::PAGE_PERMISSIONS[$page->page_type] ?? null;
            $suffix = in_array($action, ['approve', 'apply', 'rollback', 'propose']) ? '.edit' : '.index';
            abort_unless($permission && $user->can($permission.$suffix), 403, 'Bạn không có quyền với loại nội dung này.');
        }
    }

    public function queryFor(User $user, string $action = 'index'): Builder
    {
        $this->authorize($user, $action);
        $suffix = in_array($action, ['approve', 'apply', 'rollback', 'propose'], true) ? '.edit' : '.index';
        $types = array_keys(array_filter(self::PAGE_PERMISSIONS, fn ($permission) => $user->can($permission.$suffix)));

        return SeoOptimizationPage::query()->where('site_id', config('seo_optimization.site_id'))->whereIn('page_type', $types);
    }
}
