<?php

namespace Tests\Feature;

use App\Support\TravelHomePageConfig;
use Database\Seeders\HaidangTravelBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\LandingPage;
use Tests\TestCase;

class HomePopularSearchBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_seeds_deterministic_popular_searches_and_renders_them_below_featured_tours(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $homePage = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $popularSearches = data_get(
            TravelHomePageConfig::prepare($homePage->home_config, $homePage->blocks ?? []),
            'featured_tours.popular_searches',
            [],
        );

        $this->assertCount(9, $popularSearches);
        $this->assertSame('home-popular-ha-giang', data_get($popularSearches, '0.uuid'));
        $this->assertSame('Hà Giang', data_get($popularSearches, '0.label'));
        $this->assertSame('/tim-tour?q=ha-giang', data_get($popularSearches, '0.url'));
        $this->assertSame('home-featured-region-mien-bac', data_get($popularSearches, '0.filter_uuid'));
        $this->assertSame('home-popular-ai-cap', data_get($popularSearches, '8.uuid'));
        $this->assertSame('Ai Cập', data_get($popularSearches, '8.label'));
        $this->assertSame('home-featured-region-chau-phi', data_get($popularSearches, '8.filter_uuid'));
        $this->assertTrue(collect($popularSearches)->every(
            fn (array $item): bool => TravelHomePageConfig::isSafeFeaturedTourPopularSearchUrl($item['url'] ?? null),
        ));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-home-featured-popular-searches', false)
            ->assertSee('href="/tim-tour?q=ha-giang"', false)
            ->assertSee('data-home-featured-popular-search-filter="home-featured-region-mien-bac"', false)
            ->assertSee('data-home-featured-popular-search-filter="home-featured-region-chau-phi"', false)
            ->assertSeeText('New Zealand')
            ->assertSeeText('Ai Cập');

        $this->seed(HaidangTravelBootstrapSeeder::class);

        $this->assertSame(
            $popularSearches,
            data_get(
                TravelHomePageConfig::prepare($homePage->refresh()->home_config, $homePage->blocks ?? []),
                'featured_tours.popular_searches',
                [],
            ),
        );
    }
}
