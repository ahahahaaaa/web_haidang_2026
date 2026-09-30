<?php

namespace Tests\Feature;

use App\Support\FrontsiteUrls;
use App\Support\LandingPageBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Tests\TestCase;

class FrontsitePrimarySeoRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['frontsite_cache.enabled' => false, 'frontsite_seo.canonical_url' => 'https://haidangtravel.com', 'frontsite_seo.canonical_redirect_enabled' => false]);
        SiteSetting::query()->create(['id' => 1, 'active_theme' => 'haidangtravel', 'site_name' => 'Hải Đăng Travel', 'seo_robots' => 'index,follow']);
    }

    public function test_pasted_html_document_does_not_duplicate_page_structure_or_metadata(): void
    {
        $widget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $widget['html'] = '<!DOCTYPE html><html lang="vi"><head><title>Title widget</title><meta name="description" content="Description widget"><link rel="canonical" href="/wrong"><style>.team-content { color: red; }</style><script>window.teamWidget = true;</script></head><body><section class="team-content"><h1>Team Building Hải Đăng</h1><p>Chương trình doanh nghiệp.</p></section></body></html>';
        LandingPage::query()->create(['title' => 'Team Building', 'slug' => 'teambuilding', 'is_active' => true, 'blocks' => [$widget]]);

        $html = $this->get('/teambuilding')->assertOk()->getContent();

        foreach (['html', 'head', 'body', 'title', 'h1'] as $tag) {
            $this->assertSame(1, preg_match_all('/<'.$tag.'(?:\s|>)/i', $html), $tag);
        }
        $this->assertSame(1, substr_count($html, 'name="description"'));
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
        $this->assertStringContainsString('.team-content { color: red; }', $html);
        $this->assertStringContainsString('window.teamWidget = true;', $html);
        $this->assertStringContainsString('<h1>Team Building Hải Đăng</h1>', $html);
        $this->assertStringContainsString('Chương trình doanh nghiệp.', $html);
    }

    public function test_image_only_slider_has_a_visible_page_heading_by_default(): void
    {
        $hero = ['mode' => 'slider', 'slides' => [['image_url' => 'https://example.test/banner.jpg', 'title' => '']], 'autoplay_delay' => 5000];
        $html = view('themes.haidangtravel.partials.landing-hero', ['hero' => $hero, 'fallbackTitle' => 'Du lịch Hải Đăng Travel', 'landing' => null])->render();

        $this->assertSame(1, preg_match_all('/<h1(?:\s|>)/i', $html));
        $this->assertStringContainsString('Du lịch Hải Đăng Travel', $html);
        $this->assertStringNotContainsString('sr-only', $html);
    }

    public function test_homepage_keeps_one_semantic_h1_visually_hidden_at_one_pixel(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1(?:\s|>)/i', $html));
        $this->assertMatchesRegularExpression('/<h1\b[^>]*class="[^"]*\bsr-only\b[^"]*"[^>]*>/i', $html);
    }

    public function test_shared_landing_hero_keeps_h1_visible_unless_explicitly_hidden(): void
    {
        $hero = ['mode' => 'slider', 'slides' => [['title' => 'Tour trong nước']]];
        $visibleHtml = view('themes.haidangtravel.partials.landing-hero', [
            'hero' => $hero,
            'fallbackTitle' => 'Du lịch Hải Đăng Travel',
            'landing' => null,
        ])->render();
        $hiddenHtml = view('themes.haidangtravel.partials.landing-hero', [
            'hero' => $hero,
            'fallbackTitle' => 'Du lịch Hải Đăng Travel',
            'landing' => null,
            'visuallyHideHeading' => true,
        ])->render();

        $this->assertDoesNotMatchRegularExpression('/<h1\b[^>]*class="[^"]*\bsr-only\b/i', $visibleHtml);
        $this->assertMatchesRegularExpression('/<h1\b[^>]*class="sr-only"[^>]*>Tour trong nước<\/h1>/i', $hiddenHtml);
    }

    public function test_html_mode_and_multiple_widgets_share_one_primary_heading(): void
    {
        LandingPage::query()->create(['title' => 'Trang HTML', 'slug' => 'trang-html', 'is_active' => true, 'editor_mode' => LandingPage::EDITOR_MODE_HTML, 'body' => '<html><head><title>Title cũ</title></head><body><h1>Tiêu đề được biên tập</h1><p>Nội dung HTML</p></body></html>']);
        $html = $this->get('/trang-html')->assertOk()->getContent();
        $this->assertSame(1, preg_match_all('/<h1(?:\s|>)/i', $html));
        $this->assertSame(1, preg_match_all('/<title(?:\s|>)/i', $html));
        $this->assertStringContainsString('<h1>Tiêu đề được biên tập</h1>', $html);

        $first = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $second = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $first['html'] = '<section><h1>Tiêu đề chính</h1></section>';
        $second['html'] = '<section><h1>Tiêu đề bổ sung</h1></section>';
        LandingPage::query()->create(['title' => 'Trang nhiều widget', 'slug' => 'nhieu-widget', 'is_active' => true, 'blocks' => [$first, $second]]);
        $html = $this->get('/nhieu-widget')->assertOk()->getContent();
        $this->assertSame(1, preg_match_all('/<h1(?:\s|>)/i', $html));
        $this->assertStringContainsString('<h1>Tiêu đề chính</h1>', $html);
        $this->assertStringContainsString('<h2>Tiêu đề bổ sung</h2>', $html);
    }

    public function test_slider_has_one_primary_heading_with_multiple_titled_slides(): void
    {
        $hero = ['mode' => 'slider', 'slides' => [['title' => 'Tour trong nước'], ['title' => 'Tour nước ngoài']]];
        $html = view('themes.haidangtravel.partials.landing-hero', ['hero' => $hero, 'fallbackTitle' => 'Du lịch Hải Đăng Travel', 'landing' => null])->render();

        $this->assertSame(1, preg_match_all('/<h1(?:\s|>)/i', $html));
        $this->assertStringContainsString('<h2 data-hero-text="title"', $html);
    }

    public function test_html_mode_without_a_body_heading_uses_page_title_despite_unused_blocks(): void
    {
        $widget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $widget['html'] = '<h1>Tiêu đề block không sử dụng</h1>';
        LandingPage::query()->create(['title' => 'Trang HTML có tiêu đề', 'slug' => 'html-co-tieu-de', 'is_active' => true, 'editor_mode' => LandingPage::EDITOR_MODE_HTML, 'body' => '<p>Nội dung HTML không có H1.</p>', 'blocks' => [$widget]]);

        $html = $this->get('/html-co-tieu-de')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1(?:\s|>)/i', $html));
        $this->assertStringContainsString('Trang HTML có tiêu đề</h1>', $html);
        $this->assertStringNotContainsString('Tiêu đề block không sử dụng', $html);
    }

    public function test_blog_canonical_skips_redirect_only_legacy_url_and_sitemap_keeps_final_url(): void
    {
        $category = ContentCategory::query()->create(['name' => 'Kinh nghiệm du lịch', 'slug' => 'kinh-nghiem-du-lich', 'taxonomy' => 'blog']);
        $post = BlogPost::query()->create(['title' => 'Tứ Động Tâm', 'slug' => 'tu-dong-tam', 'content_category_id' => $category->id, 'status' => 'published', 'published_at' => now()->subDay(), 'canonical_url' => 'https://haidangtravel.com/blog/tu-dong-tam']);

        $this->get('/blog/tu-dong-tam')->assertStatus(301)->assertRedirect(FrontsiteUrls::blogPost($post));
        $this->get('/kinh-nghiem-du-lich/tu-dong-tam')->assertOk()
            ->assertSee('<link rel="canonical" href="https://haidangtravel.com/kinh-nghiem-du-lich/tu-dong-tam">', false);
        $this->get('/sitemap-blogs.xml')->assertOk()
            ->assertSee('<loc>https://haidangtravel.com/kinh-nghiem-du-lich/tu-dong-tam</loc>', false)
            ->assertDontSee('https://haidangtravel.com/blog/tu-dong-tam', false);
    }

    public function test_explicit_legacy_url_stays_canonical_when_an_active_mapping_renders_it(): void
    {
        $post = BlogPost::query()->create(['title' => 'Bài viết URL cũ', 'slug' => 'url-cu', 'status' => 'published', 'published_at' => now()->subDay(), 'canonical_url' => 'https://haidangtravel.com/blog/url-cu']);
        PublicUrlMapping::query()->create(['source_hash' => hash('sha256', '/blog/url-cu'), 'source_path' => '/blog/url-cu', 'mode' => PublicUrlMapping::MODE_RENDER, 'target_type' => 'blog_post', 'target_id' => (string) $post->id, 'target_path' => '/bai-viet/url-cu', 'status_code' => 200, 'is_active' => true]);

        $this->assertSame('https://haidangtravel.com/blog/url-cu', FrontsiteUrls::canonicalBlogPost($post));
        $this->get('/blog/url-cu')->assertOk()->assertSee('<link rel="canonical" href="https://haidangtravel.com/blog/url-cu">', false);
    }

    public function test_tour_and_service_canonicals_skip_their_redirect_only_aliases(): void
    {
        $tour = Tour::query()->create(['title' => 'Tour Đà Lạt', 'slug' => 'da-lat', 'scope' => 'domestic', 'status' => 'published', 'canonical_url' => 'https://haidangtravel.com/tour/da-lat']);
        $service = Service::query()->create(['title' => 'Visa', 'slug' => 'visa', 'status' => 'published', 'canonical_url' => 'https://haidangtravel.com/page/visa']);

        $this->get('/chuong-trinh/da-lat')->assertOk()->assertSee('<link rel="canonical" href="https://haidangtravel.com/chuong-trinh/da-lat">', false);
        $this->get('/dich-vu/visa')->assertOk()->assertSee('<link rel="canonical" href="https://haidangtravel.com/dich-vu/visa">', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('https://haidangtravel.com/chuong-trinh/da-lat', false)->assertSee('https://haidangtravel.com/dich-vu/visa', false);
    }

    public function test_search_pages_keep_noindex_without_canonicalizing_to_a_noindex_page(): void
    {
        foreach (['/tim-tour', '/tim-tour?q=da-lat'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<meta name="robots" content="noindex,follow">', false)
                ->assertDontSee('rel="canonical"', false);
        }

        $this->get('/tour-trong-nuoc?q=da-lat')->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertSee('rel="canonical"', false);
    }
}
