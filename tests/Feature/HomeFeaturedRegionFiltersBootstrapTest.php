<?php

namespace Tests\Feature;

use App\Support\TravelHomePageConfig;
use Database\Seeders\HaidangTravelBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\LandingPage;
use Tests\TestCase;

class HomeFeaturedRegionFiltersBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_seeds_region_and_continent_filters_in_reference_order(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $homePage = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $config = TravelHomePageConfig::prepare($homePage->home_config, $homePage->blocks ?? []);
        $filters = data_get($config, 'featured_tours.filters', []);

        $this->assertSame([
            'mien-bac',
            'mien-trung',
            'mien-nam',
            'mien-tay-nam-bo',
            'chau-a',
            'chau-au',
            'chau-my',
            'chau-uc',
            'chau-phi',
        ], collect($filters)->pluck('source_value')->all());
        $this->assertTrue(collect($filters)->every(
            fn (array $filter): bool => data_get($filter, 'source_type') === TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION,
        ));

        $response = $this->get(route('home'))->assertOk();
        $html = $response->getContent();

        foreach (['Tất cả', 'Miền Bắc', 'Miền Trung', 'Miền Đông Nam Bộ', 'Miền Tây Nam Bộ', 'Châu Á', 'Châu Âu', 'Châu Mỹ', 'Châu Úc', 'Châu Phi'] as $label) {
            $response->assertSeeText($label);
        }

        $this->assertLessThan(
            strpos($html, 'id="home-featured-tab-home-featured-region-mien-bac"'),
            strpos($html, 'id="home-featured-tab-all"'),
        );
        $this->assertSame(
            10,
            substr_count($html, 'data-home-featured-tab-title="Tour hot trong tháng"'),
        );
    }
}
