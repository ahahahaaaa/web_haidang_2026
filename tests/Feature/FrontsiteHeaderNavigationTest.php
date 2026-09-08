<?php

namespace Tests\Feature;

use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FrontsiteHeaderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_renders_active_cms_menu_items_without_runtime_filtering(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Tour trong nước, tour nước ngoài, tour đoàn và dịch vụ du lịch.',
                'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
            ],
        );

        $header = Menu::query()->where('location', 'header')->firstOrFail();

        MenuItem::query()->updateOrCreate(
            ['menu_id' => $header->getKey(), 'label' => 'Tour trong nước'],
            ['url' => '/tour-trong-nuoc', 'order' => 30, 'is_active' => true, 'target' => '_self'],
        );
        MenuItem::query()->updateOrCreate(
            ['menu_id' => $header->getKey(), 'label' => 'Điểm thưởng'],
            ['url' => '/diem-thuong', 'order' => 31, 'is_active' => true, 'target' => '_self'],
        );
        MenuItem::query()->updateOrCreate(
            ['menu_id' => $header->getKey(), 'label' => 'Dự toán xây dựng'],
            ['url' => '/du-toan', 'order' => 32, 'is_active' => true, 'target' => '_self'],
        );
        MenuItem::query()->updateOrCreate(
            ['menu_id' => $header->getKey(), 'label' => 'Thi công nội thất'],
            ['url' => '/thi-cong-noi-that', 'order' => 33, 'is_active' => true, 'target' => '_self'],
        );

        Cache::flush();

        $html = $this->get(route('home'))->assertOk()->getContent();
        $headerHtml = $this->extractHeaderHtml($html);

        $this->assertStringContainsString('Về chúng tôi', $headerHtml);
        $this->assertStringContainsString('Tour trong nước', $headerHtml);
        $this->assertStringContainsString('Điểm thưởng', $headerHtml);
        $this->assertStringContainsString('/diem-thuong', $headerHtml);
        $this->assertStringContainsString('Dịch vụ', $headerHtml);
        $this->assertStringContainsString('Blog', $headerHtml);
        $this->assertStringContainsString('Dự án', $headerHtml);
        $this->assertStringContainsString('/du-an', $headerHtml);
        $this->assertStringContainsString('Dự toán xây dựng', $headerHtml);
        $this->assertStringContainsString('/du-toan', $headerHtml);
        $this->assertStringContainsString('Thi công nội thất', $headerHtml);
        $this->assertStringContainsString('/thi-cong-noi-that', $headerHtml);
    }

    private function extractHeaderHtml(string $html): string
    {
        $matched = preg_match('/<header\b.*?<\/header>/s', $html, $matches);

        $this->assertSame(1, $matched, 'Expected the frontsite response to include a header element.');

        return $matches[0];
    }
}
