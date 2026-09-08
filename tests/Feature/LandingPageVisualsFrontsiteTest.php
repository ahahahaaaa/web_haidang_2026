<?php

namespace Tests\Feature;

use App\Support\LandingPageVisuals;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Slider;
use Tests\TestCase;

class LandingPageVisualsFrontsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_can_render_slider_hero_and_media_gallery(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $slider = Slider::query()->create([
            'name' => 'About hero slider',
            'location' => 'about-hero',
            'is_active' => true,
            'autoplay_delay' => 4500,
        ]);

        $slider
            ->items()
            ->create([
                'title' => 'Hero slider cho trang About',
                'subtitle' => 'Giới thiệu doanh nghiệp',
                'description' => 'Slide hero được render từ slider đã chọn trong CMS.',
                'primary_label' => 'Liên hệ ngay',
                'primary_url' => '/lien-he',
                'effect' => 'animate__fadeInUp',
                'order' => 1,
                'is_active' => true,
            ])
            ->addMedia(UploadedFile::fake()->image('about-slider.jpg', 1600, 900))
            ->usingFileName('about-slider.jpg')
            ->toMediaCollection('image', 'public');

        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $page->update([
            'visual_config' => [
                'hero' => [
                    'enabled' => true,
                    'media_alt' => '',
                    'slider_id' => $slider->id,
                    'source' => LandingPageVisuals::SOURCE_SLIDER,
                ],
                'gallery' => [
                    'description' => 'Gallery render từ media popup.',
                    'enabled' => true,
                    'eyebrow' => 'Hình ảnh nổi bật',
                    'items' => [
                        [
                            'image_alt' => 'Ảnh gallery about',
                            'subtitle' => 'Khoảnh khắc',
                            'title' => 'Gallery about',
                            'url' => '/ve-chung-toi',
                            'uuid' => 'about-gallery-item',
                        ],
                    ],
                    'slider_id' => null,
                    'source' => LandingPageVisuals::SOURCE_MEDIA,
                    'title' => 'Bộ sưu tập about',
                ],
            ],
        ]);
        $page
            ->addMedia(UploadedFile::fake()->image('about-gallery.jpg', 1600, 900))
            ->usingFileName('about-gallery.jpg')
            ->toMediaCollection(LandingPageVisuals::galleryCollection('about-gallery-item'), 'public');

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('data-card-carousel', false)
            ->assertSee('--mobile-card-width: 100%; --tablet-card-width: 100%;', false)
            ->assertSee('service-card-carousel-item', false)
            ->assertSee('h-auto w-full transition duration-500', false)
            ->assertSee('Hero slider cho trang About')
            ->assertSee('Bộ sưu tập about')
            ->assertSee('Gallery about');
    }

    public function test_home_page_can_render_custom_landing_hero_slider(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $slider = Slider::query()->create([
            'name' => 'Home hero slider',
            'location' => 'home-hero',
            'is_active' => true,
            'autoplay_delay' => 4800,
        ]);

        $slider
            ->items()
            ->create([
                'title' => 'Hero slider riêng cho homepage',
                'subtitle' => 'Trang chủ Hải Đăng Travel',
                'description' => 'Hero này được render khi landing page home chọn source slider.',
                'primary_label' => 'Gửi yêu cầu',
                'primary_url' => '/lien-he',
                'effect' => 'animate__fadeInUp',
                'order' => 1,
                'is_active' => true,
            ]);

        $home = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $home->update([
            'visual_config' => [
                'hero' => [
                    'enabled' => true,
                    'media_alt' => '',
                    'slider_id' => $slider->id,
                    'source' => LandingPageVisuals::SOURCE_SLIDER,
                ],
                'gallery' => LandingPageVisuals::defaultConfig()['gallery'],
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Hero slider riêng cho homepage')
            ->assertSee('data-hero-slider', false);
    }

    public function test_services_blog_and_tour_listing_pages_render_with_landing_visual_support(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('services.index'))->assertOk();
        $this->get(route('blog.index'))->assertOk();
        $this->get(route('tours.domestic'))->assertOk();
    }

    public function test_slider_hero_respects_overlay_panel_toggles_and_responsive_images(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $slider = Slider::query()->create([
            'name' => 'About hero responsive slider',
            'location' => 'about-hero',
            'is_active' => true,
            'autoplay_delay' => 5200,
        ]);

        $item = $slider->items()->create([
            'title' => 'Hero responsive cho About',
            'subtitle' => 'Ảnh mobile và desktop riêng',
            'description' => 'Slide hero tắt overlay và tắt panel mini.',
            'primary_label' => 'Xem thêm',
            'primary_url' => '/ve-chung-toi',
            'effect' => 'animate__zoomInLeft',
            'show_overlay' => false,
            'show_inner_media' => false,
            'order' => 1,
            'is_active' => true,
        ]);

        $item
            ->addMedia(UploadedFile::fake()->image('about-responsive-desktop.jpg', 1600, 900))
            ->usingFileName('about-responsive-desktop.jpg')
            ->toMediaCollection('image', 'public');

        $item
            ->addMedia(UploadedFile::fake()->image('about-responsive-mobile.jpg', 900, 1400))
            ->usingFileName('about-responsive-mobile.jpg')
            ->toMediaCollection('mobile_image', 'public');

        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $page->update([
            'visual_config' => [
                'hero' => [
                    'enabled' => true,
                    'media_alt' => '',
                    'slider_id' => $slider->id,
                    'source' => LandingPageVisuals::SOURCE_SLIDER,
                ],
                'gallery' => LandingPageVisuals::defaultConfig()['gallery'],
            ],
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Hero responsive cho About')
            ->assertSee('data-hero-effect="animate__zoomInLeft"', false)
            ->assertSee('media="(max-width: 767px)"', false)
            ->assertDontSee('data-hero-overlay', false)
            ->assertDontSee('data-hero-panel', false);
    }

    public function test_home_page_can_render_trust_section_from_home_config(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $home = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $homeConfig = is_array($home->home_config) ? $home->home_config : [];
        $homeConfig['trust'] = [
            'title' => 'Vì sao khách chọn Hải Đăng Travel',
            'description' => 'Giữ phần bằng chứng ngắn để khách quét nhanh trước khi quyết định gửi yêu cầu.',
            'cards' => [
                [
                    'uuid' => 'trust-card-1',
                    'highlight' => 'Tư vấn đúng hướng',
                    'title' => 'Tư vấn đúng hành trình',
                    'text' => 'Tập trung vào ngày đi, ngân sách và trải nghiệm phù hợp thay vì ép khách theo tour có sẵn.',
                ],
                [
                    'uuid' => 'trust-card-2',
                    'highlight' => 'Dịch vụ đi cùng',
                    'title' => 'Dễ chốt dịch vụ đi kèm',
                    'text' => 'Visa, vé máy bay và nhu cầu tour đoàn được gom vào cùng một luồng xử lý rõ ràng.',
                ],
            ],
        ];

        $home->update([
            'home_config' => $homeConfig,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Vì sao khách chọn Hải Đăng Travel')
            ->assertSee('Giữ phần bằng chứng ngắn để khách quét nhanh trước khi quyết định gửi yêu cầu.')
            ->assertSee('Tư vấn đúng hướng')
            ->assertSee('Tư vấn đúng hành trình')
            ->assertSee('Dịch vụ đi cùng')
            ->assertSee('Dễ chốt dịch vụ đi kèm');
    }

    public function test_home_page_can_render_extended_home_sections_from_home_config(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $home = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $homeConfig = is_array($home->home_config) ? $home->home_config : [];
        $homeConfig['search'] = [
            'placeholder' => 'Tìm tour theo ngân sách và điểm đến',
            'button_label' => 'Bắt đầu',
        ];
        $homeConfig['featured_tours'] = [
            'cta_label' => 'Mở danh sách tour',
            'tabs' => [
                'domestic' => [
                    'label' => 'Tour nội địa',
                    'title' => 'Tour nội địa nên xem trước',
                    'description' => 'Ưu tiên các hành trình trong nước đang được khách tìm hiểu nhiều.',
                ],
                'international' => [
                    'label' => 'Tour quốc tế',
                    'title' => 'Tour quốc tế được quan tâm',
                    'description' => 'Nhóm tour nước ngoài có lịch cập nhật mới để tiện so sánh.',
                ],
                'group' => [
                    'label' => 'Tour theo đoàn',
                    'title' => 'Tour đoàn cho nhu cầu riêng',
                    'description' => 'Phù hợp với MICE, gia đình lớn và chương trình cần thiết kế riêng.',
                ],
            ],
        ];
        $homeConfig['destination_slider'] = [
            'title' => 'Điểm đến nên xem ngay',
            'description' => 'Chọn nhanh các hub điểm đến đang có tín hiệu quan tâm tốt trước khi đi sâu vào gallery.',
            'card_cta_label' => 'Khám phá điểm đến',
        ];
        $homeConfig['services'] = [
            'title' => 'Dịch vụ đi cùng hành trình',
            'description' => 'Gom các dịch vụ bổ trợ để khách xử lý visa, vé máy bay và phương tiện ngay trong cùng một luồng.',
            'cta_label' => 'Xem toàn bộ dịch vụ',
            'cta_url' => '/dich-vu',
        ];
        $homeConfig['process'] = [
            'title' => '4 bước làm việc cùng Hải Đăng',
            'description' => 'Giúp khách hình dung rõ luồng tư vấn trước khi gửi yêu cầu.',
            'cards' => [
                [
                    'uuid' => 'process-1',
                    'title' => 'Tiếp nhận lịch trình mong muốn',
                    'description' => 'Thu thông tin về ngày đi, ngân sách và số lượng khách.',
                    'image_url' => 'https://example.com/process-step-1.jpg',
                    'image_alt' => 'Tư vấn lịch trình ban đầu',
                ],
                [
                    'uuid' => 'process-2',
                    'title' => 'Đề xuất hành trình phù hợp',
                    'description' => 'Gợi ý tour và dịch vụ bổ trợ tương ứng với nhu cầu thực tế.',
                ],
            ],
        ];
        $homeConfig['blog_preview'] = [
            'title' => 'Đọc nhanh trước khi đi',
            'description' => 'Một cụm bài viết giúp khách tự tin hơn khi chốt điểm đến và chuẩn bị giấy tờ.',
            'cta_label' => 'Xem cẩm nang',
            'cta_url' => '/blog',
        ];

        $home->update([
            'home_config' => $homeConfig,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tìm tour theo ngân sách và điểm đến')
            ->assertSee('Bắt đầu')
            ->assertSee('Tour nội địa nên xem trước')
            ->assertSee('Mở danh sách tour')
            ->assertSee('Dịch vụ đi cùng hành trình')
            ->assertSee('Xem toàn bộ dịch vụ')
            ->assertSee('4 bước làm việc cùng Hải Đăng')
            ->assertSee('Tiếp nhận lịch trình mong muốn')
            ->assertSee('https://example.com/process-step-1.jpg', false)
            ->assertSee('Tư vấn lịch trình ban đầu')
            ->assertSee('Đọc nhanh trước khi đi')
            ->assertSee('Xem cẩm nang');
    }
}
