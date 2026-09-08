<?php

namespace Tests\Feature;

use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FrontsiteTrackingScriptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_organization_image_and_postal_address_from_theme_settings(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'structured_data' => [
                'organization' => [
                    'image_url' => 'https://example.com/schema-image.jpg',
                ],
                'local_business' => [
                    'price_range' => 'Từ 2.500.000đ / khách',
                ],
                'address' => [
                    'street_address' => '123 Đường Mẫu',
                    'address_locality' => 'Thành phố Hồ Chí Minh',
                    'address_region' => 'Hồ Chí Minh',
                    'postal_code' => '700000',
                    'address_country' => 'VN',
                ],
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/schema-image.jpg"', false)
            ->assertSee('"@type":"PostalAddress"', false)
            ->assertSee('"streetAddress":"123 Đường Mẫu"', false)
            ->assertSee('"addressLocality":"Thành phố Hồ Chí Minh"', false)
            ->assertSee('"addressRegion":"Hồ Chí Minh"', false)
            ->assertSee('"postalCode":"700000"', false)
            ->assertSee('"addressCountry":"VN"', false)
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"priceRange":"Từ 2.500.000đ / khách"', false);
    }

    public function test_homepage_renders_sitewide_ga4_and_facebook_pixel_snippets(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'ga_measurement_id' => 'G-AB12C34DEF',
            'facebook_pixel_id' => '123456789012345',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-AB12C34DEF', false)
            ->assertSee('gtag(\'config\', "G-AB12C34DEF")', false)
            ->assertSee('https://connect.facebook.net/en_US/fbevents.js', false)
            ->assertSee('fbq(\'init\', "123456789012345")', false)
            ->assertSee('https://www.facebook.com/tr?id=123456789012345&ev=PageView&noscript=1', false);
    }

    public function test_homepage_renders_sitewide_custom_html_after_header_and_at_end_body(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'after_header_html' => '<div data-sitewide-after-header>After header custom HTML</div>',
            'end_body_html' => '<script>window.__sitewideEndBody = true;</script>',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                '<header',
                '<div data-sitewide-after-header>After header custom HTML</div>',
                '<main id="main-content"',
            ], false)
            ->assertSeeInOrder([
                '<script>window.__sitewideEndBody = true;</script>',
                '</body>',
            ], false);
    }
}
