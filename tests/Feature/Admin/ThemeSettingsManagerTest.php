<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\ThemeSettingsManager;
use App\Models\User;
use App\Services\Frontsite\FrontsiteCache;
use App\Support\FooterSocialLinks;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class ThemeSettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_settings_manager_can_store_homepage_schema_configuration(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.structured_data.organization.image_url', 'https://example.com/schema-image.jpg')
            ->set('form.structured_data.local_business.price_range', 'Từ 2.500.000đ/m²')
            ->set('form.structured_data.company.legal_name', 'Công ty TNHH Du lịch Hải Đăng')
            ->set('form.structured_data.company.business_license', '0312345678')
            ->set('form.structured_data.company.international_travel_license', '79-723/2017/TCDL-GP LHQT')
            ->set('form.structured_data.company.tax_code', '0312345678')
            ->set('form.structured_data.address.street_address', '123 Đường Mẫu')
            ->set('form.structured_data.address.address_locality', 'Thành phố Hồ Chí Minh')
            ->set('form.structured_data.address.address_region', 'Hồ Chí Minh')
            ->set('form.structured_data.address.postal_code', '700000')
            ->set('form.structured_data.address.address_country', 'VN')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('https://example.com/schema-image.jpg', data_get($settings->structured_data, 'organization.image_url'));
        $this->assertSame('Từ 2.500.000đ/m²', data_get($settings->structured_data, 'local_business.price_range'));
        $this->assertSame('Công ty TNHH Du lịch Hải Đăng', data_get($settings->structured_data, 'company.legal_name'));
        $this->assertSame('0312345678', data_get($settings->structured_data, 'company.business_license'));
        $this->assertSame('79-723/2017/TCDL-GP LHQT', data_get($settings->structured_data, 'company.international_travel_license'));
        $this->assertSame('0312345678', data_get($settings->structured_data, 'company.tax_code'));
        $this->assertSame('123 Đường Mẫu', data_get($settings->structured_data, 'address.street_address'));
        $this->assertSame('Thành phố Hồ Chí Minh', data_get($settings->structured_data, 'address.address_locality'));
        $this->assertSame('Hồ Chí Minh', data_get($settings->structured_data, 'address.address_region'));
        $this->assertSame('700000', data_get($settings->structured_data, 'address.postal_code'));
        $this->assertSame('VN', data_get($settings->structured_data, 'address.address_country'));
    }

    public function test_theme_settings_manager_can_store_frontsite_section_heading_overrides(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.frontsite_section_headings.tour_details.title', 'Nội dung hành trình')
            ->set('form.frontsite_section_headings.tour_departures.is_visible', false)
            ->set('form.frontsite_section_headings.tour_departures.description', 'Lịch đi đã được cập nhật theo từng tháng.')
            ->set('form.frontsite_section_headings.service_related_questions.title', 'Câu hỏi nhanh về {title}')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('Nội dung hành trình', data_get($settings->structured_data, 'frontsite_section_headings.tour_details.title'));
        $this->assertFalse(data_get($settings->structured_data, 'frontsite_section_headings.tour_departures.is_visible'));
        $this->assertSame('Lịch đi đã được cập nhật theo từng tháng.', data_get($settings->structured_data, 'frontsite_section_headings.tour_departures.description'));
        $this->assertSame('Câu hỏi nhanh về {title}', data_get($settings->structured_data, 'frontsite_section_headings.service_related_questions.title'));
    }

    public function test_theme_settings_manager_renders_frontsite_section_headings_as_a_separate_tab(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->assertSee('role="tablist"', false)
            ->assertSee('data-theme-settings-tab="general"', false)
            ->assertSee('data-theme-settings-tab="frontsite-headings"', false)
            ->assertSeeText('Cấu hình chung')
            ->assertSeeText('Heading section frontsite');
    }

    public function test_theme_settings_can_use_library_images_from_media_popup_for_primary_assets(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $settings = SiteSetting::query()->findOrFail(1);
        $logoMedia = $settings
            ->addMedia(UploadedFile::fake()->image('theme-logo-library.jpg', 800, 800))
            ->usingName('Theme logo library')
            ->usingFileName('theme-logo-library.jpg')
            ->withCustomProperties(['alt' => 'Logo từ media library'])
            ->toMediaCollection('library', 'public');
        $faviconMedia = $settings
            ->addMedia(UploadedFile::fake()->image('theme-favicon-library.png', 64, 64))
            ->usingName('Theme favicon library')
            ->usingFileName('theme-favicon-library.png')
            ->withCustomProperties(['alt' => 'Favicon từ media library'])
            ->toMediaCollection('library', 'public');
        $ogMedia = $settings
            ->addMedia(UploadedFile::fake()->image('theme-og-library.jpg', 1200, 630))
            ->usingName('Theme og library')
            ->usingFileName('theme-og-library.jpg')
            ->withCustomProperties(['alt' => 'OG từ media library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->call('selectLibraryMediaForUpload', 'logoUpload', $logoMedia->id)
            ->call('selectLibraryMediaForUpload', 'faviconUpload', $faviconMedia->id)
            ->call('selectLibraryMediaForUpload', 'ogImageUpload', $ogMedia->id)
            ->call('save')
            ->assertHasNoErrors();

        $settings->refresh();
        $logo = $settings->getFirstMedia('logo');
        $favicon = $settings->getFirstMedia('favicon');
        $ogImage = $settings->getFirstMedia('og_image');

        $this->assertNotNull($logo);
        $this->assertNotNull($favicon);
        $this->assertNotNull($ogImage);
        $this->assertSame($logoMedia->id, (int) data_get($logo?->custom_properties, 'source_library_media_id'));
        $this->assertSame($faviconMedia->id, (int) data_get($favicon?->custom_properties, 'source_library_media_id'));
        $this->assertSame($ogMedia->id, (int) data_get($ogImage?->custom_properties, 'source_library_media_id'));
    }

    public function test_theme_settings_manager_can_store_instagram_url(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.instagram_url', 'https://www.instagram.com/haidangtravel')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('https://www.instagram.com/haidangtravel', $settings->instagram_url);
    }

    public function test_theme_settings_manager_can_store_configurable_footer_social_links(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY, [
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
            ])
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);
        $storedConfig = data_get($settings->structured_data, FooterSocialLinks::STRUCTURED_DATA_KEY);

        $this->assertTrue(data_get($storedConfig, 'is_configured'));
        $this->assertSame('Facebook hỗ trợ', data_get($storedConfig, 'items.0.label'));
        $this->assertSame('Tư vấn nhanh qua fanpage', data_get($storedConfig, 'items.0.description'));
        $this->assertSame('youtube', data_get($storedConfig, 'items.1.platform'));
        $this->assertFalse(data_get($storedConfig, 'items.2.is_active'));
    }

    public function test_theme_settings_manager_can_store_customer_loyalty_api_credentials(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.customer_loyalty_api_base_url', 'https://loyalty.example.test/api/')
            ->set('form.customer_loyalty_api_username', 'staff@example.com')
            ->set('form.customer_loyalty_api_password', 'secret')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('https://loyalty.example.test/api', $settings->customer_loyalty_api_base_url);
        $this->assertSame('staff@example.com', $settings->customer_loyalty_api_username);
        $this->assertSame('secret', $settings->customer_loyalty_api_password);
        $this->assertNull($settings->customer_loyalty_api_token);
    }

    public function test_theme_settings_manager_can_check_customer_loyalty_api_login(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.customer_loyalty_api_base_url', 'https://loyalty.example.test/api')
            ->set('form.customer_loyalty_api_username', 'staff@example.com')
            ->set('form.customer_loyalty_api_password', 'secret')
            ->call('save')
            ->call('checkCustomerLoyaltyApi')
            ->assertHasNoErrors()
            ->assertSeeText('Kiểm tra API quà thành công');

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('fresh-token', $settings->customer_loyalty_api_token);
        $this->assertNotNull($settings->customer_loyalty_api_token_expires_at);
        $this->assertNotNull($settings->customer_loyalty_api_token_refreshed_at);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://loyalty.example.test/api/DashboardLogin'
                && $request['UserName'] === 'staff@example.com'
                && $request['Password'] === 'secret';
        });
    }

    public function test_theme_settings_manager_uses_fresh_customer_loyalty_token_without_forcing_login(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $settings = SiteSetting::query()->findOrFail(1);
        $settings->forceFill([
            'customer_loyalty_api_base_url' => 'https://loyalty.example.test/api',
            'customer_loyalty_api_username' => 'staff@example.com',
            'customer_loyalty_api_password' => 'secret',
            'customer_loyalty_api_token' => 'stored-token',
            'customer_loyalty_api_token_expires_at' => now()->addYear(),
            'customer_loyalty_api_token_refreshed_at' => now(),
        ])->save();

        Http::preventStrayRequests();

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->call('checkCustomerLoyaltyApi')
            ->assertHasNoErrors()
            ->assertSeeText('Kiểm tra API quà thành công');
    }

    public function test_theme_settings_manager_normalizes_google_maps_share_url_to_embed_url(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.map_embed_url', 'https://www.google.com/maps/search/10.827624,+106.642022?entry=tts')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('https://www.google.com/maps?q=10.827624%2C%20106.642022&output=embed', $settings->map_embed_url);
    }

    public function test_theme_settings_manager_accepts_google_maps_iframe_code(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.map_embed_url', '<iframe src="https://www.google.com/maps/embed?pb=abc123" width="600"></iframe>')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('https://www.google.com/maps/embed?pb=abc123', $settings->map_embed_url);
    }

    public function test_theme_settings_manager_can_store_sitewide_tracking_ids(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.ga_measurement_id', 'g-ab12c34def')
            ->set('form.facebook_pixel_id', '123456789012345')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('G-AB12C34DEF', $settings->ga_measurement_id);
        $this->assertSame('123456789012345', $settings->facebook_pixel_id);
    }

    public function test_theme_settings_manager_can_store_google_recaptcha_v3_settings(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->assertSeeText('Google reCAPTCHA v3')
            ->set('form.google_recaptcha_v3_enabled', true)
            ->set('form.google_recaptcha_v3_site_key', 'site-key-demo')
            ->set('form.google_recaptcha_v3_secret_key', 'secret-key-demo')
            ->set('form.google_recaptcha_v3_min_score', 0.7)
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertTrue($settings->google_recaptcha_v3_enabled);
        $this->assertSame('site-key-demo', $settings->google_recaptcha_v3_site_key);
        $this->assertSame('secret-key-demo', $settings->google_recaptcha_v3_secret_key);
        $this->assertSame(0.7, $settings->google_recaptcha_v3_min_score);
    }

    public function test_theme_settings_manager_can_store_sitewide_custom_html_snippets(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.after_header_html', '  <div data-sitewide-after-header>After header</div>  ')
            ->set('form.end_body_html', '  <script>window.__sitewideEndBody = true;</script>  ')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('<div data-sitewide-after-header>After header</div>', $settings->after_header_html);
        $this->assertSame('<script>window.__sitewideEndBody = true;</script>', $settings->end_body_html);
    }

    public function test_theme_settings_manager_can_store_shared_tour_terms(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.tour_terms_title', 'Điều khoản tour chung')
            ->set('form.tour_terms_items', [
                [
                    'title' => 'Giữ chỗ',
                    'content' => '<p>Khách cần <a href="https://example.com/chinh-sach-dat-coc" target="_blank">đặt cọc</a> để giữ chỗ và liên hệ sớm nếu cần đổi ngày.</p>',
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertSame('Điều khoản tour chung', $settings->tour_terms_title);
        $this->assertSame('Giữ chỗ', data_get($settings->tour_terms_items, '0.title'));
        $this->assertStringContainsString('href="https://example.com/chinh-sach-dat-coc"', (string) data_get($settings->tour_terms_items, '0.content'));
    }

    public function test_theme_settings_manager_normalizes_bare_domain_links_in_tour_terms(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ThemeSettingsManager::class)
            ->set('form.tour_terms_title', 'Điều khoản tour chung')
            ->set('form.tour_terms_items', [
                [
                    'title' => 'Liên kết',
                    'content' => '<p>Check link <a href="google.com">check</a></p>',
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $settings = SiteSetting::query()->findOrFail(1);

        $this->assertStringContainsString('href="https://google.com"', (string) data_get($settings->tour_terms_items, '0.content'));
    }

    public function test_theme_settings_manager_can_clear_frontsite_cache(): void
    {
        Cache::forget('frontsite:versions');
        Cache::forget('frontsite:last_clear');

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $this->assertSame(1, data_get(app(FrontsiteCache::class)->status(), 'global_version'));

        Livewire::test(ThemeSettingsManager::class)
            ->assertSee('wire:click="clearFrontsiteCache"', false)
            ->assertSeeText('Cache frontsite')
            ->call('clearFrontsiteCache')
            ->assertHasNoErrors()
            ->assertSeeText('Đã xóa cache frontsite');

        $status = app(FrontsiteCache::class)->status();

        $this->assertSame(2, data_get($status, 'global_version'));
        $this->assertSame(['all'], data_get($status, 'last_clear.groups'));
    }
}
