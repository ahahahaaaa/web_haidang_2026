<?php

namespace Tests\Feature;

use App\Services\Frontsite\FrontsiteCacheInvalidator;
use App\Services\Frontsite\FrontsiteGeoPresenter;
use App\Support\FrontsiteUrls;
use App\Support\LandingPageBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Tests\TestCase;

class FrontsiteGeoComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['frontsite_geo.enabled' => true]);
    }

    public function test_geo_config_casts_on_public_models(): void
    {
        $geoConfig = [
            'is_enabled' => true,
            'answer_summary' => 'Tóm tắt GEO cho trang du lịch Việt Nam.',
            'decision_notes' => ['So sánh lịch trình với nhu cầu thực tế.'],
            'updated_label' => 'Cập nhật tháng 05/2026',
        ];
        $category = TourCategory::query()->create([
            'name' => 'Tour biển',
            'slug' => 'tour-bien',
            'status' => 'published',
            'geo_config' => $geoConfig,
        ]);
        $region = Region::query()->create([
            'name' => 'Miền Trung',
            'slug' => 'mien-trung',
            'status' => 'published',
            'geo_config' => $geoConfig,
        ]);
        $destination = Destination::query()->create([
            'name' => 'Đà Nẵng',
            'slug' => 'da-nang',
            'status' => 'published',
            'region_id' => $region->getKey(),
            'geo_config' => $geoConfig,
        ]);
        $tour = Tour::query()->create([
            'title' => 'Tour Đà Nẵng 3 ngày',
            'slug' => 'tour-da-nang-3-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'geo_config' => $geoConfig,
        ]);
        $serviceCategory = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Visa',
            'slug' => 'visa',
            'geo_config' => $geoConfig,
        ]);
        $service = Service::query()->create([
            'title' => 'Dịch vụ visa du lịch',
            'slug' => 'dich-vu-visa-du-lich',
            'status' => 'published',
            'content_category_id' => $serviceCategory->getKey(),
            'geo_config' => $geoConfig,
        ]);
        $blogCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang du lịch',
            'slug' => 'cam-nang-du-lich',
            'geo_config' => $geoConfig,
        ]);
        $post = BlogPost::query()->create([
            'title' => 'Chuẩn bị hành lý đi biển',
            'slug' => 'chuan-bi-hanh-ly-di-bien',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'author_name' => 'Hải Đăng Travel',
            'published_at' => now()->subDay(),
            'geo_config' => $geoConfig,
        ]);
        $landing = LandingPage::query()->create([
            'page_key' => 'geo-test',
            'title' => 'Landing GEO',
            'is_active' => true,
            'geo_config' => $geoConfig,
        ]);

        $this->assertSame($geoConfig['answer_summary'], $category->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $region->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $destination->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $tour->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $serviceCategory->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $service->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $blogCategory->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $post->fresh()->geo_config['answer_summary']);
        $this->assertSame($geoConfig['answer_summary'], $landing->fresh()->geo_config['answer_summary']);
    }

    public function test_presenter_uses_override_summary_but_keeps_commercial_facts_field_backed(): void
    {
        $records = $this->createTourRecords([
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Override copy dùng cho AI search.',
                'decision_notes' => ['Chọn lịch khởi hành phù hợp trước khi gửi yêu cầu.'],
                'updated_label' => '',
            ],
            'sale_price' => 1230000,
        ]);

        $payload = app(FrontsiteGeoPresenter::class)->forTour($records['tour']->fresh(['primaryCategory', 'destination', 'region']));
        $facts = collect($payload['facts']);

        $this->assertSame('Override copy dùng cho AI search.', $payload['summary']);
        $this->assertSame(['Chọn lịch khởi hành phù hợp trước khi gửi yêu cầu.'], $payload['decision_notes']);
        $this->assertTrue($facts->contains(fn (array $fact): bool => $fact['label'] === 'Giá tham khảo' && $fact['value'] === '1.230.000đ'));
        $this->assertTrue($facts->contains(fn (array $fact): bool => $fact['label'] === 'Điểm đến' && $fact['value'] === 'Đà Nẵng'));
    }

    public function test_frontsite_geo_panel_renders_and_can_be_disabled_on_public_pages(): void
    {
        $records = $this->createTourRecords([
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Tour này phù hợp để so sánh lịch đi Đà Nẵng.',
                'decision_notes' => [],
                'updated_label' => '',
            ],
        ]);
        $service = $this->createService([
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Dịch vụ visa có phần tóm tắt GEO riêng.',
                'decision_notes' => [],
                'updated_label' => '',
            ],
        ]);
        $post = $this->createBlogPost([
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Bài viết này tóm tắt kinh nghiệm chuẩn bị hồ sơ.',
                'decision_notes' => [],
                'updated_label' => '',
            ],
        ]);

        $this->get(route('tours.show', $records['tour']))
            ->assertOk()
            ->assertSee('data-geo-answer-panel', false)
            ->assertSeeText('Tour này phù hợp để so sánh lịch đi Đà Nẵng.');

        $this->get(route('destinations.show', $records['destination']))
            ->assertOk()
            ->assertSee('data-geo-answer-panel', false)
            ->assertSeeText('Đà Nẵng');

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('data-geo-answer-panel', false)
            ->assertSeeText('Dịch vụ visa có phần tóm tắt GEO riêng.');

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('data-geo-answer-panel', false)
            ->assertSeeText('Bài viết này tóm tắt kinh nghiệm chuẩn bị hồ sơ.');

        $service->update(['geo_config' => ['is_enabled' => false]]);

        $this->get(route('services.show', $service->fresh()))
            ->assertOk()
            ->assertDontSee('data-geo-answer-panel', false);
    }

    public function test_frontsite_geo_panel_can_be_disabled_from_config(): void
    {
        config(['frontsite_geo.enabled' => false]);

        $records = $this->createTourRecords([
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Nội dung GEO sẽ không render khi flag global tắt.',
                'decision_notes' => [],
                'updated_label' => '',
            ],
        ]);

        $payload = app(FrontsiteGeoPresenter::class)->forTour($records['tour']->fresh(['primaryCategory', 'destination', 'region']));

        $this->assertSame(['is_enabled' => false], $payload);

        $this->get(route('tours.show', $records['tour']))
            ->assertOk()
            ->assertDontSee('data-geo-answer-panel', false)
            ->assertDontSeeText('Nội dung GEO sẽ không render khi flag global tắt.');
    }

    public function test_geo_panel_does_not_create_faq_schema_without_visible_faq(): void
    {
        $service = $this->createService([
            'faq_items' => [],
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Tóm tắt GEO không được biến thành FAQ schema.',
                'decision_notes' => ['Đọc phạm vi dịch vụ trước khi gửi yêu cầu.'],
                'updated_label' => '',
            ],
        ]);

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('data-geo-answer-panel', false)
            ->assertSee('"@type":"Service"', false)
            ->assertDontSee('"@type":"FAQPage"', false);
    }

    public function test_landing_geo_answer_block_overrides_default_geo_position(): void
    {
        $page = LandingPage::query()->create([
            'page_key' => null,
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'title' => 'Tour hè miền Trung',
            'slug' => 'tour-he-mien-trung',
            'is_active' => true,
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Fallback GEO từ landing page.',
                'decision_notes' => [],
                'updated_label' => '',
            ],
            'blocks' => [
                [
                    'uuid' => 'geo-block',
                    'type' => LandingPageBlocks::TYPE_GEO_ANSWER,
                    'is_enabled' => true,
                    'title' => 'Tóm tắt thủ công',
                    'answer_summary' => 'Manual GEO block tại đúng vị trí trong stack.',
                    'decision_notes' => ['Block này thắng fallback của landing page.'],
                ],
            ],
        ]);

        $this->get(route('landing.show', ['slug' => $page->slug]))
            ->assertOk()
            ->assertSeeText('Manual GEO block tại đúng vị trí trong stack.')
            ->assertSeeText('Block này thắng fallback của landing page.')
            ->assertDontSeeText('Fallback GEO từ landing page.');
    }

    public function test_geo_config_changes_use_existing_frontsite_cache_groups(): void
    {
        $records = $this->createTourRecords();
        $service = $this->createService();
        $post = $this->createBlogPost();
        $invalidator = app(FrontsiteCacheInvalidator::class);

        $this->assertContains('tour:'.$records['tour']->getKey(), $invalidator->groupsFor($records['tour']));
        $this->assertContains('destination:'.$records['destination']->getKey(), $invalidator->groupsFor($records['destination']));
        $this->assertContains('service:'.$service->getKey(), $invalidator->groupsFor($service));
        $this->assertContains('blog-post:'.$post->getKey(), $invalidator->groupsFor($post));
    }

    /**
     * @return array{category: TourCategory, region: Region, destination: Destination, tour: Tour}
     */
    private function createTourRecords(array $tourOverrides = []): array
    {
        $category = TourCategory::query()->create([
            'name' => 'Tour biển',
            'slug' => 'tour-bien-'.uniqid(),
            'status' => 'published',
            'excerpt' => 'Các tour biển phù hợp gia đình và nhóm bạn.',
        ]);
        $region = Region::query()->create([
            'name' => 'Miền Trung',
            'slug' => 'mien-trung-'.uniqid(),
            'status' => 'published',
            'excerpt' => 'Hub tour miền Trung.',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Đà Nẵng',
            'slug' => 'da-nang-'.uniqid(),
            'status' => 'published',
            'region_id' => $region->getKey(),
            'excerpt' => 'Điểm đến biển miền Trung.',
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'Đà Nẵng là hub tour biển miền Trung.',
                'decision_notes' => [],
                'updated_label' => '',
            ],
        ]);
        $tour = Tour::query()->create(array_merge([
            'title' => 'Tour Đà Nẵng 3 ngày',
            'slug' => 'tour-da-nang-3-ngay-'.uniqid(),
            'excerpt' => 'Lịch trình Đà Nẵng 3 ngày cho khách cần đi nhanh.',
            'content' => '<p>Nội dung tour Đà Nẵng.</p>',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'duration_days' => 3,
            'duration_nights' => 2,
            'transport' => 'Máy bay',
            'sale_price' => 3500000,
        ], $tourOverrides));
        $tour->syncTaxonomyLinks([$category->getKey()], [$destination->getKey()], [$region->getKey()]);

        return compact('category', 'region', 'destination', 'tour');
    }

    private function createService(array $overrides = []): Service
    {
        $category = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Visa',
            'slug' => 'visa-'.uniqid(),
            'description' => 'Dịch vụ visa và hồ sơ du lịch.',
        ]);

        return Service::query()->create(array_merge([
            'title' => 'Dịch vụ visa du lịch',
            'slug' => 'dich-vu-visa-du-lich-'.uniqid(),
            'excerpt' => 'Hỗ trợ hồ sơ visa du lịch.',
            'content' => '<p>Nội dung dịch vụ visa.</p>',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
            'price_note' => 'Liên hệ',
        ], $overrides));
    }

    private function createBlogPost(array $overrides = []): BlogPost
    {
        $category = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang visa',
            'slug' => 'cam-nang-visa-'.uniqid(),
            'description' => 'Bài viết về visa và chuẩn bị hồ sơ.',
        ]);

        return BlogPost::query()->create(array_merge([
            'title' => 'Kinh nghiệm chuẩn bị visa du lịch',
            'slug' => 'kinh-nghiem-chuan-bi-visa-du-lich-'.uniqid(),
            'excerpt' => 'Checklist giấy tờ và mốc thời gian cần chuẩn bị.',
            'content' => '<p>Nội dung bài viết visa.</p>',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
            'author_name' => 'Hải Đăng Travel',
            'published_at' => now()->subDay(),
        ], $overrides));
    }
}
