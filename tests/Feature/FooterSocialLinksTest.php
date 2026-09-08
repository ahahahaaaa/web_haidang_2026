<?php

namespace Tests\Feature;

use App\Support\FooterSocialLinks;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FooterSocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_footer_renders_brand_social_icons_from_theme_settings(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'facebook_url' => 'https://facebook.com/haidangtravel',
            'youtube_url' => 'https://youtube.com/@haidangtravel',
            'tiktok_url' => 'https://www.tiktok.com/@haidangtravel',
            'instagram_url' => 'https://www.instagram.com/haidangtravel',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://facebook.com/haidangtravel', false)
            ->assertSee('https://youtube.com/@haidangtravel', false)
            ->assertSee('https://www.tiktok.com/@haidangtravel', false)
            ->assertSee('https://www.instagram.com/haidangtravel', false)
            ->assertSee('fa-brands fa-facebook-f', false)
            ->assertSee('fa-brands fa-youtube', false)
            ->assertSee('fa-brands fa-tiktok', false)
            ->assertSee('fa-brands fa-instagram', false);
    }

    public function test_homepage_footer_renders_bct_and_dmca_badges_with_live_links(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('http://online.gov.vn/Home/WebDetails/15656', false)
            ->assertSee('https://www.dmca.com/Protection/Status.aspx?ID=0b22a7b7-ed93-4820-acbf-7bd6d1caaf34', false)
            ->assertSee('https://haidangtravel.com/images/logoSaleNoti.png', false)
            ->assertSee('https://haidangtravel.com/images/dmca_protected_sml_120m.png', false)
            ->assertSee('Đã thông báo Bộ Công Thương', false)
            ->assertSee('DMCA.com Protection Status', false);
    }

    public function test_homepage_footer_renders_zalo_and_secondary_footer_menu_with_editable_titles(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'zalo_url' => 'https://zalo.me/haidangtravel',
        ]);

        Menu::query()->where('location', 'footer')->firstOrFail()->update([
            'description' => 'Đi nhanh hơn',
        ]);

        $secondaryMenu = Menu::query()->updateOrCreate(
            ['location' => 'footer_secondary'],
            [
                'name' => 'Footer Secondary Menu',
                'description' => 'Hỗ trợ nhanh',
            ],
        );

        MenuItem::query()->updateOrCreate(
            [
                'menu_id' => $secondaryMenu->id,
                'label' => 'Chính sách',
            ],
            [
                'url' => '/chinh-sach',
                'order' => 1,
                'is_active' => true,
                'target' => '_self',
            ],
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://zalo.me/haidangtravel', false)
            ->assertSee('aria-label="Zalo hỗ trợ"', false)
            ->assertSee('Zalo hỗ trợ')
            ->assertSee('images/zalo-footer-logo.svg', false)
            ->assertSee('Đi nhanh hơn')
            ->assertSee('Hỗ trợ nhanh')
            ->assertSee('/chinh-sach', false)
            ->assertSee('Chính sách');
    }

    public function test_homepage_footer_uses_configured_social_buttons_with_labels_and_descriptions(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'structured_data' => [
                FooterSocialLinks::STRUCTURED_DATA_KEY => [
                    'is_configured' => true,
                    'items' => [
                        [
                            'platform' => 'facebook',
                            'label' => 'Facebook hỗ trợ',
                            'description' => 'Tư vấn nhanh qua fanpage',
                            'url' => 'https://facebook.com/haidang-support',
                            'is_active' => true,
                        ],
                        [
                            'platform' => 'youtube',
                            'label' => 'YouTube Channel',
                            'description' => 'Video hành trình',
                            'url' => 'https://youtube.com/@haidangtravel',
                            'is_active' => true,
                        ],
                        [
                            'platform' => 'tiktok',
                            'label' => 'TikTok tạm ẩn',
                            'url' => 'https://www.tiktok.com/@hidden',
                            'is_active' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://facebook.com/haidang-support', false)
            ->assertSee('Facebook hỗ trợ')
            ->assertSee('Tư vấn nhanh qua fanpage')
            ->assertSee('fa-brands fa-facebook-f', false)
            ->assertSee('https://youtube.com/@haidangtravel', false)
            ->assertSee('Video hành trình')
            ->assertDontSee('TikTok tạm ẩn');
    }
}
