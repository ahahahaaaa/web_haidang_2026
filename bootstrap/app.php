<?php

use App\Http\Middleware\AddNoIndexHeaders;
use App\Http\Middleware\AuthenticateAgencyExportToken;
use App\Http\Middleware\CacheFrontsiteResponse;
use App\Http\Middleware\CanonicalizeFrontsiteUrl;
use App\Services\Cms\SiteSettingsManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/frontsite.php',
            __DIR__.'/../routes/admin.php',
            __DIR__.'/../routes/seo_optimization_admin.php',
            __DIR__.'/../routes/admin_api.php',
            __DIR__.'/../routes/admin_seo.php',
            __DIR__.'/../routes/ai.php',
            __DIR__.'/../routes/api_v1/seo.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CanonicalizeFrontsiteUrl::class);

        $middleware->alias([
            'agency.export.token' => AuthenticateAgencyExportToken::class,
            'frontsite.cache' => CacheFrontsiteResponse::class,
            'noindex.headers' => AddNoIndexHeaders::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson() || ! $request->acceptsHtml() || $request->is('admin*', 'api*', 'livewire*')) {
                return null;
            }

            $path = $request->path();

            $legacyRedirects = [
                'tin-tuc/tour-du-lich-an-do-tron-goi' => '/tour-du-lich-an-do-nepal',
            ];

            if (($destination = $legacyRedirects[$path] ?? null) !== null) {
                return redirect()->to($destination, 301);
            }

            // Lấy slug từ segment cuối cùng của URL
            $segments = array_filter(explode('/', $path));
            $slug = end($segments);

            // Kiểm tra slug có hợp lệ để tìm kiếm hay không
            if (!empty($slug)
                && $slug !== 'tim-tour'
                && !preg_match('/\.(?:png|jpe?g|gif|svg|webp|css|js|ico|txt|xml|map|woff2?|ttf|json)$/i', $slug)
            ) {
                // 1. Kiểm tra TourCategory (Danh mục Tour)
                $tourCategory = \Src\Domains\Cms\Models\TourCategory::published()->where('slug', $slug)->first();
                if ($tourCategory) {
                    return redirect()->route('tour-categories.show', ['category' => $tourCategory->slug]);
                }

                // 2. Kiểm tra ContentCategory (Danh mục Bài viết / Danh mục Dịch vụ)
                $contentCategory = \Src\Domains\Cms\Models\ContentCategory::where('slug', $slug)->first();
                if ($contentCategory) {
                    if ($contentCategory->taxonomy === 'blog') {
                        return redirect()->route('blog-categories.show', ['slug' => $contentCategory->slug]);
                    } elseif ($contentCategory->taxonomy === 'service') {
                        return redirect()->route('service-categories.show', ['category' => $contentCategory->slug]);
                    }
                }

                // 3. Kiểm tra BlogPost (Bài viết)
                $post = \Src\Domains\Cms\Models\BlogPost::published()->where('slug', $slug)->first();
                if ($post) {
                    return redirect()->to(\App\Support\FrontsiteUrls::blogPost($post));
                }

                // 4. Kiểm tra Tour (Chương trình Tour)
                $tour = \Src\Domains\Cms\Models\Tour::published()->where('slug', $slug)->first();
                if ($tour) {
                    return redirect()->route('tours.show', ['tour' => $tour->slug]);
                }

                // 5. Kiểm tra Destination (Điểm đến / Quốc gia)
                $destination = \Src\Domains\Cms\Models\Destination::published()->where('slug', $slug)->first();
                if ($destination) {
                    if ($destination->is_country_root) {
                        return redirect()->route('countries.show', ['slug' => $destination->slug]);
                    }
                    return redirect()->route('destinations.show', ['destination' => $destination->slug]);
                }

                // 6. Kiểm tra Region (Vùng miền)
                $region = \Src\Domains\Cms\Models\Region::published()->where('slug', $slug)->first();
                if ($region) {
                    return redirect()->route('regions.show', ['region' => $region->slug]);
                }

                // 7. Kiểm tra Service (Dịch vụ)
                $service = \Src\Domains\Cms\Models\Service::published()->where('slug', $slug)->first();
                if ($service) {
                    return redirect()->route('services.show', ['service' => $service->slug]);
                }

                // 8. Kiểm tra LandingPage (Trang tĩnh)
                $landing = \Src\Domains\Cms\Models\LandingPage::where('slug', $slug)->where('is_active', true)->first();
                if ($landing) {
                    return redirect()->route('landing.show', ['slug' => $landing->slug]);
                }

                // Nếu không khớp thực thể nào chính xác, chuyển sang tìm kiếm từ khóa
                $keyword = str_replace('-', ' ', $slug);

                if (trim($keyword) !== '') {
                    return redirect()->route('tours.search', ['q' => $keyword]);
                }
            }

            $settings = app(SiteSettingsManager::class)->current();
            $siteName = trim((string) $settings->site_name) ?: config('app.name', 'Haidang Travel');

            return response()
                ->view('themes.haidangtravel.pages.errors.404', [
                    'seo' => [
                        'title' => 'Không tìm thấy trang | '.$siteName,
                        'description' => 'Trang bạn đang tìm hiện không còn tồn tại. Hãy quay về trang chủ hoặc xem các tour mới và tour nổi bật của Hải Đăng Travel.',
                        'canonical' => $request->url(),
                        'robots' => 'noindex,follow',
                        'type' => 'website',
                        'og_title' => 'Không tìm thấy trang | '.$siteName,
                        'og_description' => 'Gợi ý các tour mới cập nhật và tour nổi bật để bạn tiếp tục tìm hành trình phù hợp.',
                        'schema' => null,
                    ],
                ], 404)
                ->header('X-Robots-Tag', 'noindex,follow');
        });
    })->create();
