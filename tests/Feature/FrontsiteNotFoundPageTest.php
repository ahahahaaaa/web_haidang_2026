<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Tests\TestCase;

class FrontsiteNotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_url_redirects_use_static_map_before_tour_search_fallback(): void
    {
        $this->withHeaders(['Accept' => 'text/html'])
            ->get('/tin-tuc/tour-du-lich-an-do-tron-goi')
            ->assertStatus(301)
            ->assertRedirect(url('/chuong-trinh/tour-du-lich-an-do-nepal'));

        $this->withHeaders(['Accept' => 'text/html'])
            ->get('/tin-tuc/tour-du-lich-an-do-tron-goi-cu')
            ->assertRedirect(route('tours.search', ['q' => 'tour du lich an do tron goi cu']));
    }

    public function test_frontsite_not_found_page_suggests_hot_and_latest_tours(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'hotline' => '0909 123 456',
                'phone' => '028 1234 5678',
                'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
                'site_name' => 'Hải Đăng Travel',
            ],
        );

        $category = TourCategory::query()->create([
            'name' => 'Tour nghỉ dưỡng',
            'slug' => 'tour-nghi-duong',
            'status' => 'published',
        ]);

        $destination = Destination::query()->create([
            'name' => 'Đà Nẵng',
            'slug' => 'da-nang',
            'status' => 'published',
        ]);

        $hotTour = Tour::query()->create([
            'title' => 'Đà Nẵng cuối tuần nổi bật',
            'slug' => 'da-nang-cuoi-tuan-noi-bat',
            'excerpt' => 'Hành trình ngắn ngày cho gia đình.',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'transport' => 'Máy bay',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 3,
            'duration_nights' => 2,
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 4990000,
            'cta_mode' => 'booking',
            'is_featured' => true,
            'published_at' => now()->subDays(2),
        ]);

        $latestTour = Tour::query()->create([
            'title' => 'Hội An lịch mới cập nhật',
            'slug' => 'hoi-an-lich-moi-cap-nhat',
            'excerpt' => 'Lịch trình mới cho nhóm bạn và gia đình.',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'transport' => 'Xe du lịch',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 2,
            'duration_nights' => 1,
            'standard_label' => 'Tiêu chuẩn tiết kiệm',
            'sale_price' => 2990000,
            'cta_mode' => 'booking',
            'is_featured' => false,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/duong-dan-khong-ton-tai.css')
            ->assertNotFound()
            ->assertHeader('X-Robots-Tag', 'noindex,follow')
            ->assertSee('meta name="robots" content="noindex,follow"', false)
            ->assertSeeText('Không tìm thấy trang bạn đang tìm')
            ->assertSeeText('Tour đang nổi bật')
            ->assertSeeText($hotTour->title)
            ->assertSeeText('Tour mới cập nhật')
            ->assertSeeText($latestTour->title);

        $this->get('/rate_blog/801.css')
            ->assertNotFound()
            ->assertSeeText('Không tìm thấy trang bạn đang tìm');
    }
}
