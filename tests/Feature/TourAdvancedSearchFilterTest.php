<?php

namespace Tests\Feature;

use App\Support\FrontsiteUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;
use Tests\TestCase;

class TourAdvancedSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_and_search_page_render_the_advanced_tour_filter(): void
    {
        $fixture = $this->travelFixture();
        $minimumDepartureDate = now(config('app.timezone'))->addDay()->toDateString();

        foreach ([route('home'), route('tours.search')] as $url) {
            $response = $this->get($url);

            $response
                ->assertOk()
                ->assertSee('data-sitewide-tour-search-overlay', false)
                ->assertSee('bottom-5 z-30 hidden lg:block', false)
                ->assertSee('overflow-x-auto bg-transparent', false)
                ->assertSee('bg-[color:var(--color-primary-soft)] text-primary', false)
                ->assertSee('data-mobile-departure-selector', false)
                ->assertSee('data-mobile-tour-discovery', false)
                ->assertSee('aria-label="Bộ lọc tìm tour"', false)
                ->assertSee('action="'.route('tours.search').'"', false)
                ->assertSee('name="scope"', false)
                ->assertSee('data-tour-search-scope', false)
                ->assertSee('data-frontsite-select-hide-placeholder="true"', false)
                ->assertSee('data-frontsite-select-single-line="true"', false)
                ->assertSee('name="departure_location"', false)
                ->assertSee('name="destination"', false)
                ->assertSee('name="departure_date"', false)
                ->assertSee('min="'.$minimumDepartureDate.'"', false)
                ->assertSee('data-frontsite-datepicker-min-date="'.$minimumDepartureDate.'"', false)
                ->assertSee('data-frontsite-datepicker-month-selector="static"', false)
                ->assertSee('value="ho-chi-minh"', false)
                ->assertSee('value="'.$fixture['domestic_destination']->slug.'"', false)
                ->assertSeeText('Tour trọn gói')
                ->assertSeeText('Dịch vụ cộng thêm')
                ->assertSee('data-tour-search-popular-track', false)
                ->assertSee('data-tour-search-popular-prev', false)
                ->assertSee('data-tour-search-popular-next', false)
                ->assertSeeInOrder([
                    'data-tour-search-popular-prev',
                    'data-tour-search-popular-track',
                    'data-tour-search-popular-next',
                ], false)
                ->assertDontSeeText('Vé máy bay + khách sạn')
                ->assertDontSee('>Khách sạn<', false);

            $this->assertSame(1, substr_count($response->getContent(), 'data-sitewide-tour-search-overlay'));
            $this->assertSame(1, substr_count($response->getContent(), 'aria-label="Bộ lọc tìm tour"'));
            $this->assertSame(2, substr_count($response->getContent(), 'data-frontsite-select-single-line="true"'));
        }
    }

    public function test_search_non_group_scope_excludes_group_tours(): void
    {
        $fixture = $this->travelFixture();
        $groupRegion = Region::query()->create([
            'name' => 'Vùng chỉ có tour đoàn',
            'slug' => 'vung-chi-co-tour-doan',
            'scope' => TourScope::Group->value,
            'status' => 'published',
        ]);
        $groupDestination = Destination::query()->create([
            'name' => 'Điểm chỉ có tour đoàn',
            'slug' => 'diem-chi-co-tour-doan',
            'region_id' => $groupRegion->id,
            'status' => 'published',
        ]);
        Tour::query()->create([
            'title' => 'Tour đoàn chỉ để kiểm thử tìm kiếm',
            'slug' => 'tour-doan-chi-de-kiem-thu-tim-kiem',
            'status' => 'published',
            'scope' => TourScope::Group->value,
            'destination_id' => $groupDestination->id,
            'region_id' => $groupRegion->id,
        ]);

        $this->get(route('tours.search', ['scope' => 'non_group']))
            ->assertOk()
            ->assertSeeText($fixture['matching_tour']->title)
            ->assertDontSeeText('Tour đoàn chỉ để kiểm thử tìm kiếm')
            ->assertDontSee('value="diem-chi-co-tour-doan"', false);
    }

    public function test_tour_detail_omits_desktop_hero_filter_but_keeps_mobile_header_discovery(): void
    {
        $fixture = $this->travelFixture();

        $response = $this->get(route('tours.show', $fixture['matching_tour']))
            ->assertOk()
            ->assertSee('id="tour-hero"', false)
            ->assertSee('lg:py-12', false)
            ->assertSee('class="w-full space-y-7"', false)
            ->assertSee('font-heading text-lg font-extrabold leading-tight tracking-tight text-white sm:text-2xl lg:text-3xl', false)
            ->assertDontSee('class="max-w-4xl space-y-7"', false)
            ->assertDontSee('data-sitewide-tour-search-host', false)
            ->assertDontSee('data-sitewide-tour-search-overlay', false)
            ->assertDontSee('aria-label="Bộ lọc tìm tour"', false)
            ->assertSee('data-mobile-departure-selector', false)
            ->assertSee('data-mobile-tour-discovery', false)
            ->assertSeeText('Tour trong nước')
            ->assertSeeText('Tour nước ngoài')
            ->assertSeeText('Tour đoàn');

        $this->assertSame(0, substr_count($response->getContent(), 'data-sitewide-tour-search-overlay'));
    }

    public function test_tour_detail_can_hide_hero_from_theme_settings_and_keeps_one_visible_h1(): void
    {
        $fixture = $this->travelFixture();
        $settings = SiteSetting::query()->firstOrFail();
        $structuredData = $settings->structured_data ?? [];
        data_set($structuredData, 'frontsite_appearance.tour_detail.show_hero', false);
        $settings->update(['structured_data' => $structuredData]);

        $response = $this->get(route('tours.show', $fixture['matching_tour']))
            ->assertOk()
            ->assertDontSee('id="tour-hero"', false)
            ->assertSee('class="mt-4 w-full font-heading text-[0.9375rem] font-extrabold leading-tight tracking-tight text-slate-900 sm:text-lg lg:text-2xl"', false)
            ->assertDontSee('max-w-5xl font-heading text-3xl', false)
            ->assertSeeText($fixture['matching_tour']->title);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_blog_detail_omits_desktop_hero_filter_but_keeps_mobile_header_discovery(): void
    {
        $this->travelFixture();
        $category = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang du lịch',
            'slug' => 'cam-nang-du-lich',
        ]);
        $post = BlogPost::query()->create([
            'title' => 'Kinh nghiệm chuẩn bị hành trình',
            'slug' => 'kinh-nghiem-chuan-bi-hanh-trinh',
            'excerpt' => 'Các bước chuẩn bị trước chuyến đi.',
            'content' => '<p>Nội dung bài viết.</p>',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('lg:py-12', false)
            ->assertDontSee('data-sitewide-tour-search-host', false)
            ->assertDontSee('data-sitewide-tour-search-overlay', false)
            ->assertDontSee('aria-label="Bộ lọc tìm tour"', false)
            ->assertSee('data-mobile-departure-selector', false)
            ->assertSee('data-mobile-tour-discovery', false);

        $this->assertSame(0, substr_count($response->getContent(), 'data-sitewide-tour-search-overlay'));
    }

    public function test_combined_filters_only_return_tours_matching_every_condition(): void
    {
        $fixture = $this->travelFixture();

        $this->get(route('tours.search', [
            'scope' => TourScope::Domestic->value,
            'departure_location' => 'ho-chi-minh',
            'destination' => $fixture['domestic_destination']->slug,
            'departure_date' => $fixture['primary_date'],
        ]))
            ->assertOk()
            ->assertSeeText($fixture['matching_tour']->title)
            ->assertDontSeeText($fixture['other_date_tour']->title)
            ->assertDontSeeText($fixture['other_origin_tour']->title)
            ->assertDontSeeText($fixture['split_departure_tour']->title)
            ->assertDontSeeText($fixture['international_tour']->title)
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_departure_location_aliases_share_one_canonical_filter_value(): void
    {
        $fixture = $this->travelFixture();

        $this->get(route('tours.search', [
            'scope' => TourScope::Domestic->value,
            'departure_location' => 'ho-chi-minh',
            'destination' => $fixture['domestic_destination']->slug,
        ]))
            ->assertOk()
            ->assertSeeText($fixture['matching_tour']->title)
            ->assertSeeText($fixture['other_date_tour']->title)
            ->assertDontSeeText($fixture['other_origin_tour']->title)
            ->assertDontSeeText($fixture['international_tour']->title);
    }

    public function test_filter_rejects_invalid_enum_slug_and_past_date_values(): void
    {
        $this->travelFixture();

        $this->from(route('tours.search'))
            ->get(route('tours.search', [
                'scope' => 'unknown',
                'departure_location' => '<script>',
                'destination' => 'da nang',
                'departure_date' => now()->subDay()->toDateString(),
            ]))
            ->assertRedirect(route('tours.search'))
            ->assertSessionHasErrors([
                'scope',
                'departure_location',
                'destination',
                'departure_date',
            ]);
    }

    public function test_filter_requires_departure_date_to_start_from_tomorrow(): void
    {
        $this->travelFixture();

        $this->from(route('tours.search'))
            ->get(route('tours.search', [
                'departure_date' => now(config('app.timezone'))->toDateString(),
            ]))
            ->assertRedirect(route('tours.search'))
            ->assertSessionHasErrors('departure_date');

        $this->get(route('tours.search', [
            'departure_date' => now(config('app.timezone'))->addDay()->toDateString(),
        ]))
            ->assertOk()
            ->assertSessionDoesntHaveErrors('departure_date');
    }

    public function test_flight_product_tab_only_appears_when_a_published_service_exists(): void
    {
        $this->travelFixture();

        $this->get(route('tours.search'))
            ->assertOk()
            ->assertDontSee('>Vé máy bay<', false);

        $category = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Vé máy bay',
            'slug' => 've-may-bay',
        ]);

        Service::query()->create([
            'title' => 'Đặt vé máy bay',
            'slug' => 'dat-ve-may-bay',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
        ]);

        $this->get(route('tours.search'))
            ->assertOk()
            ->assertSeeText('Vé máy bay')
            ->assertSee(route('service-categories.show', ['category' => $category->slug]), false);
    }

    public function test_product_tabs_mark_the_current_route_active(): void
    {
        $this->travelFixture();
        $flightCategory = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Vé máy bay',
            'slug' => 've-may-bay',
        ]);
        $otherCategory = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Visa du lịch',
            'slug' => 'visa-du-lich-filter-test',
        ]);
        $flightService = Service::query()->create([
            'title' => 'Vé máy bay thử nghiệm',
            'slug' => 've-may-bay-thu-nghiem',
            'status' => 'published',
            'content_category_id' => $flightCategory->getKey(),
        ]);
        $otherService = Service::query()->create([
            'title' => 'Visa thử nghiệm',
            'slug' => 'visa-thu-nghiem',
            'status' => 'published',
            'content_category_id' => $otherCategory->getKey(),
        ]);

        $this->assertActiveProductTab($this->get(route('home'))->assertOk(), 'tour');
        $this->assertActiveProductTab($this->get(route('tours.search'))->assertOk(), 'tour');
        $this->assertActiveProductTab($this->get(route('service-categories.show', $flightCategory))->assertOk(), 'flight');
        $this->assertActiveProductTab($this->get(route('services.show', $flightService))->assertOk(), 'flight');
        $this->assertActiveProductTab($this->get(route('services.index'))->assertOk(), 'services');
        $this->assertActiveProductTab($this->get(route('service-categories.show', $otherCategory))->assertOk(), 'services');
        $this->assertActiveProductTab($this->get(route('services.show', $otherService))->assertOk(), 'services');
    }

    private function assertActiveProductTab(TestResponse $response, string $activeKey): void
    {
        foreach (['tour', 'flight', 'services'] as $key) {
            $response->assertSee(
                'data-tour-search-product-tab="'.$key.'" data-tour-search-product-tab-active="'.($key === $activeKey ? 'true' : 'false').'"',
                false,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function travelFixture(): array
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Tour trong nước và quốc tế.',
                'seo_description' => 'Tour trong nước và quốc tế.',
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'tour@example.com',
            ],
        );

        $category = TourCategory::query()->create([
            'name' => 'Tour hè nổi bật',
            'slug' => 'tour-he-noi-bat',
            'status' => 'published',
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);

        $domesticRegion = Region::query()->create([
            'name' => 'Miền Trung',
            'slug' => 'mien-trung-filter-test',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $internationalRegion = Region::query()->create([
            'name' => 'Đông Nam Á',
            'slug' => 'dong-nam-a-filter-test',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);

        $domesticDestination = Destination::query()->create([
            'region_id' => $domesticRegion->getKey(),
            'name' => 'Đà Nẵng',
            'slug' => 'da-nang-filter-test',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);
        $internationalDestination = Destination::query()->create([
            'region_id' => $internationalRegion->getKey(),
            'name' => 'Bangkok',
            'slug' => 'bangkok-filter-test',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $primaryDate = Carbon::today(config('app.timezone'))->addDays(15)->toDateString();
        $secondaryDate = Carbon::today(config('app.timezone'))->addDays(25)->toDateString();

        $matchingTour = $this->createTour(
            'Tour Đà Nẵng khớp toàn bộ filter',
            'tour-da-nang-match-filter',
            TourScope::Domestic,
            $category,
            $domesticDestination,
            $domesticRegion,
            'HCM',
            $primaryDate,
        );
        $otherDateTour = $this->createTour(
            'Tour Đà Nẵng khác ngày',
            'tour-da-nang-khac-ngay',
            TourScope::Domestic,
            $category,
            $domesticDestination,
            $domesticRegion,
            'HỒ CHÍ MINH',
            $secondaryDate,
        );
        $otherOriginTour = $this->createTour(
            'Tour Đà Nẵng khởi hành Hà Nội',
            'tour-da-nang-khoi-hanh-ha-noi',
            TourScope::Domestic,
            $category,
            $domesticDestination,
            $domesticRegion,
            'Sân bay Nội Bài',
            $primaryDate,
        );
        $internationalTour = $this->createTour(
            'Tour Bangkok quốc tế',
            'tour-bangkok-quoc-te-filter',
            TourScope::International,
            $category,
            $internationalDestination,
            $internationalRegion,
            'Sân bay Tân Sơn Nhất',
            $primaryDate,
        );
        $splitDepartureTour = Tour::query()->create([
            'title' => 'Tour có điểm đi và ngày ở hai lịch khác nhau',
            'slug' => 'tour-split-departure-filter',
            'excerpt' => 'Không được ghép điều kiện giữa hai lịch.',
            'content' => '<p>Nội dung tour kiểm thử.</p>',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $domesticDestination->getKey(),
            'region_id' => $domesticRegion->getKey(),
            'published_at' => now()->subDay(),
        ]);
        TourDeparture::query()->create([
            'tour_id' => $splitDepartureTour->getKey(),
            'departure_date' => $secondaryDate,
            'departure_location' => 'HCM',
            'status' => 'scheduled',
        ]);
        TourDeparture::query()->create([
            'tour_id' => $splitDepartureTour->getKey(),
            'departure_date' => $primaryDate,
            'departure_location' => 'Hà Nội',
            'status' => 'scheduled',
        ]);

        return [
            'domestic_destination' => $domesticDestination,
            'matching_tour' => $matchingTour,
            'other_date_tour' => $otherDateTour,
            'other_origin_tour' => $otherOriginTour,
            'international_tour' => $internationalTour,
            'split_departure_tour' => $splitDepartureTour,
            'primary_date' => $primaryDate,
        ];
    }

    private function createTour(
        string $title,
        string $slug,
        TourScope $scope,
        TourCategory $category,
        Destination $destination,
        Region $region,
        string $departureLocation,
        string $departureDate,
    ): Tour {
        $tour = Tour::query()->create([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'Dữ liệu kiểm thử bộ lọc tour.',
            'content' => '<p>Nội dung tour kiểm thử.</p>',
            'status' => 'published',
            'scope' => $scope->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'departure_location' => $departureLocation,
            'sale_price' => 6500000,
            'published_at' => now()->subDay(),
        ]);

        TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => $departureDate,
            'departure_location' => $departureLocation,
            'status' => 'scheduled',
            'sale_price' => 6500000,
        ]);

        return $tour;
    }
}
