<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cms\LandingPagesManager;
use App\Models\User;
use App\Support\FrontsiteMedia;
use App\Support\LandingPageBlocks;
use App\Support\TravelHomePageConfig;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\VoucherCampaign;
use Src\Domains\Cms\Models\VoucherCode;
use Tests\TestCase;

class CustomLandingPageBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_tour_widget_list_mode_excludes_group_tours_and_keeps_item_list_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Bắc widget chung',
            'slug' => 'mien-bac-widget-chung',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Hà Nội widget chung',
            'slug' => 'ha-noi-widget-chung',
            'region_id' => $region->id,
            'status' => 'published',
        ]);

        Tour::query()->create([
            'title' => 'Tour thường kiểm thử widget',
            'slug' => 'tour-thuong-kiem-thu-widget',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'is_featured' => true,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);
        Tour::query()->create([
            'title' => 'Tour đoàn kiểm thử widget',
            'slug' => 'tour-doan-kiem-thu-widget',
            'status' => 'published',
            'scope' => TourScope::Group->value,
            'is_featured' => true,
        ]);

        $widget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $widget['display_mode'] = 'list';
        $widget['scope'] = 'non_group';
        $widget['title'] = 'Tour không phải đoàn';
        $widget['featured'] = true;
        $tabsWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $tabsWidget['scope'] = 'non_group';
        $tabsWidget['show_all_tab'] = true;
        $tabsWidget['tabs'] = [LandingPageBlocks::defaultTourTaxonomyTab('destination', $destination->slug)];

        LandingPage::query()->create([
            'title' => 'Kiểm thử widget tour chung',
            'slug' => 'kiem-thu-widget-tour-chung',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$widget, $tabsWidget],
        ]);

        $this->get('/kiem-thu-widget-tour-chung')
            ->assertOk()
            ->assertSeeText('Tour thường kiểm thử widget')
            ->assertDontSeeText('Tour đoàn kiểm thử widget')
            ->assertSee('scope=non_group&amp;destination=ha-noi-widget-chung', false)
            ->assertSee('"@type":"ItemList"', false);
    }

    public function test_custom_root_landing_page_renders_from_block_builder(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $category = ContentCategory::query()->forTaxonomy('blog')->firstOrFail();
        BlogPost::query()->create([
            'title' => 'Kinh nghiệm đi tour Nhật Bản',
            'slug' => 'kinh-nghiem-di-tour-nhat-ban',
            'excerpt' => 'Bài viết blog xuất hiện từ block query.',
            'content' => '<p>Nội dung bài viết</p>',
            'status' => 'published',
            'content_category_id' => $category->id,
            'author_name' => 'Admin',
        ]);

        $slider = Slider::query()->create([
            'name' => 'Custom landing slider',
            'location' => 'custom-tour-hero',
            'is_active' => true,
        ]);

        $slider->items()->create([
            'title' => 'Landing theo block builder',
            'subtitle' => 'Hero từ slider',
            'description' => 'Slide hero lấy từ slider riêng của landing custom.',
            'order' => 1,
            'is_active' => true,
        ]);

        $hero = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_SLIDER);
        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $blogList = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_BLOG_LIST);
        $faq = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FAQ);
        $hero['slider_id'] = $slider->id;
        $hero['title'] = 'Landing builder';
        $richText['eyebrow'] = 'Eyebrow rich text';
        $richText['title'] = 'Khối nội dung';
        $richText['body'] = '<p>Landing custom dùng block runtime.</p>';
        $blogList['title'] = 'Blog liên quan';
        $blogList['limit'] = 3;
        $faq['title'] = 'Câu hỏi thường gặp';
        $faq['items'] = [[
            'question' => 'Landing builder có schema gì?',
            'answer' => 'Landing block-aware sẽ tạo CollectionPage, ItemList và FAQPage đúng theo block đang hiển thị.',
        ]];

        LandingPage::query()->create([
            'title' => 'Landing custom',
            'slug' => 'custom-tour',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$hero, $richText, $blogList, $faq],
        ]);

        $this->get('/custom-tour')
            ->assertOk()
            ->assertSee('Landing theo block builder')
            ->assertSee('Eyebrow rich text')
            ->assertSee('Khối nội dung')
            ->assertSee('Kinh nghiệm đi tour Nhật Bản')
            ->assertSeeText('Landing builder có schema gì?')
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_custom_root_landing_page_preserves_visual_block_order(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $gallery = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_GALLERY_MEDIA);
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);

        $richText['title'] = 'Nội dung trước gallery';
        $richText['body'] = '<p data-block-order="rich">Rich text đứng trước gallery.</p>';
        $gallery['title'] = 'Gallery nằm sau nội dung';
        $gallery['items'][0]['title'] = 'Ảnh gallery theo thứ tự';
        $gallery['items'][0]['image_url'] = 'https://example.com/gallery-order.jpg';
        $htmlWidget['html'] = '<section data-block-order="html">HTML widget đứng sau gallery.</section>';

        LandingPage::query()->create([
            'title' => 'Landing giữ thứ tự block',
            'slug' => 'landing-giu-thu-tu-block',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$richText, $gallery, $htmlWidget],
        ]);

        $html = $this->get('/landing-giu-thu-tu-block')
            ->assertOk()
            ->assertSee('Rich text đứng trước gallery.')
            ->assertSee('Gallery nằm sau nội dung')
            ->assertSee('data-block-order="html"', false)
            ->getContent();

        $richPosition = strpos($html, 'Rich text đứng trước gallery.');
        $galleryPosition = strpos($html, 'Gallery nằm sau nội dung');
        $htmlWidgetPosition = strpos($html, 'data-block-order="html"');

        $this->assertNotFalse($richPosition);
        $this->assertNotFalse($galleryPosition);
        $this->assertNotFalse($htmlWidgetPosition);
        $this->assertLessThan($galleryPosition, $richPosition);
        $this->assertLessThan($htmlWidgetPosition, $galleryPosition);
    }

    public function test_custom_root_landing_page_can_render_trust_proof_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $trustProof = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TRUST_PROOF);
        $trustProof['title'] = 'Lý do khách chốt nhanh với Hải Đăng Travel';
        $trustProof['description'] = 'Dùng block proof ngắn để làm rõ đầu mối xử lý, khả năng đồng hành và cảm giác an tâm trước khi gửi yêu cầu.';
        $trustProof['cards'] = [
            [
                'uuid' => 'trust-proof-1',
                'highlight' => 'Một đầu mối xử lý',
                'title' => 'Một yêu cầu, đội ngũ theo tới cùng',
                'text' => 'Tour, visa và dịch vụ đi kèm được gom về cùng một luồng trao đổi rõ ràng hơn.',
            ],
            [
                'uuid' => 'trust-proof-2',
                'highlight' => 'So sánh dễ hơn',
                'title' => 'Thông tin tour được bày theo logic chốt mua',
                'text' => 'Khách dễ quét ngày đi, thời lượng và giá trước khi bấm vào chi tiết từng tour.',
            ],
        ];

        LandingPage::query()->create([
            'title' => 'Landing trust proof',
            'slug' => 'landing-trust-proof',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$trustProof],
        ]);

        $this->get('/landing-trust-proof')
            ->assertOk()
            ->assertSee('Lý do khách chốt nhanh với Hải Đăng Travel')
            ->assertSee('Một đầu mối xử lý')
            ->assertSee('Một yêu cầu, đội ngũ theo tới cùng')
            ->assertSee('So sánh dễ hơn')
            ->assertSee('Thông tin tour được bày theo logic chốt mua');
    }

    public function test_custom_root_landing_page_can_render_hero_demo_landingpage_block(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $category = TourCategory::query()->create([
            'name' => 'Hero Demo Landingpage',
            'slug' => 'hero-demo-landingpage',
            'status' => 'published',
            'is_featured' => true,
        ]);

        foreach (TourScope::cases() as $scope) {
            Tour::query()->create([
                'title' => match ($scope) {
                    TourScope::Domestic => 'Tour hero demo nội địa',
                    TourScope::International => 'Tour hero demo quốc tế',
                    TourScope::Group => 'Tour hero demo đoàn riêng',
                },
                'slug' => 'tour-hero-demo-'.$scope->value,
                'status' => 'published',
                'scope' => $scope->value,
                'tour_category_id' => $category->id,
                'departure_location' => 'TP.HCM',
                'duration_days' => 3,
                'duration_nights' => 2,
                'sale_price' => 3900000,
                'is_featured' => true,
            ]);
        }

        $heroDemo = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE);
        $heroDemo['title'] = 'Hero Demo Landingpage';
        $heroDemo['description'] = 'Block hero demo từ homepage dùng lại cho landing page khi cần.';
        $heroDemo['primary_label'] = 'Nhận tư vấn ngay';
        $heroDemo['secondary_label'] = 'Xem nhóm tour';
        $heroDemo['secondary_url'] = '/tour-trong-nuoc';
        $heroDemo['panel_title'] = 'Bắt đầu nhanh từ hero demo';
        $heroDemo['media_alt'] = 'Ảnh nền demo tùy chọn';
        $heroDemo['category_slug'] = $category->slug;
        $heroDemo['scope'] = TourScope::Group->value;

        $page = LandingPage::query()->create([
            'title' => 'Landing hero demo',
            'slug' => 'landing-hero-demo',
            'is_active' => true,
            'template_key' => 'generic',
            'hero_title' => 'Hero mặc định không được render',
            'blocks' => [$heroDemo],
        ]);
        $page
            ->addMedia(UploadedFile::fake()->image('hero-demo-override.jpg', 1600, 900))
            ->usingFileName('hero-demo-override.jpg')
            ->toMediaCollection(LandingPageBlocks::mediaCollection($heroDemo['uuid']), 'public');

        $this->get('/landing-hero-demo')
            ->assertOk()
            ->assertSee('Hero Demo Landingpage')
            ->assertSee('Block hero demo từ homepage dùng lại cho landing page khi cần.')
            ->assertSee('hero-demo-override', false)
            ->assertSee('Ảnh nền demo tùy chọn')
            ->assertSee('Nhận tư vấn ngay')
            ->assertSee('Bắt đầu nhanh từ hero demo')
            ->assertSee('Tour hero demo đoàn riêng')
            ->assertSee('Xem tour trong nước')
            ->assertSee('Xem tour nước ngoài')
            ->assertSee('Xem tour đoàn')
            ->assertSee('1 tour nổi bật')
            ->assertSee('data-travel-inquiry-open', false)
            ->assertSee('"@id":"'.url('/landing-hero-demo').'#hero-demo-1"', false)
            ->assertDontSee('Hero mặc định không được render');
    }

    public function test_custom_root_landing_page_can_render_region_rail_and_skip_disabled_blocks(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Bắc',
            'slug' => 'mien-bac',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Hub vùng miền dành cho các hành trình phía Bắc.',
            'is_featured' => true,
        ]);

        Tour::query()->create([
            'title' => 'Tour miền Bắc mùa lúa',
            'slug' => 'tour-mien-bac-mua-lua',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'region_id' => $region->id,
        ]);

        $disabledRichText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $regionRail = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_RAIL);
        $disabledRichText['is_enabled'] = false;
        $disabledRichText['title'] = 'Khối đang tắt';
        $disabledRichText['body'] = '<p>Nội dung không nên xuất hiện.</p>';
        $regionRail['title'] = 'Khám phá theo vùng miền';
        $regionRail['description'] = 'Chọn nhanh khu vực đang có tour hoạt động.';
        $regionRail['card_cta_label'] = 'Xem hub vùng miền';
        $regionRail['featured'] = true;
        $regionRail['scope'] = TourScope::Domestic->value;
        $regionRail['limit'] = 4;

        LandingPage::query()->create([
            'title' => 'Landing vùng miền',
            'slug' => 'landing-vung-mien',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$disabledRichText, $regionRail],
        ]);

        $this->get('/landing-vung-mien')
            ->assertOk()
            ->assertSee('Khám phá theo vùng miền')
            ->assertSee('Miền Bắc')
            ->assertSee('Xem hub vùng miền')
            ->assertSee('"@id":"'.url('/landing-vung-mien').'#region-rail-1"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@id":"'.route('regions.show', $region).'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertDontSee('Khối đang tắt')
            ->assertDontSee('Nội dung không nên xuất hiện.');
    }

    public function test_custom_root_landing_page_can_render_topic_rail_with_optional_heading_parts(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $category = TourCategory::query()->create([
            'name' => 'Tour gia đình',
            'slug' => 'tour-gia-dinh',
            'status' => 'published',
            'excerpt' => 'Chủ đề tour dành cho nhóm gia đình.',
            'is_featured' => true,
        ]);

        $topicRail = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOPIC_RAIL);
        $topicRail['eyebrow'] = 'Khởi đầu từ nhu cầu';
        $topicRail['title'] = 'Chọn nhanh chủ đề tour';
        $topicRail['description'] = '';
        $topicRail['show_navigation'] = false;
        $topicRail['limit'] = 6;
        unset($topicRail['show_card_titles']);

        $page = LandingPage::query()->create([
            'title' => 'Landing chủ đề tour',
            'slug' => 'landing-chu-de-tour',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$topicRail],
        ]);

        $this->get('/landing-chu-de-tour')
            ->assertOk()
            ->assertSee('Khởi đầu từ nhu cầu')
            ->assertSee('Chọn nhanh chủ đề tour')
            ->assertSee('Tour gia đình')
            ->assertSee('<h3 class="line-clamp-2', false)
            ->assertSee('"@id":"'.url('/landing-chu-de-tour').'#topic-rail-1"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@id":"'.route('tour-categories.show', $category).'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertDontSee('Lướt nhanh các chủ đề tour đang có hành trình hoạt động để khoanh vùng nhu cầu phù hợp trước khi so sánh điểm đến, ngày đi và mức giá.')
            ->assertDontSee('data-card-carousel-prev', false)
            ->assertDontSee('data-card-carousel-next', false);

        $topicRail['show_card_titles'] = false;
        $page->update(['blocks' => [$topicRail]]);

        $this->get('/landing-chu-de-tour')
            ->assertOk()
            ->assertSee('aria-label="Xem chủ đề tour: Tour gia đình"', false)
            ->assertSee('data-topic-card-hover-title', false)
            ->assertSee('group-hover:translate-y-0', false)
            ->assertDontSee('<h3 class="line-clamp-2', false);
    }

    public function test_custom_root_landing_page_region_taxonomy_tabs_outputs_taxonomy_item_list_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Trung',
            'slug' => 'mien-trung',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Hub vùng miền cho các hành trình miền Trung.',
            'is_featured' => true,
        ]);

        $destination = Destination::query()->create([
            'name' => 'Đà Nẵng',
            'slug' => 'da-nang',
            'status' => 'published',
            'excerpt' => 'Điểm đến biển và city break nổi bật.',
            'region_id' => $region->id,
        ]);

        Tour::query()->create([
            'title' => 'Tour Đà Nẵng 3 ngày 2 đêm',
            'slug' => 'tour-da-nang-3-ngay-2-dem',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);

        $regionTabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS);
        $regionTabs['featured'] = true;
        $regionTabs['scope'] = TourScope::Domestic->value;
        $regionTabs['card_source_type'] = 'destination';
        $regionTabs['tab_limit'] = 4;
        $regionTabs['limit'] = 4;

        LandingPage::query()->create([
            'title' => 'Landing tab vùng miền',
            'slug' => 'landing-tab-vung-mien',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$regionTabs],
        ]);

        $this->get('/landing-tab-vung-mien')
            ->assertOk()
            ->assertSee('Miền Trung')
            ->assertSee('Đà Nẵng')
            ->assertSee('"@id":"'.url('/landing-tab-vung-mien').'#region-taxonomy-tab-1-1"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@id":"'.route('destinations.show', $destination).'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false);
    }

    public function test_custom_root_landing_page_with_tour_prefix_still_renders_when_no_destination_matches(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        LandingPage::query()->create([
            'title' => 'Landing tour mùa hè',
            'slug' => 'tour-mua-he-rieng',
            'is_active' => true,
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_HTML,
            'body' => '<p>Nội dung landing tour mùa hè.</p>',
            'blocks' => [],
        ]);

        $this->get('/tour-mua-he-rieng')
            ->assertOk()
            ->assertSeeText('Landing tour mùa hè')
            ->assertSeeText('Nội dung landing tour mùa hè.');
    }

    public function test_custom_root_landing_page_tour_list_outputs_product_tourist_trip_offer_aggregate_rating_and_faq_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Tây',
            'slug' => 'mien-tay',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Nhóm tour miền Tây.',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Cần Thơ',
            'slug' => 'can-tho',
            'status' => 'published',
            'excerpt' => 'Điểm đến sông nước nổi bật.',
            'region_id' => $region->id,
        ]);
        $category = TourCategory::query()->create([
            'name' => 'Tour lễ hội',
            'slug' => 'tour-le-hoi',
            'status' => 'published',
            'excerpt' => 'Nhóm tour thiên về lễ hội và trải nghiệm văn hóa.',
            'is_featured' => true,
        ]);
        $tour = Tour::query()->create([
            'title' => 'Miền Tây lễ hội bánh dân gian',
            'slug' => 'mien-tay-le-hoi-banh-dan-gian',
            'excerpt' => 'Khám phá Mỹ Tho, Bến Tre, Cần Thơ và không khí lễ hội bánh dân gian.',
            'content' => '<p>Nội dung hành trình miền Tây.</p>',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
            'cover_image_url' => 'https://example.com/mien-tay-tour.jpg',
            'transport' => 'Xe du lịch',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 3,
            'duration_nights' => 2,
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 2590000,
            'rating_average' => 4.8,
            'rating_count' => 132,
        ]);
        TourDeparture::query()->create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(14)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Xe du lịch',
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 2590000,
            'available_slots' => 9,
            'status' => 'scheduled',
        ]);
        Tour::query()->create([
            'title' => 'Miền Tây tour nháp không được hiển thị',
            'slug' => 'mien-tay-tour-nhap-khong-duoc-hien-thi',
            'excerpt' => 'Tour cùng taxonomy nhưng chưa publish.',
            'content' => '<p>Nội dung tour nháp.</p>',
            'status' => 'draft',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
            'cover_image_url' => 'https://example.com/mien-tay-draft-tour.jpg',
        ]);

        $tourList = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_LIST);
        $faq = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FAQ);
        $tourList['title'] = 'Tour lễ hội nổi bật';
        $tourList['description'] = 'Chọn nhanh các tour lễ hội đang mở bán.';
        $tourList['card_cta_variant'] = 'red';
        $tourList['scope'] = TourScope::Domestic->value;
        $tourList['category_slug'] = $category->slug;
        $tourList['limit'] = 4;
        $faq['items'] = [[
            'question' => 'Landing tour list có schema gì?',
            'answer' => 'Landing tour list sẽ tạo graph cho tour đang hiển thị và khối FAQ nhìn thấy trên trang.',
        ]];

        $landing = LandingPage::query()->create([
            'title' => 'Landing tour lễ hội',
            'slug' => 'landing-tour-le-hoi',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$tourList, $faq],
        ]);

        $response = $this->get('/landing-tour-le-hoi');

        $response
            ->assertOk()
            ->assertSee('Tour lễ hội nổi bật')
            ->assertSee('Miền Tây lễ hội bánh dân gian')
            ->assertDontSee('Miền Tây tour nháp không được hiển thị')
            ->assertSee('aspect-[4/3]', false)
            ->assertSee('-mt-[22.5%]', false)
            ->assertSee('data-tour-card-info-panel', false)
            ->assertSee('data-tour-card-category-overlay', false)
            ->assertSee('bg-slate-950/80', false)
            ->assertSee('data-tour-card-rating-overlay', false)
            ->assertSee('data-tour-card-location-short>HCM</span>', false)
            ->assertSee('title="Điểm khởi hành: TP. Hồ Chí Minh"', false)
            ->assertDontSee('frontsite-media-content absolute', false)
            ->assertSee('aria-label="Tiêu chuẩn 4 sao"', false)
            ->assertSee('data-tour-card-standard-stars="4"', false)
            ->assertSee('data-tour-card-meta-list', false)
            ->assertSee('data-tour-card-departure-rail', false)
            ->assertSee('data-tour-card-departure-prev', false)
            ->assertSee('data-tour-card-departure-next', false)
            ->assertSee('width="1200"', false)
            ->assertSee('height="900"', false)
            ->assertSee('data-tour-card-cta-variant="red"', false)
            ->assertSee('frontsite-tour-card-cta', false)
            ->assertSee('data-tour-card-price-action', false)
            ->assertSee('grid-cols-[minmax(0,1fr)_auto]', false)
            ->assertSee('h-[58px]', false)
            ->assertSee('items-start', false)
            ->assertSee('justify-between', false)
            ->assertSee('relative -top-3.5', false)
            ->assertSee('sm:h-auto sm:items-end sm:justify-normal', false)
            ->assertSee('sm:relative sm:-left-1.5 sm:-top-1.5', false)
            ->assertSee('-mb-[1.375rem]', false)
            ->assertSee('text-[clamp(1rem,4.8vw,1.15rem)]', false)
            ->assertSeeText('Đặt ngay')
            ->assertDontSee('data-tour-card-slider="true"', false)
            ->assertSee('4,8/5')
            ->assertSee('132 đánh giá')
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"TouristTrip"', false)
            ->assertSee('"@type":"Offer"', false)
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#tour"', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#trip"', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#offer-', false);

        $this->assertMatchesRegularExpression(
            '/data-tour-card-rating-overlay[^>]*>.*?<\/span>\s*<\/span>\s*<div[^>]*data-tour-card-info-panel/s',
            $response->getContent(),
        );

        $tourList['is_slider'] = true;
        $landing->update(['blocks' => [$tourList, $faq]]);

        $this->get('/landing-tour-le-hoi')
            ->assertOk()
            ->assertSee('data-tour-card-slider="true"', false)
            ->assertSee('data-desktop-slider="true"', false)
            ->assertSee('data-card-carousel-track', false)
            ->assertSee('data-card-carousel-item', false)
            ->assertDontSee('data-mobile-two-rows="true"', false);
    }

    public function test_custom_root_landing_page_can_render_region_taxonomy_tabs_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $northRegion = Region::query()->create([
            'name' => 'Miền Bắc',
            'slug' => 'mien-bac',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Khám phá nhanh các điểm đến phía Bắc.',
            'is_featured' => true,
        ]);
        $centralRegion = Region::query()->create([
            'name' => 'Miền Trung',
            'slug' => 'mien-trung',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Khám phá nhanh các điểm đến miền Trung.',
        ]);
        $hanoi = Destination::query()->create([
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'status' => 'published',
            'excerpt' => 'Điểm đến city break và hành trình ngắn ngày.',
            'region_id' => $northRegion->id,
        ]);
        $danang = Destination::query()->create([
            'name' => 'Đà Nẵng',
            'slug' => 'da-nang',
            'status' => 'published',
            'excerpt' => 'Điểm đến biển và nghỉ dưỡng nổi bật.',
            'region_id' => $centralRegion->id,
        ]);
        $category = TourCategory::query()->create([
            'name' => 'Tour nghỉ dưỡng',
            'slug' => 'tour-nghi-duong',
            'status' => 'published',
        ]);

        Tour::query()->create([
            'title' => 'Tour Hà Nội cuối tuần',
            'slug' => 'tour-ha-noi-cuoi-tuan',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $hanoi->id,
            'region_id' => $northRegion->id,
        ]);
        Tour::query()->create([
            'title' => 'Tour Đà Nẵng nghỉ dưỡng',
            'slug' => 'tour-da-nang-nghi-duong',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $danang->id,
            'region_id' => $centralRegion->id,
        ]);

        $regionTaxonomyTabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS);
        $regionTaxonomyTabs['title'] = 'Khám phá vùng miền theo điểm đến';
        $regionTaxonomyTabs['description'] = 'Chọn tab vùng miền để xem nhanh các điểm đến đang có tour hoạt động.';
        $regionTaxonomyTabs['cta_label'] = 'Xem hub vùng miền';
        $regionTaxonomyTabs['card_cta_label'] = 'Xem hub điểm đến';
        $regionTaxonomyTabs['scope'] = TourScope::Domestic->value;
        $regionTaxonomyTabs['card_source_type'] = 'destination';
        $regionTaxonomyTabs['tab_limit'] = 6;
        $regionTaxonomyTabs['limit'] = 4;

        LandingPage::query()->create([
            'title' => 'Landing vùng miền theo điểm đến',
            'slug' => 'landing-vung-mien-theo-diem-den',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$regionTaxonomyTabs],
        ]);

        $this->get('/landing-vung-mien-theo-diem-den')
            ->assertOk()
            ->assertSee('Khám phá vùng miền theo điểm đến')
            ->assertSee('Miền Bắc')
            ->assertSee('Miền Trung')
            ->assertSee('Hà Nội')
            ->assertSee('Đà Nẵng')
            ->assertSee('Xem hub vùng miền')
            ->assertSee('data-tour-list-tabs', false)
            ->assertSee('grid grid-cols-3 gap-2', false)
            ->assertDontSee('data-desktop-slider="true"', false);
    }

    public function test_region_taxonomy_tabs_destination_cards_include_country_roots_with_child_destination_tours(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Châu Á landing',
            'slug' => 'chau-a-landing',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'excerpt' => 'Nhóm quốc gia và điểm đến nổi bật tại châu Á.',
        ]);
        $country = Destination::query()->create([
            'name' => 'Hàn Quốc landing',
            'slug' => 'du-lich-han-quoc-landing',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'is_country_root' => true,
            'excerpt' => 'Quốc gia root cần hiển thị như một điểm đến trong widget vùng miền.',
        ]);
        $country
            ->addMedia(UploadedFile::fake()->image('han-quoc-widget.jpg', 1600, 900))
            ->usingFileName('han-quoc-widget.jpg')
            ->toMediaCollection('avatar', 'public');
        $countryMediumUrl = FrontsiteMedia::taxonomyAvatarUrl(
            $country,
            FrontsiteMedia::SIZE_MEDIUM,
            'cover_image_url',
            false,
        );
        $countrySmallUrl = FrontsiteMedia::taxonomyAvatarUrl(
            $country,
            FrontsiteMedia::SIZE_SMALL,
            'cover_image_url',
            false,
        );

        $destination = Destination::query()->create([
            'name' => 'Seoul landing',
            'slug' => 'seoul-landing',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'country_id' => $country->id,
            'region_id' => $region->id,
            'excerpt' => 'Điểm đến con thuộc Hàn Quốc.',
        ]);
        $category = TourCategory::query()->create([
            'name' => 'Tour Đông Bắc Á landing',
            'slug' => 'tour-dong-bac-a-landing',
            'status' => 'published',
        ]);

        Tour::query()->create([
            'title' => 'Tour Seoul mùa thu landing',
            'slug' => 'tour-seoul-mua-thu-landing',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'tour_category_id' => $category->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);

        $regionTaxonomyTabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS);
        $regionTaxonomyTabs['title'] = 'Tour theo vùng miền quốc tế';
        $regionTaxonomyTabs['description'] = 'Quốc gia root cần xuất hiện cùng nhóm điểm đến trong từng vùng.';
        $regionTaxonomyTabs['scope'] = TourScope::International->value;
        $regionTaxonomyTabs['card_source_type'] = 'destination';
        $regionTaxonomyTabs['tab_limit'] = 4;
        $regionTaxonomyTabs['limit'] = 4;

        LandingPage::query()->create([
            'title' => 'Landing vùng miền quốc gia',
            'slug' => 'landing-vung-mien-quoc-gia',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$regionTaxonomyTabs],
        ]);

        $this->get('/landing-vung-mien-quoc-gia')
            ->assertOk()
            ->assertSee('Tour theo vùng miền quốc tế')
            ->assertSee('Châu Á landing')
            ->assertSee('Hàn Quốc landing')
            ->assertSee('Seoul landing')
            ->assertSee(route('destinations.show', $country), false)
            ->assertSee((string) $countryMediumUrl, false)
            ->assertDontSee((string) $countrySmallUrl, false)
            ->assertSee('"@id":"'.route('destinations.show', $country).'#webpage"', false);
    }

    public function test_homepage_can_render_region_taxonomy_tabs_block_fallback(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Bắc',
            'slug' => 'mien-bac',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Khám phá các điểm đến nổi bật ở miền Bắc.',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'status' => 'published',
            'excerpt' => 'Điểm đến city break nổi bật ở phía Bắc.',
            'region_id' => $region->id,
        ]);
        $category = TourCategory::query()->create([
            'name' => 'Tour ngắn ngày',
            'slug' => 'tour-ngan-ngay',
            'status' => 'published',
        ]);

        Tour::query()->create([
            'title' => 'Tour Hà Nội khám phá phố cổ',
            'slug' => 'tour-ha-noi-kham-pha-pho-co',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Khám phá vùng miền theo điểm đến hoặc chủ đề')
            ->assertSee('Miền Bắc')
            ->assertSee('Hà Nội')
            ->assertSee('home-region-taxonomy-tabs', false)
            ->assertSee('grid grid-cols-3 gap-2', false);
    }

    public function test_homepage_skips_disabled_fixed_home_config_blocks(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $landing = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $blocks = collect(LandingPageBlocks::normalize($landing->blocks ?: LandingPageBlocks::presetBlocks('home')))
            ->map(function (array $block): array {
                if (in_array($block['type'] ?? null, [
                    LandingPageBlocks::TYPE_CTA,
                    LandingPageBlocks::TYPE_FAQ,
                ], true)) {
                    $block['is_enabled'] = false;
                }

                return $block;
            })
            ->all();

        $landing->update([
            'blocks' => $blocks,
            'home_config' => TravelHomePageConfig::prepare([
                'search' => ['is_enabled' => false],
                'topic_rail' => ['is_enabled' => false],
                'featured_tours' => ['is_enabled' => false],
                'destination_slider' => ['is_enabled' => false],
                'services' => ['is_enabled' => false],
                'trust' => ['is_enabled' => false],
                'process' => ['is_enabled' => false],
                'blog_preview' => ['is_enabled' => false],
            ]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-sitewide-tour-search-overlay', false)
            ->assertDontSee('id="home-tour-topics"', false)
            ->assertDontSee('id="featured-tours"', false)
            ->assertDontSee('id="core-services"', false)
            ->assertDontSee('id="trust-and-proof"', false)
            ->assertDontSee('id="booking-process"', false)
            ->assertDontSee('id="blog-preview"', false)
            ->assertDontSee('id="home-faq"', false)
            ->assertDontSee('Cần tư vấn tour phù hợp với ngân sách, thời gian và quy mô đoàn?');
    }

    public function test_homepage_fixed_sections_follow_configured_section_order_and_visibility(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'home_config' => TravelHomePageConfig::prepare([
                    'section_order' => [
                        'services',
                        'featured_tours',
                        'search',
                        'topic_rail',
                        'destination_slider',
                        'trust',
                        'process',
                        'blog_preview',
                        'faq',
                        'cta',
                    ],
                    'cta' => ['is_enabled' => false],
                    'featured_tours' => ['is_slider' => true],
                ]),
            ]);

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('id="core-services"', false)
            ->assertSee('id="featured-tours"', false)
            ->assertSee('data-tour-card-slider="true"', false)
            ->assertSee('data-sitewide-tour-search-overlay', false)
            ->assertDontSee('Cần tư vấn tour phù hợp với ngân sách, thời gian và quy mô đoàn?')
            ->getContent();

        $servicesPosition = strpos($html, 'id="core-services"');
        $featuredToursPosition = strpos($html, 'id="featured-tours"');

        $this->assertNotFalse($servicesPosition);
        $this->assertNotFalse($featuredToursPosition);
        $this->assertLessThan($featuredToursPosition, $servicesPosition);
        $this->assertSame(1, substr_count($html, 'data-sitewide-tour-search-overlay'));
    }

    public function test_homepage_featured_tour_tabs_load_all_before_scope_filters(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('id="featured-tours"', false)
            ->assertSee('id="home-featured-tab-all"', false)
            ->assertSee('id="home-featured-tab-international"', false)
            ->assertSee('id="home-featured-tab-domestic"', false)
            ->assertSee('data-home-featured-tab="all"', false)
            ->assertSee('aria-controls="home-featured-panel-all"', false)
            ->assertSee('grid gap-x-2 gap-y-1 md:grid-cols-2 lg:grid-cols-4', false)
            ->assertDontSee('rounded-full bg-black/8 px-2.5 py-1 text-[11px]', false)
            ->assertDontSee('data-home-featured-popular-searches', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'id="home-featured-tab-international"'),
            strpos($html, 'id="home-featured-tab-all"'),
        );
        $this->assertLessThan(
            strpos($html, 'id="home-featured-tab-domestic"'),
            strpos($html, 'id="home-featured-tab-international"'),
        );
        $this->assertMatchesRegularExpression(
            '/id="home-featured-tab-all"[^>]*aria-selected="true"/s',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/id="home-featured-panel-all"[^>]*data-home-featured-panel="all"(?![^>]*\shidden(?:\s|>))[^>]*>/s',
            $html,
        );
    }

    public function test_homepage_featured_tours_without_filter_buttons_show_all_panel(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $home = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $config = $home->home_config;
        data_set($config, 'featured_tours.show_filters', false);
        $home->update(['home_config' => $config]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-home-featured-panel="all"', false)
            ->assertDontSee('data-home-featured-tab="all"', false)
            ->assertDontSee('data-home-featured-panel="domestic"', false)
            ->assertDontSee('data-home-featured-panel="international"', false);
    }

    public function test_homepage_featured_tour_filters_support_destination_topic_region_and_custom_popular_urls(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $home = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $region = Region::query()->create([
            'name' => 'Miền Nam',
            'slug' => 'mien-nam-featured-filter',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Phú Quốc',
            'slug' => 'phu-quoc-featured-filter',
            'status' => 'published',
            'region_id' => $region->id,
        ]);
        $topic = TourCategory::query()->create([
            'name' => 'Nghỉ dưỡng biển',
            'slug' => 'nghi-duong-bien-featured-filter',
            'status' => 'published',
        ]);
        Tour::query()->create([
            'title' => 'Tour Phú Quốc nghỉ dưỡng biển',
            'slug' => 'tour-phu-quoc-nghi-duong-bien-featured-filter',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'is_featured' => true,
            'tour_category_id' => $topic->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);
        $homeConfig = is_array($home->home_config) ? $home->home_config : [];
        $homeConfig['featured_tours'] = array_merge(
            is_array($homeConfig['featured_tours'] ?? null) ? $homeConfig['featured_tours'] : [],
            [
                'filters' => [
                    TravelHomePageConfig::featuredTourFilter(
                        TravelHomePageConfig::FEATURED_TOUR_FILTER_DESTINATION,
                        $destination->slug,
                        'Điểm đến '.$destination->name,
                        'Tour hot tại '.$destination->name,
                        'Danh sách tour được phân loại theo điểm đến.',
                        'destination-featured',
                    ),
                    TravelHomePageConfig::featuredTourFilter(
                        TravelHomePageConfig::FEATURED_TOUR_FILTER_TOPIC,
                        $topic->slug,
                        'Chủ đề '.$topic->name,
                        'Tour theo chủ đề '.$topic->name,
                        'Danh sách tour được phân loại theo chủ đề.',
                        'topic-featured',
                    ),
                    TravelHomePageConfig::featuredTourFilter(
                        TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION,
                        $region->slug,
                        'Nhãn tùy chỉnh không được dùng',
                        'Tiêu đề tùy chỉnh không được dùng',
                        'Mô tả tùy chỉnh không được dùng.',
                        'region-featured',
                    ),
                ],
                'popular_searches' => [
                    TravelHomePageConfig::featuredTourPopularSearch(
                        'Tìm '.$destination->name,
                        '/tim-tour?destination='.$destination->slug,
                        'popular-internal',
                        'destination-featured',
                    ),
                    TravelHomePageConfig::featuredTourPopularSearch(
                        '<script>alert(1)</script>',
                        'https://example.com/tour-hot',
                        'popular-external',
                    ),
                ],
            ],
        );
        $home->update(['home_config' => $homeConfig]);

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee('id="home-featured-tab-all"', false)
            ->assertSee('id="home-featured-tab-destination-featured"', false)
            ->assertSee('id="home-featured-tab-topic-featured"', false)
            ->assertSee('id="home-featured-tab-region-featured"', false)
            ->assertSee('data-home-featured-tab-url="'.route('destinations.show', $destination).'"', false)
            ->assertSee('data-home-featured-tab-url="'.route('tour-categories.show', $topic).'"', false)
            ->assertSee('data-home-featured-tab-url="'.route('regions.show', $region).'"', false)
            ->assertSee('data-home-featured-tab="region-featured"', false)
            ->assertSee('data-home-featured-tab-title="Tour hot trong tháng"', false)
            ->assertSee('data-home-featured-tab-description="Tổng hợp các tour trọn gói và tour du lịch đoàn đang được quan tâm để bạn dễ so sánh hành trình, lịch đi và mức giá."', false)
            ->assertSeeText($region->name)
            ->assertDontSee('Nhãn tùy chỉnh không được dùng')
            ->assertDontSee('Tiêu đề tùy chỉnh không được dùng')
            ->assertDontSee('Mô tả tùy chỉnh không được dùng.')
            ->assertSee('data-home-featured-popular-searches', false)
            ->assertSee('data-popular-search-rail-track', false)
            ->assertSee('data-popular-search-rail-prev', false)
            ->assertSee('data-popular-search-rail-next', false)
            ->assertSee('data-home-featured-popular-search-filter="destination-featured"', false)
            ->assertSee('data-home-featured-popular-search-filter="region-featured"', false)
            ->assertSee('data-home-featured-popular-search-filter=""', false)
            ->assertSee('href="/tim-tour?destination='.$destination->slug.'"', false)
            ->assertSee('href="'.route('tours.search', ['destination' => $destination->slug]).'"', false)
            ->assertSee('href="https://example.com/tour-hot"', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->assertLessThan(
            strpos($html, 'id="home-featured-tab-destination-featured"'),
            strpos($html, 'id="home-featured-tab-all"'),
        );
        $this->assertLessThan(
            strpos($html, 'data-home-featured-popular-searches'),
            strpos($html, 'id="home-featured-panel-all"'),
        );
    }

    public function test_custom_root_landing_page_can_render_tour_taxonomy_tabs_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Bắc',
            'slug' => 'mien-bac',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'excerpt' => 'Nhóm tour theo vùng miền phía Bắc.',
            'is_featured' => true,
        ]);
        $destination = Destination::query()->create([
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'status' => 'published',
            'excerpt' => 'Điểm đến trung tâm với các hành trình city tour và ngoại thành.',
            'region_id' => $region->id,
        ]);
        $category = TourCategory::query()->create([
            'name' => 'Tour gia đình',
            'slug' => 'tour-gia-dinh',
            'status' => 'published',
            'excerpt' => 'Chủ đề tour phù hợp cho nhóm gia đình.',
            'is_featured' => true,
        ]);
        Tour::query()->create([
            'title' => 'Tour Hà Nội cuối tuần',
            'slug' => 'tour-ha-noi-cuoi-tuan',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
            'cover_image_url' => 'https://example.com/tour-ha-noi-cuoi-tuan.jpg',
            'transport' => 'Máy bay',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 3,
            'duration_nights' => 2,
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 5990000,
            'rating_average' => 4.7,
            'rating_count' => 88,
        ]);
        $tour = Tour::query()->where('slug', 'tour-ha-noi-cuoi-tuan')->firstOrFail();
        TourDeparture::query()->create([
            'tour_id' => $tour->id,
            'departure_date' => now()->addDays(10)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 5990000,
            'available_slots' => 6,
            'status' => 'scheduled',
        ]);

        $tourTaxonomyTabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $tourTaxonomyTabs['cta_label'] = 'Xem trang nhóm tour';
        $tourTaxonomyTabs['card_cta_variant'] = 'blue';
        $tourTaxonomyTabs['scope'] = TourScope::Domestic->value;
        $tourTaxonomyTabs['tabs'] = [
            LandingPageBlocks::defaultTourTaxonomyTab('region', $region->slug, 'Miền Bắc', 'Tour theo Miền Bắc', 'Nhóm tour nổi bật ở khu vực phía Bắc.'),
            LandingPageBlocks::defaultTourTaxonomyTab('destination', $destination->slug, 'Hà Nội', ''),
            LandingPageBlocks::defaultTourTaxonomyTab('tour_category', $category->slug, 'Gia đình', 'Tour cho gia đình'),
        ];

        $landing = LandingPage::query()->create([
            'title' => 'Landing tab tour taxonomy',
            'slug' => 'landing-tab-tour-taxonomy',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$tourTaxonomyTabs],
        ]);

        $this->get('/landing-tab-tour-taxonomy')
            ->assertOk()
            ->assertSee('Tour theo Miền Bắc')
            ->assertSee('Miền Bắc')
            ->assertSee('Hà Nội')
            ->assertSee('Gia đình')
            ->assertSee('Tour Hà Nội cuối tuần')
            ->assertSee('data-tour-card-cta-variant="blue"', false)
            ->assertSee('4,7/5')
            ->assertSee('88 đánh giá')
            ->assertSee('data-tour-list-tabs', false)
            ->assertDontSee('data-tour-card-slider="true"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@id":"'.url('/landing-tab-tour-taxonomy').'#tour-taxonomy-tab-1-2"', false)
            ->assertSee('"name":"Hà Nội"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"TouristTrip"', false)
            ->assertSee('"@type":"Offer"', false)
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#trip"', false);

        $tourTaxonomyTabs['is_slider'] = true;
        $landing->update(['blocks' => [$tourTaxonomyTabs]]);

        $this->get('/landing-tab-tour-taxonomy')
            ->assertOk()
            ->assertSee('data-tour-card-slider="true"', false)
            ->assertSee('data-desktop-slider="true"', false)
            ->assertSee('data-card-carousel-item', false)
            ->assertDontSee('data-mobile-two-rows="true"', false);
    }

    public function test_group_tour_taxonomy_tabs_keep_all_panels_and_popular_links_in_group_scope(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $region = Region::query()->create([
            'name' => 'Miền Tây tour đoàn',
            'slug' => 'mien-tay-tour-doan',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Cà Mau',
            'slug' => 'ca-mau-tour-doan',
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $autoDestination = Destination::query()->create([
            'name' => 'Sóc Trăng',
            'slug' => 'soc-trang-tour-doan',
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $domesticOnlyDestination = Destination::query()->create([
            'name' => 'Bạc Liêu',
            'slug' => 'bac-lieu-tour-le',
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $groupTour = Tour::query()->create([
            'title' => 'Tour đoàn Cà Mau kiểm thử',
            'slug' => 'tour-doan-ca-mau-kiem-thu',
            'status' => 'published',
            'scope' => TourScope::Group->value,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);
        $groupTour->syncTaxonomyLinks([], [], [$region->id]);
        Tour::query()->create([
            'title' => 'Tour đoàn Sóc Trăng kiểm thử',
            'slug' => 'tour-doan-soc-trang-kiem-thu',
            'status' => 'published',
            'scope' => TourScope::Group->value,
            'destination_id' => $autoDestination->id,
            'region_id' => $region->id,
        ]);
        $domesticTour = Tour::query()->create([
            'title' => 'Tour lẻ Cà Mau kiểm thử',
            'slug' => 'tour-le-ca-mau-kiem-thu',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'destination_id' => $domesticOnlyDestination->id,
            'region_id' => $region->id,
        ]);
        $domesticTour->syncTaxonomyLinks([], [], [$region->id]);

        $block = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $block['title'] = 'Tour đoàn nổi bật';
        $block['scope'] = TourScope::Group->value;
        $block['show_all_tab'] = true;
        $block['tabs'] = [LandingPageBlocks::defaultTourTaxonomyTab('region', $region->slug, 'Miền Tây Nam Bộ')];
        $block['popular_searches'] = [
            TravelHomePageConfig::featuredTourPopularSearch('Cà Mau', '/tim-tour?scope=group&destination=ca-mau', null, $block['tabs'][0]['uuid']),
            TravelHomePageConfig::featuredTourPopularSearch('Liên kết lỗi', 'javascript:alert(1)'),
        ];

        $landing = LandingPage::query()->create([
            'title' => 'Landing tour đoàn kiểm thử',
            'slug' => 'landing-tour-doan-kiem-thu',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$block],
        ]);

        $this->get('/landing-tour-doan-kiem-thu')
            ->assertOk()
            ->assertSee('data-tour-list-tab="all"', false)
            ->assertSee('data-tour-list-tab="'.$block['tabs'][0]['uuid'].'"', false)
            ->assertSee('Tour đoàn Cà Mau kiểm thử')
            ->assertDontSee('Tour lẻ Cà Mau kiểm thử')
            ->assertSee('data-tour-list-popular-search-filter="'.$block['tabs'][0]['uuid'].'"', false)
            ->assertSee('data-popular-search-rail-track', false)
            ->assertSee('data-popular-search-rail-prev', false)
            ->assertSee('data-popular-search-rail-next', false)
            ->assertSee('href="'.e(route('tours.search', ['destination' => $autoDestination->slug, 'scope' => 'group'])).'"', false)
            ->assertSee('/tim-tour?scope=group&amp;destination=ca-mau', false)
            ->assertDontSee('destination='.$destination->slug, false)
            ->assertDontSee('destination='.$domesticOnlyDestination->slug, false)
            ->assertDontSee('javascript:alert(1)', false);

        $block['show_all_tab'] = false;
        $block['show_filters'] = false;
        $landing->update(['blocks' => [$block]]);

        $this->get('/landing-tour-doan-kiem-thu')
            ->assertOk()
            ->assertSee('data-tour-list-panel="all"', false)
            ->assertSee('Tour đoàn Cà Mau kiểm thử')
            ->assertSee('Tour đoàn Sóc Trăng kiểm thử')
            ->assertDontSee('Tour lẻ Cà Mau kiểm thử')
            ->assertDontSee('data-tour-list-tab=', false)
            ->assertDontSee('data-tour-list-panel="'.$block['tabs'][0]['uuid'].'"', false);
    }

    public function test_custom_root_landing_page_can_render_manual_html_mode(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        LandingPage::query()->create([
            'title' => 'Landing HTML',
            'slug' => 'landing-html',
            'is_active' => true,
            'template_key' => 'blank',
            'editor_mode' => LandingPage::EDITOR_MODE_HTML,
            'body' => <<<'HTML'
<section data-landing-html>
    <script>window.__landingCampaign = true;</script>
    <div class="campaign-shell">Campaign HTML runtime</div>
</section>
HTML,
        ]);

        $this->get('/landing-html')
            ->assertOk()
            ->assertSee('Landing HTML')
            ->assertSee('data-landing-html', false)
            ->assertSee('window.__landingCampaign = true;', false)
            ->assertSee('Campaign HTML runtime');
    }

    public function test_custom_root_landing_page_can_render_html_widget_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['html'] = <<<'HTML'
<section data-html-widget="campaign-form">
    <div class="campaign-widget-shell">Widget HTML trong block stack</div>
</section>
HTML;

        LandingPage::query()->create([
            'title' => 'Landing HTML widget',
            'slug' => 'landing-html-widget',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$htmlWidget],
        ]);

        $this->get('/landing-html-widget')
            ->assertOk()
            ->assertSee('data-html-widget="campaign-form"', false)
            ->assertSee('Widget HTML trong block stack');
    }

    public function test_custom_root_landing_page_can_render_voucher_promotion_block_with_countdown(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $voucherBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_PROMOTION);
        $voucherBlock['voucher_campaign_slug'] = 'voucher-test-200k';
        $voucherBlock['offer_code'] = 'TEST200';

        $landing = LandingPage::query()->create([
            'title' => 'Landing voucher widget',
            'slug' => 'landing-voucher-widget',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$voucherBlock],
        ]);

        VoucherCampaign::query()->create([
            'landing_page_id' => $landing->getKey(),
            'title' => 'Voucher test 200k',
            'slug' => 'voucher-test-200k',
            'description' => 'Voucher test.',
            'code_prefix' => 'TEST200',
            'code_quantity' => 10,
            'code_set_version' => 'test-version',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->get('/landing-voucher-widget')
            ->assertOk()
            ->assertSee('TEST200')
            ->assertSee('data-voucher-countdown', false)
            ->assertSee('data-travel-inquiry-voucher-campaign="voucher-test-200k"', false)
            ->assertDontSee('data-html-widget', false);
    }

    public function test_landing_voucher_rail_only_loads_public_redeemable_campaigns_with_available_codes(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $voucherRail = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_RAIL);
        $voucherRail['title'] = 'Mã ưu đãi cho hành trình mới';
        $voucherRail['campaign_slugs'] = ['voucher-public-500k', 'voucher-public-300k', 'voucher-expired', 'voucher-private', 'voucher-without-landing'];

        LandingPage::query()->create([
            'title' => 'Landing voucher rail',
            'slug' => 'landing-voucher-rail',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$voucherRail],
        ]);

        $public500Landing = LandingPage::query()->create([
            'title' => 'Nhận voucher 500K',
            'slug' => 'nhan-voucher-public-500k',
            'is_active' => true,
            'template_key' => 'generic',
        ]);
        $public300Landing = LandingPage::query()->create([
            'title' => 'Nhận voucher 300K',
            'slug' => 'nhan-voucher-public-300k',
            'is_active' => true,
            'template_key' => 'generic',
        ]);
        $privateLanding = LandingPage::query()->create([
            'title' => 'Voucher riêng tư',
            'slug' => 'nhan-voucher-private',
            'is_active' => true,
            'template_key' => 'generic',
        ]);
        $expiredLanding = LandingPage::query()->create([
            'title' => 'Voucher hết hạn',
            'slug' => 'nhan-voucher-expired',
            'is_active' => true,
            'template_key' => 'generic',
        ]);

        $public500 = VoucherCampaign::query()->create([
            'landing_page_id' => $public500Landing->getKey(),
            'title' => 'Tặng bạn eVoucher 500K',
            'slug' => 'voucher-public-500k',
            'description' => 'Giảm 500.000đ cho đơn hàng online từ 26.000.000đ.',
            'code_prefix' => 'PRIVATE500',
            'code_quantity' => 5,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'code_valid_until' => now()->addDays(10),
            'is_active' => true,
            'meta' => [
                'public_widget_enabled' => true,
                'public_code' => 'TRIP1500',
                'public_terms' => 'Áp dụng cho tour nội địa, Đông Nam Á',
            ],
        ]);
        $public300 = VoucherCampaign::query()->create([
            'landing_page_id' => $public300Landing->getKey(),
            'title' => 'Tặng bạn eVoucher 300K',
            'slug' => 'voucher-public-300k',
            'description' => 'Giảm 300.000đ cho đơn hàng online từ 16.000.000đ.',
            'code_prefix' => 'PRIVATE300',
            'code_quantity' => 5,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(4),
            'is_active' => true,
            'meta' => [
                'public_widget_enabled' => true,
                'public_code' => 'TRIP1300',
            ],
        ]);
        $private = VoucherCampaign::query()->create([
            'landing_page_id' => $privateLanding->getKey(),
            'title' => 'Voucher không công khai',
            'slug' => 'voucher-private',
            'code_prefix' => 'PRIVATE',
            'code_quantity' => 5,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(4),
            'is_active' => true,
            'meta' => [
                'public_widget_enabled' => false,
                'public_code' => 'KHONGHIEN',
            ],
        ]);
        $expired = VoucherCampaign::query()->create([
            'landing_page_id' => $expiredLanding->getKey(),
            'title' => 'Voucher đã hết hạn',
            'slug' => 'voucher-expired',
            'code_prefix' => 'EXPIRED',
            'code_quantity' => 5,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'is_active' => true,
            'meta' => [
                'public_widget_enabled' => true,
                'public_code' => 'HETHAN',
            ],
        ]);
        $withoutLanding = VoucherCampaign::query()->create([
            'title' => 'Voucher chưa có landing',
            'slug' => 'voucher-without-landing',
            'code_prefix' => 'ORPHAN',
            'code_quantity' => 5,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(4),
            'is_active' => true,
            'meta' => [
                'public_widget_enabled' => true,
                'public_code' => 'KHONGLANDING',
            ],
        ]);

        foreach ([$public500, $public300, $private, $expired, $withoutLanding] as $campaign) {
            VoucherCode::query()->create([
                'voucher_campaign_id' => $campaign->id,
                'code' => $campaign->code_prefix.'-INTERNAL',
                'code_set_version' => $campaign->code_set_version,
                'status' => VoucherCode::STATUS_AVAILABLE,
            ]);
        }

        $response = $this->get('/landing-voucher-rail');

        $response
            ->assertOk()
            ->assertSee('Mã ưu đãi cho hành trình mới')
            ->assertSee('TRIP1500')
            ->assertSee('TRIP1300')
            ->assertSee('Nhận voucher')
            ->assertSee('href="'.route('landing.show', ['slug' => $public500Landing->slug]).'#nhan-voucher"', false)
            ->assertSee('href="'.route('landing.show', ['slug' => $public300Landing->slug]).'#nhan-voucher"', false)
            ->assertSee('data-voucher-claim-link', false)
            ->assertDontSee('data-voucher-code-copy', false)
            ->assertSee('data-card-carousel', false)
            ->assertSee('data-desktop-slider="true"', false)
            ->assertSee('--mobile-card-width: 80%', false)
            ->assertSee('--desktop-card-width: calc((100% - 2rem) / 3)', false)
            ->assertSee('voucher-premium-plane.png', false)
            ->assertSee('data-voucher-card-layout="split"', false)
            ->assertSee('data-voucher-offer-value="500K"', false)
            ->assertDontSee('KHONGHIEN')
            ->assertDontSee('HETHAN')
            ->assertDontSee('KHONGLANDING')
            ->assertDontSee('PRIVATE500-INTERNAL');
    }

    public function test_custom_root_landing_page_can_render_premium_voucher_promotion_variant(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $voucherBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_PROMOTION);
        $voucherBlock['voucher_campaign_slug'] = 'voucher-premium-200k';
        $voucherBlock['variant'] = LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM;
        $voucherBlock['panel_title'] = 'Nhận voucher nhanh, tư vấn tour đúng gu.';
        $voucherBlock['trust_note'] = 'Không bắt buộc đặt tour. Tư vấn viên chỉ liên hệ khi bạn để lại thông tin.';
        $receiveUntil = now()->addDay()->endOfDay();
        $validUntil = now()->addDays(10)->endOfDay();

        $landing = LandingPage::query()->create([
            'title' => 'Landing voucher premium widget',
            'slug' => 'landing-voucher-premium-widget',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$voucherBlock],
        ]);

        VoucherCampaign::query()->create([
            'landing_page_id' => $landing->getKey(),
            'title' => 'Voucher premium 200k',
            'slug' => 'voucher-premium-200k',
            'description' => 'Voucher premium.',
            'code_prefix' => 'PREMIUM200',
            'code_quantity' => 10,
            'code_set_version' => 'premium-version',
            'starts_at' => now()->subDay(),
            'ends_at' => $receiveUntil,
            'code_valid_until' => $validUntil,
            'is_active' => true,
        ]);

        $this->get('/landing-voucher-premium-widget')
            ->assertOk()
            ->assertSee('voucher-landing-section--premium', false)
            ->assertSee('data-voucher-variant="premium"', false)
            ->assertSee('Voucher đặc quyền')
            ->assertSee('Áp dụng đến hết ngày '.$validUntil->format('d/m/Y'))
            ->assertDontSee('Áp dụng đến hết ngày '.$receiveUntil->format('d/m/Y'))
            ->assertSee('data-travel-inquiry-voucher-variant="premium"', false)
            ->assertSee('Không bắt buộc đặt tour. Tư vấn viên chỉ liên hệ khi bạn để lại thông tin.');
    }

    public function test_reserved_slug_is_rejected_for_custom_landing_page(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(LandingPagesManager::class)
            ->call('createPage')
            ->set('form.title', 'Landing mới')
            ->set('form.slug', 'blog')
            ->call('save')
            ->assertHasErrors(['form.slug']);
    }
}
