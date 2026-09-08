<?php

namespace Tests\Feature;

use App\Support\LandingPageBlocks;
use Database\Seeders\HaidangTravelBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\LandingPage;
use Tests\TestCase;

class TourScopeLandingBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_scope_landing_pages_render_html_widget_blocks(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $pages = [
            'group_tours' => route('tours.group'),
            'domestic_tours' => route('tours.domestic'),
            'international_tours' => route('tours.international'),
        ];

        foreach ($pages as $pageKey => $url) {
            $this->appendHtmlWidget($pageKey, 'scope');

            $this->get($url)
                ->assertOk()
                ->assertSee('data-scope-html-widget="'.$pageKey.'"', false)
                ->assertSee('Widget HTML cho '.$pageKey);
        }
    }

    public function test_core_system_landing_pages_render_html_widget_blocks(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $pages = [
            'home' => route('home'),
            'about' => route('about'),
            'services' => route('services.index'),
            'blog' => route('blog.index'),
            'contact' => route('contact'),
        ];

        foreach ($pages as $pageKey => $url) {
            $this->appendHtmlWidget($pageKey, 'system');

            $this->get($url)
                ->assertOk()
                ->assertSee('data-system-html-widget="'.$pageKey.'"', false)
                ->assertSee('Widget HTML cho '.$pageKey);
        }
    }

    public function test_home_blocks_can_choose_render_slots_around_featured_sections(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $beforeTopics = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $beforeTopics['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_TOPIC_RAIL;
        $beforeTopics['html'] = '<section data-home-slot-widget="before-topics">Widget trước chủ đề tour</section>';

        $beforeFeatured = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $beforeFeatured['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;
        $beforeFeatured['title'] = 'Rich text trước tour nổi bật';
        $beforeFeatured['body'] = '<p>Nội dung có thể đặt trước tour nổi bật.</p>';

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'blocks' => [$beforeTopics, $beforeFeatured],
            ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-home-slot-widget="before-topics"', false)
            ->assertSee('Rich text trước tour nổi bật')
            ->assertSee('id="home-tour-topics"', false)
            ->assertSee('id="featured-tours"', false)
            ->getContent();

        $beforeTopicsPosition = strpos($html, 'data-home-slot-widget="before-topics"');
        $topicsPosition = strpos($html, 'id="home-tour-topics"');
        $beforeFeaturedPosition = strpos($html, 'Rich text trước tour nổi bật');
        $featuredPosition = strpos($html, 'id="featured-tours"');

        $this->assertNotFalse($beforeTopicsPosition);
        $this->assertNotFalse($topicsPosition);
        $this->assertNotFalse($beforeFeaturedPosition);
        $this->assertNotFalse($featuredPosition);
        $this->assertLessThan($topicsPosition, $beforeTopicsPosition);
        $this->assertLessThan($beforeFeaturedPosition, $topicsPosition);
        $this->assertLessThan($featuredPosition, $beforeFeaturedPosition);
    }

    public function test_home_blocks_keep_stack_order_inside_same_render_slot(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $firstWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $middleWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $lastWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);

        $firstWidget['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;
        $firstWidget['html'] = '<section data-home-html-widget="first">HTML widget đầu</section>';
        $middleWidget['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;
        $middleWidget['html'] = '<section data-home-html-widget="middle">HTML widget giữa</section>';
        $lastWidget['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;
        $lastWidget['html'] = '<section data-home-html-widget="last">HTML widget cuối</section>';

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'blocks' => [$firstWidget, $middleWidget, $lastWidget],
            ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-home-html-widget="first"', false)
            ->assertSee('data-home-html-widget="middle"', false)
            ->assertSee('data-home-html-widget="last"', false)
            ->assertSee('id="featured-tours"', false)
            ->getContent();

        $firstPosition = strpos($html, 'data-home-html-widget="first"');
        $middlePosition = strpos($html, 'data-home-html-widget="middle"');
        $lastPosition = strpos($html, 'data-home-html-widget="last"');
        $featuredPosition = strpos($html, 'id="featured-tours"');

        $this->assertNotFalse($firstPosition);
        $this->assertNotFalse($middlePosition);
        $this->assertNotFalse($lastPosition);
        $this->assertNotFalse($featuredPosition);
        $this->assertLessThan($middlePosition, $firstPosition);
        $this->assertLessThan($lastPosition, $middlePosition);
        $this->assertLessThan($featuredPosition, $lastPosition);
    }

    public function test_home_html_widget_default_renders_before_featured_tours(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['html'] = '<section data-home-html-default="campaign">HTML widget mặc định trên homepage</section>';

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'blocks' => [$htmlWidget],
                'home_config' => [],
            ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-home-html-default="campaign"', false)
            ->assertSee('id="featured-tours"', false)
            ->assertSee('id="home-destination-slider"', false)
            ->getContent();

        $widgetPosition = strpos($html, 'data-home-html-default="campaign"');
        $featuredPosition = strpos($html, 'id="featured-tours"');
        $destinationPosition = strpos($html, 'id="home-destination-slider"');

        $this->assertNotFalse($widgetPosition);
        $this->assertNotFalse($featuredPosition);
        $this->assertNotFalse($destinationPosition);
        $this->assertLessThan($featuredPosition, $widgetPosition);
        $this->assertLessThan($destinationPosition, $widgetPosition);
    }

    public function test_home_layout_order_can_mix_fixed_sections_and_dynamic_blocks(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;
        $htmlWidget['html'] = '<section data-mixed-home-layout="campaign">Campaign HTML xen giữa section cố định</section>';

        $layoutOrder = [
            \App\Support\TravelHomePageConfig::homeLayoutTokenForSection('services'),
            \App\Support\TravelHomePageConfig::homeLayoutTokenForBlock((string) $htmlWidget['uuid']),
            \App\Support\TravelHomePageConfig::homeLayoutTokenForSection('featured_tours'),
        ];

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'blocks' => [$htmlWidget],
                'home_config' => \App\Support\TravelHomePageConfig::prepare([
                    'layout_order' => $layoutOrder,
                    'section_order' => ['services', 'featured_tours'],
                ], [$htmlWidget]),
            ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="core-services"', false)
            ->assertSee('data-mixed-home-layout="campaign"', false)
            ->assertSee('id="featured-tours"', false)
            ->getContent();

        $servicesPosition = strpos($html, 'id="core-services"');
        $widgetPosition = strpos($html, 'data-mixed-home-layout="campaign"');
        $featuredPosition = strpos($html, 'id="featured-tours"');

        $this->assertNotFalse($servicesPosition);
        $this->assertNotFalse($widgetPosition);
        $this->assertNotFalse($featuredPosition);
        $this->assertLessThan($widgetPosition, $servicesPosition);
        $this->assertLessThan($featuredPosition, $widgetPosition);
    }

    public function test_home_layout_order_inserts_new_dynamic_blocks_near_their_fallback_anchor(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $afterHeroWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $afterHeroWidget['home_position'] = LandingPageBlocks::HOME_POSITION_AFTER_HERO;
        $afterHeroWidget['html'] = '<section data-fallback-home-layout="after-hero">Campaign HTML ngay sau hero</section>';

        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;
        $htmlWidget['html'] = '<section data-fallback-home-layout="campaign">Campaign HTML fallback theo anchor</section>';

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'blocks' => [$afterHeroWidget, $htmlWidget],
                'home_config' => [
                    'layout_order' => [
                        \App\Support\TravelHomePageConfig::homeLayoutTokenForSection('services'),
                        \App\Support\TravelHomePageConfig::homeLayoutTokenForSection('featured_tours'),
                    ],
                    'section_order' => ['services', 'featured_tours'],
                ],
            ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-fallback-home-layout="after-hero"', false)
            ->assertSee('id="core-services"', false)
            ->assertSee('data-fallback-home-layout="campaign"', false)
            ->assertSee('id="featured-tours"', false)
            ->getContent();

        $afterHeroPosition = strpos($html, 'data-fallback-home-layout="after-hero"');
        $servicesPosition = strpos($html, 'id="core-services"');
        $widgetPosition = strpos($html, 'data-fallback-home-layout="campaign"');
        $featuredPosition = strpos($html, 'id="featured-tours"');

        $this->assertNotFalse($afterHeroPosition);
        $this->assertNotFalse($servicesPosition);
        $this->assertNotFalse($widgetPosition);
        $this->assertNotFalse($featuredPosition);
        $this->assertLessThan($servicesPosition, $afterHeroPosition);
        $this->assertLessThan($widgetPosition, $servicesPosition);
        $this->assertLessThan($featuredPosition, $widgetPosition);
    }

    public function test_home_dynamic_blocks_without_explicit_position_still_render_at_type_fallback_anchor(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $richText['title'] = 'Nội dung dynamic mặc định trên home';
        $richText['body'] = '<p>Block này không chọn home_position nhưng vẫn phải render.</p>';

        LandingPage::query()
            ->where('page_key', 'home')
            ->firstOrFail()
            ->update([
                'blocks' => [$richText],
                'home_config' => [],
            ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Nội dung dynamic mặc định trên home')
            ->assertSee('id="trust-and-proof"', false)
            ->getContent();

        $richTextPosition = strpos($html, 'Nội dung dynamic mặc định trên home');
        $trustPosition = strpos($html, 'id="trust-and-proof"');

        $this->assertNotFalse($richTextPosition);
        $this->assertNotFalse($trustPosition);
        $this->assertLessThan($trustPosition, $richTextPosition);
    }

    public function test_about_route_renders_configured_landing_content_blocks(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $cta = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_CTA);

        $richText['title'] = 'Block giới thiệu từ CMS';
        $richText['body'] = '<p>Nội dung rich text của route về chúng tôi.</p>';
        $htmlWidget['html'] = '<section data-about-html-widget="enabled">Widget HTML giữa block stack</section>';
        $cta['title'] = 'CTA từ block about';
        $cta['description'] = 'Mô tả CTA lấy từ landing block.';
        $cta['primary_label'] = 'Gửi yêu cầu block';
        $cta['primary_url'] = route('contact');

        LandingPage::query()
            ->where('page_key', 'about')
            ->firstOrFail()
            ->update([
                'blocks' => [$richText, $htmlWidget, $cta],
            ]);

        $response = $this->get(route('about'))
            ->assertOk()
            ->assertSee('Block giới thiệu từ CMS')
            ->assertSee('Nội dung rich text của route về chúng tôi.')
            ->assertSee('data-about-html-widget="enabled"', false)
            ->assertSee('CTA từ block about')
            ->assertDontSee('Thông tin chung');

        $html = $response->getContent();
        $richPosition = strpos($html, 'Block giới thiệu từ CMS');
        $widgetPosition = strpos($html, 'data-about-html-widget="enabled"');
        $ctaPosition = strpos($html, 'CTA từ block about');

        $this->assertNotFalse($richPosition);
        $this->assertNotFalse($widgetPosition);
        $this->assertNotFalse($ctaPosition);
        $this->assertLessThan($widgetPosition, $richPosition);
        $this->assertLessThan($ctaPosition, $widgetPosition);
    }

    public function test_tour_scope_landing_page_preserves_content_blocks_around_route_listing(): void
    {
        $this->seed(HaidangTravelBootstrapSeeder::class);

        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $tourList = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_LIST);
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $cta = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_CTA);

        $richText['title'] = 'Rich trước danh sách tour';
        $richText['body'] = '<p>Khối rich text trước danh sách route.</p>';
        $tourList['title'] = 'Danh sách tour route';
        $htmlWidget['html'] = '<section data-between-route-list="domestic">Widget giữa list và CTA</section>';
        $cta['title'] = 'CTA sau danh sách tour';
        $cta['description'] = 'CTA này lấy từ block landing page.';
        $cta['primary_label'] = 'Gửi yêu cầu từ block';
        $cta['primary_url'] = route('contact');

        LandingPage::query()
            ->where('page_key', 'domestic_tours')
            ->firstOrFail()
            ->update([
                'blocks' => [$richText, $tourList, $htmlWidget, $cta],
            ]);

        $html = $this->get(route('tours.domestic'))
            ->assertOk()
            ->assertSee('Rich trước danh sách tour')
            ->assertSee('data-between-route-list="domestic"', false)
            ->assertSee('CTA sau danh sách tour')
            ->getContent();

        $richPosition = strpos($html, 'Rich trước danh sách tour');
        $routeListPosition = strpos($html, 'Hiện có');
        $betweenPosition = strpos($html, 'data-between-route-list="domestic"');
        $ctaPosition = strpos($html, 'CTA sau danh sách tour');

        $this->assertNotFalse($richPosition);
        $this->assertNotFalse($routeListPosition);
        $this->assertNotFalse($betweenPosition);
        $this->assertNotFalse($ctaPosition);
        $this->assertLessThan($routeListPosition, $richPosition);
        $this->assertLessThan($betweenPosition, $routeListPosition);
        $this->assertLessThan($ctaPosition, $betweenPosition);
    }

    protected function appendHtmlWidget(string $pageKey, string $marker): void
    {
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['html'] = sprintf(
            '<section data-%s-html-widget="%s"><div>Widget HTML cho %s</div></section>',
            $marker,
            $pageKey,
            $pageKey,
        );

        $page = LandingPage::query()->where('page_key', $pageKey)->firstOrFail();
        $page->update([
            'blocks' => [
                ...LandingPageBlocks::normalize($page->blocks ?? []),
                $htmlWidget,
            ],
        ]);
    }
}
