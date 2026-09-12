<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\PageSnapshotService;
use App\Support\LandingPageBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Tests\TestCase;

class PageRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['seo_optimization.site_id' => 'haidang-test', 'frontsite_seo.canonical_url' => 'https://haidangtravel.test']);
    }

    public function test_inventory_supports_all_sixteen_types_and_uses_actual_travel_urls(): void
    {
        $category = TourCategory::query()->create(['name' => 'Khám phá', 'slug' => 'kham-pha', 'status' => 'published']);
        $region = Region::query()->create(['name' => 'Miền Trung', 'slug' => 'mien-trung', 'status' => 'published']);
        $country = Destination::query()->updateOrCreate(['slug' => 'du-lich-viet-nam'], ['name' => 'Việt Nam', 'is_country_root' => true, 'status' => 'published']);
        $destination = Destination::query()->create(['name' => 'Đà Nẵng', 'slug' => 'da-nang', 'country_id' => $country->id, 'status' => 'published']);
        Tour::query()->create(['title' => 'Khám phá Đà Nẵng', 'slug' => 'kham-pha-da-nang', 'scope' => 'domestic', 'status' => 'published', 'tour_category_id' => $category->id, 'region_id' => $region->id, 'destination_id' => $destination->id]);
        $serviceCategory = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Dịch vụ tour',
            'slug' => 'dich-vu-tour',
            'description' => '<p>Mô tả danh mục dịch vụ.</p>',
        ]);
        Service::query()->create(['title' => 'Tư vấn hành trình', 'slug' => 'tu-van-hanh-trinh', 'status' => 'published', 'content_category_id' => $serviceCategory->id]);
        $blogCategory = ContentCategory::query()->create(['taxonomy' => 'blog', 'name' => 'Cẩm nang', 'slug' => 'cam-nang']);
        BlogPost::query()->create(['title' => 'Cẩm nang Đà Nẵng', 'slug' => 'cam-nang-da-nang', 'status' => 'published', 'content_category_id' => $blogCategory->id]);
        LandingPage::query()->create(['title' => 'Du lịch mùa thu', 'slug' => 'du-lich-mua-thu', 'is_active' => true]);

        $result = app(PageRegistryService::class)->sync();

        $this->assertEqualsCanonicalizing(PageRegistryService::PAGE_TYPES, array_keys($result['by_type']));
        $this->assertGreaterThanOrEqual(18, $result['total']);
        $this->assertDatabaseHas('seo_optimization_pages', ['page_type' => 'country', 'owner_type' => 'destination', 'owner_id' => (string) $country->id, 'path' => '/tour-du-lich-viet-nam']);
        $this->assertDatabaseHas('seo_optimization_pages', ['page_type' => 'blog_post', 'path' => '/cam-nang/cam-nang-da-nang']);
        $this->assertDatabaseHas('seo_optimization_pages', ['page_type' => 'tour', 'path' => '/chuong-trinh/kham-pha-da-nang']);
        $this->assertSame(0, SeoOptimizationPage::query()->where('path', 'like', '/seo-pages/%')->count());
        $serviceCategoryPage = SeoOptimizationPage::query()
            ->where('page_type', 'service_category')
            ->where('owner_id', (string) $serviceCategory->id)
            ->firstOrFail();
        $this->assertContains('description', app(PageRegistryService::class)->writableFields($serviceCategoryPage));
        $this->assertSame('<p>Mô tả danh mục dịch vụ.</p>', app(PageRegistryService::class)->sourceFields($serviceCategoryPage)['description']);
        SiteSetting::query()->create(['id' => 1, 'active_theme' => 'haidangtravel', 'site_name' => 'Hải Đăng Travel']);
        foreach (SeoOptimizationPage::query()->get() as $page) {
            $snapshot = app(PageSnapshotService::class)->capture($page);
            $this->assertSame(200, $snapshot['http_status'], $page->page_type.':'.$page->path);
            $this->assertSame('anonymous_local_blade', $snapshot['render_mode']);
        }
    }

    public function test_identity_survives_slug_changes_and_system_landing_backfill(): void
    {
        $service = Service::query()->create(['title' => 'Tư vấn tour', 'slug' => 'tu-van-tour', 'status' => 'published']);
        $registry = app(PageRegistryService::class);
        $registry->sync();
        $servicePageId = SeoOptimizationPage::query()->where('page_type', 'service')->value('id');
        $homePageId = SeoOptimizationPage::query()->where('page_type', 'home')->value('id');
        $service->update(['slug' => 'tu-van-hanh-trinh']);
        $home = LandingPage::query()->create(['page_key' => 'home', 'title' => 'Hải Đăng Travel', 'is_active' => true]);

        $registry->sync();

        $this->assertDatabaseHas('seo_optimization_pages', ['id' => $servicePageId, 'path' => '/dich-vu/tu-van-hanh-trinh']);
        $this->assertDatabaseHas('seo_optimization_pages', ['id' => $homePageId, 'owner_type' => 'landing_page', 'owner_id' => (string) $home->id]);
        $this->assertSame(1, SeoOptimizationPage::query()->where('page_type', 'home')->count());
    }

    public function test_admin_edit_urls_follow_each_cms_owner_type(): void
    {
        $tour = Tour::query()->create(['title' => 'Tour Phú Quốc', 'slug' => 'tour-phu-quoc', 'scope' => 'domestic', 'status' => 'published']);
        $tourCategory = TourCategory::query()->create(['name' => 'Tour biển', 'slug' => 'tour-bien', 'status' => 'published']);
        $country = Destination::query()->create(['name' => 'Việt Nam', 'slug' => 'viet-nam', 'is_country_root' => true, 'status' => 'published']);
        $destination = Destination::query()->create(['name' => 'Phú Quốc', 'slug' => 'phu-quoc', 'country_id' => $country->id, 'status' => 'published']);
        $region = Region::query()->create(['name' => 'Miền Nam', 'slug' => 'mien-nam', 'status' => 'published']);
        $serviceCategory = ContentCategory::query()->create(['taxonomy' => 'service', 'name' => 'Dịch vụ tour', 'slug' => 'dich-vu-tour']);
        $service = Service::query()->create(['title' => 'Tư vấn tour', 'slug' => 'tu-van-tour', 'status' => 'published', 'content_category_id' => $serviceCategory->id]);
        $blogCategory = ContentCategory::query()->create(['taxonomy' => 'blog', 'name' => 'Kinh nghiệm', 'slug' => 'kinh-nghiem']);
        $blogPost = BlogPost::query()->create(['title' => 'Đi Phú Quốc', 'slug' => 'di-phu-quoc', 'status' => 'published', 'content_category_id' => $blogCategory->id]);
        $landing = LandingPage::query()->create(['title' => 'Du lịch mùa thu', 'slug' => 'du-lich-mua-thu', 'is_active' => true]);
        $registry = app(PageRegistryService::class);
        $registry->sync();

        $expectations = [
            [$tour, 'tour', 'admin.tours.edit'],
            [$tourCategory, 'tour_category', 'admin.tours.categories.edit'],
            [$country, 'country', 'admin.tours.destinations.edit'],
            [$destination, 'destination', 'admin.tours.destinations.edit'],
            [$region, 'region', 'admin.tours.regions.edit'],
            [$service, 'service', 'admin.services.edit'],
            [$serviceCategory, 'service_category', 'admin.services.categories.edit'],
            [$blogCategory, 'blog_category', 'admin.blogs.categories.edit'],
            [$blogPost, 'blog_post', 'admin.blogs.edit'],
            [$landing, 'landing', 'admin.landing-pages.edit'],
        ];

        foreach ($expectations as [$source, $pageType, $routeName]) {
            $page = SeoOptimizationPage::query()
                ->where('page_type', $pageType)
                ->where('owner_id', (string) $source->getKey())
                ->firstOrFail();

            $this->assertSame(route($routeName, $source), $registry->adminEditUrl($page), $pageType);
        }

        $this->assertNull($registry->adminEditUrl(
            SeoOptimizationPage::query()->where('page_type', 'home')->firstOrFail(),
        ));
    }

    public function test_draft_and_deleted_sources_are_not_available_to_ai_and_versions_include_dependencies(): void
    {
        $service = Service::query()->create(['title' => 'Dịch vụ tour', 'slug' => 'dich-vu-tour', 'status' => 'published', 'content' => 'Nội dung đã công khai']);
        Service::query()->create(['title' => 'Dịch vụ khác', 'slug' => 'dich-vu-khac', 'status' => 'published', 'content' => 'Nội dung khác']);
        $draft = BlogPost::query()->create(['title' => 'Nội bộ', 'slug' => 'noi-bo', 'status' => 'draft', 'content' => 'PRIVATE_SECRET']);
        $registry = app(PageRegistryService::class);
        $registry->sync();
        $page = SeoOptimizationPage::query()->where('page_type', 'service')->firstOrFail();
        $before = $registry->currentVersion($page);
        DB::table('services')->where('slug', 'dich-vu-khac')->update(['content' => 'Nội dung khác đã đổi']);
        $this->assertSame($before, $registry->currentVersion($page));
        $this->travel(1)->days();
        $this->assertSame($before, $registry->currentVersion($page));
        DB::table('services')->where('id', $service->id)->update(['content' => 'Nội dung mới trong cùng giây']);
        $this->assertNotSame($before, $registry->currentVersion($page));
        $draftPage = SeoOptimizationPage::query()->where('page_type', 'blog_post')->firstOrFail();
        $this->assertSame('DRAFT_OR_PRIVATE', $draftPage->classification);
        $this->assertSame([], $registry->sourceFields($draftPage));
        $this->assertSame([], $registry->writableFields($draftPage));
        $draft->delete();
        $registry->sync();
        $this->assertSame('UNRESOLVED', $draftPage->fresh()->classification);
    }

    public function test_capabilities_do_not_offer_unused_block_body_or_commercial_and_secret_fields(): void
    {
        $tour = Tour::query()->create(['title' => 'Tour Đà Nẵng', 'slug' => 'tour-da-nang', 'scope' => 'domestic', 'status' => 'published', 'review_submission_token' => 'PRIVATE_TOKEN']);
        LandingPage::query()->create([
            'title' => 'Trang block',
            'slug' => 'trang-block',
            'editor_mode' => 'blocks',
            'blocks' => [[...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT), 'uuid' => 'rich-main']],
            'is_active' => true,
        ]);
        $registry = app(PageRegistryService::class);
        $registry->sync();
        $tourPage = SeoOptimizationPage::query()->where('page_type', 'tour')->firstOrFail();
        $landingPage = SeoOptimizationPage::query()->where('page_type', 'landing')->firstOrFail();

        $this->assertNotContains('body', $registry->writableFields($landingPage));
        $this->assertContains('block_changes', $registry->writableFields($landingPage));
        $this->assertContains('meta_description', $registry->writableFields($landingPage));
        $this->assertNotContains('base_price', $registry->writableFields($tourPage));
        $this->assertNotContains('canonical_url', $registry->writableFields($tourPage));
        $this->assertArrayNotHasKey('review_submission_token', $registry->sourceFields($tourPage));
    }

    public function test_content_contracts_follow_each_cms_editor_and_split_landing_modes(): void
    {
        $tour = Tour::query()->create(['title' => 'Tour Phú Quốc', 'slug' => 'tour-phu-quoc', 'scope' => 'domestic', 'status' => 'published']);
        $tourCategory = TourCategory::query()->create(['name' => 'Tour biển', 'slug' => 'tour-bien', 'status' => 'published']);
        $destination = Destination::query()->create(['name' => 'Phú Quốc', 'slug' => 'phu-quoc', 'status' => 'published']);
        $blogCategory = ContentCategory::query()->create(['taxonomy' => 'blog', 'name' => 'Kinh nghiệm', 'slug' => 'kinh-nghiem', 'description' => 'Mô tả thuần văn bản']);
        $blogPost = BlogPost::query()->create(['title' => 'Đi Phú Quốc', 'slug' => 'di-phu-quoc', 'status' => 'published', 'content_category_id' => $blogCategory->id]);
        $htmlLanding = LandingPage::query()->create(['title' => 'Landing HTML', 'slug' => 'landing-html', 'editor_mode' => 'html', 'body' => '<section>Nội dung HTML</section>', 'is_active' => true]);
        $blockLanding = LandingPage::query()->create([
            'title' => 'Landing blocks',
            'slug' => 'landing-blocks',
            'editor_mode' => 'blocks',
            'blocks' => [
                [
                    ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT),
                    'uuid' => 'rich-main',
                    'title' => 'Giới thiệu',
                    'body' => '<p>Nội dung block</p>',
                ],
                [
                    ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_CTA),
                    'uuid' => 'cta-disabled',
                    'is_enabled' => false,
                ],
            ],
            'is_active' => true,
        ]);
        $legacyBlockLanding = LandingPage::query()->create([
            'title' => 'Landing block thiếu định danh',
            'slug' => 'landing-block-khong-uuid',
            'editor_mode' => 'blocks',
            'blocks' => [['type' => 'rich_text', 'title' => 'Nội dung cũ', 'body' => '<p>Chưa có UUID ổn định.</p>']],
            'is_active' => true,
        ]);
        $registry = app(PageRegistryService::class);
        $registry->sync();
        $pageFor = fn (string $type, int $ownerId): SeoOptimizationPage => SeoOptimizationPage::query()
            ->where('page_type', $type)->where('owner_id', (string) $ownerId)->firstOrFail();

        $this->assertSame(
            ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description', 'cover_alt', 'faq_items'],
            $registry->writableFields($pageFor('tour', $tour->id)),
        );
        $this->assertSame(
            ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description', 'cover_alt', 'faq_items'],
            $registry->writableFields($pageFor('blog_post', $blogPost->id)),
        );
        $this->assertSame(
            ['name', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description', 'cover_alt', 'faq_items'],
            $registry->writableFields($pageFor('destination', $destination->id)),
        );
        $this->assertSame(
            ['name', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description', 'cover_alt', 'faq_items'],
            $registry->writableFields($pageFor('tour_category', $tourCategory->id)),
        );
        $this->assertSame(
            ['name', 'slug', 'description', 'faq_items'],
            $registry->writableFields($pageFor('blog_category', $blogCategory->id)),
        );
        $this->assertSame(
            ['title', 'meta_title', 'meta_description', 'slug', 'body'],
            $registry->writableFields($pageFor('landing', $htmlLanding->id)),
        );
        $blockPage = $pageFor('landing', $blockLanding->id);
        $this->assertSame(
            ['title', 'meta_title', 'meta_description', 'slug', 'block_changes'],
            $registry->writableFields($blockPage),
        );
        $this->assertSame('rich-main', data_get($registry->contentUnits($blockPage), '0.uuid'));
        $this->assertCount(1, $registry->contentUnits($blockPage));
        $this->assertSame('rich_html', data_get($registry->contentUnits($blockPage), '0.fields.body.kind'));
        $legacyBlockPage = $pageFor('landing', $legacyBlockLanding->id);
        $this->assertSame([], $registry->contentUnits($legacyBlockPage));
        $this->assertNotContains('block_changes', $registry->writableFields($legacyBlockPage));
        $this->assertArrayNotHasKey('content', $registry->sourceFields($pageFor('blog_category', $blogCategory->id)));
    }
}
