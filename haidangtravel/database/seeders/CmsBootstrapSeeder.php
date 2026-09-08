<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Admin\AdminNavigationRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\ProjectType;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;

class CmsBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->cleanupDeprecatedEstimateBootstrapRecords();

        $permissions = collect([
            ...AdminNavigationRegistry::fullAdminPermissions(),
            ...AdminNavigationRegistry::legacyPermissionKeys(),
            'manage projects',
        ])->unique()->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $content = Role::findOrCreate('content', 'web');
        $sale = Role::findOrCreate('sale', 'web');
        $seoManager = Role::findOrCreate('seo_manager', 'web');

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions($permissions);
        $content->syncPermissions(AdminNavigationRegistry::defaultContentPermissions());
        $sale->syncPermissions(AdminNavigationRegistry::defaultSalePermissions());
        $seoManager->syncPermissions(['access admin panel', 'manage seo']);

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ],
        );

        $user->syncRoles(['super_admin']);

        $quanTri = User::query()->updateOrCreate(
            ['email' => 'quantri@phongthanhdat.vn'],
            [
                'name' => 'quantri',
                'password' => Hash::make('Abc123!@#'),
            ],
        );

        $quanTri->syncRoles(['super_admin']);

        $contentUser = User::query()->updateOrCreate(
            ['email' => 'content@example.com'],
            [
                'name' => 'content',
                'password' => Hash::make('Abc123!@#'),
            ],
        );

        $contentUser->syncRoles(['content']);

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Phong Thành Đạt',
                'company_name' => 'Công ty Xây dựng Phong Thành Đạt',
                'active_theme' => 'haidangtravel',
                'site_tagline' => 'Xây dựng giá trị vững bền',
                'site_description' => 'Giải pháp xây dựng, thiết kế nội thất, thi công và triển khai dự án tại Việt Nam.',
                'about_summary' => 'Đơn vị tổng thầu xây dựng và thiết kế nội thất theo định hướng doanh nghiệp hiện đại, tập trung vào chất lượng, tiến độ và giải pháp thực chiến.',
                'address' => '123 Đường Mẫu, Thành phố Hồ Chí Minh',
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'hello@phongthanhdat.vn',
                'support_email' => 'support@phongthanhdat.vn',
                'sales_email' => 'sales@phongthanhdat.vn',
                'mail_from_name' => 'Phong Thành Đạt',
                'mail_from_address' => 'hello@phongthanhdat.vn',
                'mail_contact_recipient' => 'hello@phongthanhdat.vn',
                'facebook_url' => 'https://facebook.com/phongthanhdat',
                'youtube_url' => 'https://youtube.com/@phongthanhdat',
                'linkedin_url' => 'https://linkedin.com/company/phongthanhdat',
                'experience_years' => 15,
                'completed_projects_count' => 320,
                'team_size' => 85,
                'quality_badge_label' => 'ISO / QA',
                'seo_title' => 'Phong Thành Đạt | Xây dựng và thiết kế nội thất',
                'seo_description' => 'Đơn vị xây dựng, thi công và thiết kế nội thất cho nhà phố, biệt thự, văn phòng và dự án thương mại.',
                'seo_keywords' => 'xây dựng, dự án, dịch vụ, blog, nội thất',
                'seo_robots' => 'index,follow',
                'copyright_text' => '© Phong Thành Đạt',
            ],
        );

        $header = Menu::query()->firstOrCreate(['location' => 'header'], ['name' => 'Header Menu']);
        $footer = Menu::query()->firstOrCreate(['location' => 'footer'], ['name' => 'Footer Menu']);

        foreach ([
            [$header, 'Trang chủ', '/', 1],
            [$header, 'Về chúng tôi', '/ve-chung-toi', 2],
            [$header, 'Dự án', '/du-an', 3],
            [$header, 'Dịch vụ', '/dich-vu', 4],
            [$header, 'Blog', '/blog', 5],
            [$footer, 'Về chúng tôi', '/ve-chung-toi', 1],
            [$footer, 'Dự án', '/du-an', 2],
            [$footer, 'Dịch vụ', '/dich-vu', 3],
            [$footer, 'Blog', '/blog', 4],
        ] as [$menu, $label, $url, $order]) {
            MenuItem::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'label' => $label],
                ['url' => $url, 'order' => $order, 'is_active' => true, 'target' => '_self'],
            );
        }

        $serviceCategories = collect([
            'Xây dựng',
            'Thiết kế nội thất',
        ])->map(fn (string $name, int $index) => ContentCategory::query()->updateOrCreate(
            ['taxonomy' => 'service', 'slug' => Str::slug($name)],
            ['name' => $name, 'sort_order' => $index + 1],
        ));

        $projectCategory = ContentCategory::query()->updateOrCreate(
            ['taxonomy' => 'project', 'slug' => 'biet-thu'],
            ['name' => 'Biệt thự', 'sort_order' => 1],
        );

        $blogCategory = ContentCategory::query()->updateOrCreate(
            ['taxonomy' => 'blog', 'slug' => 'kien-thuc-xay-dung'],
            ['name' => 'Kiến thức xây dựng', 'sort_order' => 1],
        );

        $projectType = ProjectType::query()->updateOrCreate(
            ['slug' => 'residential'],
            ['name' => 'Residential', 'sort_order' => 1],
        );

        foreach ([
            ['about', 'Landing Về chúng tôi'],
            ['blog', 'Landing Blog'],
            ['home', 'Landing Trang chủ'],
            ['projects', 'Landing Dự án'],
            ['services', 'Landing Dịch vụ'],
        ] as [$key, $title]) {
            LandingPage::query()->updateOrCreate(
                ['page_key' => $key],
                [
                    'title' => $title,
                    'faq_items' => match ($key) {
                        'services' => [
                            [
                                'question' => 'Trang dịch vụ này giúp tôi làm gì trước khi liên hệ?',
                                'answer' => 'Trang dịch vụ giúp bạn xác định đúng nhóm nhu cầu, xem phạm vi triển khai phù hợp và chuẩn bị brief ban đầu rõ hơn trước khi yêu cầu tư vấn.',
                            ],
                            [
                                'question' => 'Khi nào nên chuyển sang trang dự toán?',
                                'answer' => 'Khi bạn đã có các thông số cơ bản của công trình và muốn chuẩn hóa đầu vào chi phí, hãy chuyển sang trang dự toán để nhập liệu theo từng cấp.',
                            ],
                        ],
                        'projects' => [
                            [
                                'question' => 'Tôi nên đọc trang dự án theo tiêu chí nào trước?',
                                'answer' => 'Bạn nên bắt đầu từ loại công trình, vị trí, diện tích và timeline để nhanh chóng tìm ra case study sát nhất với nhu cầu tham chiếu của mình.',
                            ],
                            [
                                'question' => 'Trang dự án có giúp gì ngoài việc xem hình ảnh?',
                                'answer' => 'Mỗi case study còn cho biết bối cảnh đầu bài, giải pháp triển khai, vật liệu hoặc kỹ thuật ưu tiên và giá trị bàn giao sau thi công.',
                            ],
                        ],
                        default => null,
                    },
                    'hero_badge' => strtoupper($key),
                    'hero_title' => $title,
                    'hero_excerpt' => 'Nội dung giới thiệu linh hoạt cho '.$title,
                    'robots_directive' => 'index,follow',
                ],
            );
        }

        foreach ([
            [
                'title' => 'Thi công nhà phố trọn gói',
                'slug' => 'thi-cong-nha-pho-tron-goi',
                'category_id' => $serviceCategories[0]->id,
                'icon_class' => 'fa-solid fa-building',
            ],
            [
                'title' => 'Thiết kế nội thất biệt thự',
                'slug' => 'thiet-ke-noi-that-biet-thu',
                'category_id' => $serviceCategories[1]->id,
                'icon_class' => 'fa-solid fa-couch',
            ],
        ] as $serviceData) {
            Service::query()->updateOrCreate(
                ['slug' => $serviceData['slug']],
                [
                    'title' => $serviceData['title'],
                    'excerpt' => 'Mô tả ngắn cho '.$serviceData['title'],
                    'content' => 'Nội dung chi tiết cho '.$serviceData['title'].' với quy trình, giá trị và CTA.',
                    'status' => 'published',
                    'content_category_id' => $serviceData['category_id'],
                    'icon_class' => $serviceData['icon_class'],
                    'is_featured' => true,
                    'meta_title' => $serviceData['title'].' | Phong Thành Đạt',
                    'meta_description' => 'Thông tin SEO cho '.$serviceData['title'],
                    'faq_items' => [
                        [
                            'question' => $serviceData['title'].' thường bao gồm những phần việc nào?',
                            'answer' => 'Dịch vụ này thường bao gồm bước khảo sát, xác định phạm vi, lập phương án triển khai và kiểm soát tiến độ theo từng giai đoạn thực hiện.',
                        ],
                        [
                            'question' => 'Tôi nên chuẩn bị gì trước khi yêu cầu '.$serviceData['title'].'?',
                            'answer' => 'Bạn nên chuẩn bị loại công trình, quy mô, hiện trạng và mốc thời gian mong muốn để đội ngũ tư vấn nhanh hơn và bám sát thực tế hơn.',
                        ],
                    ],
                    'related_questions' => [
                        'Trước khi yêu cầu '.$serviceData['title'].' tôi nên chuẩn bị những thông tin nào?',
                        'Làm sao để kiểm soát phát sinh khi triển khai '.$serviceData['title'].'?',
                    ],
                ],
            );
        }

        Project::query()->updateOrCreate(
            ['slug' => 'biet-thu-lakeview'],
            [
                'title' => 'Dự án biệt thự Lakeview',
                'excerpt' => 'Công trình biệt thự cao cấp tại khu đô thị Lakeview.',
                'content' => 'Nội dung chi tiết về dự án, giải pháp thi công, vật liệu và kết quả bàn giao.',
                'status' => 'published',
                'content_category_id' => $projectCategory->id,
                'project_type_id' => $projectType->id,
                'location' => 'Thu Duc, HCM',
                'area_value' => 420,
                'area_unit' => 'm2',
                'timeline' => '8 tháng',
                'completion_date' => now()->subMonths(2)->toDateString(),
                'is_featured' => true,
                'meta_title' => 'Dự án biệt thự Lakeview | Phong Thành Đạt',
                'meta_description' => 'Case study thi công biệt thự và nội thất hoàn chỉnh.',
                'faq_items' => [
                    [
                        'question' => 'Dự án Biệt thự Lakeview bắt đầu từ bài toán gì?',
                        'answer' => 'Công trình cần cân bằng giữa chất lượng hoàn thiện, tiến độ thi công và khả năng phối hợp nhiều hạng mục trong cùng một mặt bằng.',
                    ],
                    [
                        'question' => 'Điểm nào đáng tham chiếu nhất từ dự án này?',
                        'answer' => 'Giá trị lớn nhất nằm ở cách tổ chức phase thi công, phối hợp vật liệu và giữ mốc bàn giao ổn định trong suốt quá trình triển khai.',
                    ],
                ],
                'related_questions' => [
                    'Nếu tôi có công trình tương tự Biệt thự Lakeview, nên bắt đầu từ khảo sát hay lập brief trước?',
                    'Những yếu tố nào của dự án này có thể áp dụng cho công trình khác?',
                ],
            ],
        );

        BlogPost::query()->updateOrCreate(
            ['slug' => 'xu-huong-xay-dung-hien-dai-2026'],
            [
                'title' => 'Xu hướng xây dựng hiện đại 2026',
                'excerpt' => 'Tổng hợp những thay đổi đang định hình thị trường xây dựng hiện đại.',
                'content' => 'Nội dung bài viết về xu hướng xây dựng, vật liệu, quy trình và giải pháp tối ưu.',
                'status' => 'published',
                'content_category_id' => $blogCategory->id,
                'author_name' => 'Phong Thành Đạt',
                'published_at' => now()->subWeek(),
                'is_featured' => true,
                'meta_title' => 'Xu hướng xây dựng hiện đại 2026 | Phong Thành Đạt',
                'meta_description' => 'Bài viết blog hỗ trợ SEO và authority cho thương hiệu.',
            ],
        );

        $slider = Slider::query()->firstOrCreate(
            ['location' => 'home-hero'],
            ['name' => 'Home Hero Slider', 'is_active' => true, 'autoplay_delay' => 5000],
        );

        SliderItem::query()->updateOrCreate(
            ['slider_id' => $slider->id, 'order' => 1],
            [
                'title' => 'Kiến tạo không gian, xây dựng niềm tin',
                'subtitle' => 'Phong Thành Đạt',
                'description' => 'Hero slider cho trang chủ với CTA và hiệu ứng animate.css.',
                'effect' => 'animate__fadeInUp',
                'cta_label' => 'Xem dự án',
                'cta_url' => '/du-an',
                'is_active' => true,
            ],
        );
    }

    protected function cleanupDeprecatedEstimateBootstrapRecords(): void
    {
        LandingPage::query()->where('page_key', 'estimate')->delete();

        MenuItem::query()
            ->where(fn ($query) => $query
                ->where('label', 'Dự toán')
                ->orWhere('url', '/du-toan'))
            ->delete();
    }
}

