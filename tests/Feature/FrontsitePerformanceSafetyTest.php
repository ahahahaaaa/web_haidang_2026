<?php

namespace Tests\Feature;

use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FrontsitePerformanceSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_expandable_rich_text_reserves_its_collapsed_height_before_javascript_loads(): void
    {
        $this->view('themes.haidangtravel.partials.expandable-rich-text', [
            'contentHtml' => '<p>Nội dung dài để kiểm tra trạng thái thu gọn ban đầu.</p>',
            'desktopCollapsedHeight' => 600,
            'expandableId' => 'performance-expandable-panel',
            'mobileCollapsedHeight' => 300,
        ])
            ->assertSee('style="--tour-details-collapsed-height-mobile: 300px; --tour-details-collapsed-height-desktop: 600px;"', false)
            ->assertSee('invisible', false)
            ->assertSee('aria-hidden="true"', false);
    }


    public function test_public_html_cache_stores_session_cookie_pages_and_restores_csrf_token(): void
    {
        config()->set('frontsite_cache.enabled', true);
        config()->set('frontsite_cache.middleware.enabled', true);

        $this->seed(CmsBootstrapSeeder::class);

        $firstResponse = $this->get(route('home'));
        $secondResponse = $this->get(route('home'));

        $firstResponse
            ->assertOk()
            ->assertHeader('X-Frontsite-Cache', 'MISS')
            ->assertDontSee('__FRONTSITE_CSRF_TOKEN__', false);

        $secondResponse
            ->assertOk()
            ->assertHeader('X-Frontsite-Cache', 'HIT')
            ->assertDontSee('__FRONTSITE_CSRF_TOKEN__', false);

        $this->assertSame(
            $this->csrfTokenFromHtml((string) $firstResponse->getContent()),
            $this->csrfTokenFromHtml((string) $secondResponse->getContent()),
        );
    }

    public function test_zalo_end_body_embed_keeps_widget_div_and_defers_sdk_script(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $zaloWidget = '<div class="zalo-chat-widget" data-oaid="3240233203137612609" data-welcome-message="Bạn cần tư vấn tour?" data-autopopup="0" data-width="" data-height=""></div>';

        SiteSetting::query()->findOrFail(1)->update([
            'end_body_html' => $zaloWidget."\n\n".'<script src="https://sp.zalo.me/plugins/sdk.js"></script>',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($zaloWidget, false)
            ->assertSee('const loadZalo = () => {', false)
            ->assertSee("script.src = 'https://sp.zalo.me/plugins/sdk.js';", false)
            ->assertSee('requestIdleCallback(loadZalo, { timeout: 3500 });', false)
            ->assertDontSee('<script src="https://sp.zalo.me/plugins/sdk.js"></script>', false);
    }

    public function test_boost_is_disabled_by_default_outside_local_environment(): void
    {
        $this->assertFalse(config('boost.enabled'));
        $this->assertFalse(config('boost.browser_logs_watcher'));
    }

    private function csrfTokenFromHtml(string $html): string
    {
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'Expected rendered frontsite HTML to include a CSRF meta token.');

        return $matches[1];
    }
}
