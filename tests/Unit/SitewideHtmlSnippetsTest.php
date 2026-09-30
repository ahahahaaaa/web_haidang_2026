<?php

namespace Tests\Unit;

use App\Support\SitewideHtmlSnippets;
use Tests\TestCase;

class SitewideHtmlSnippetsTest extends TestCase
{
    public function test_after_header_html_stabilizes_zalo_widget_and_lazy_loads_snippet_images(): void
    {
        $html = SitewideHtmlSnippets::afterHeaderHtml('<div class="zalo-chat-widget" style="left: 16px;"></div><img src="https://cdn.example.test/banner.webp" alt="Banner">');

        $this->assertStringContainsString('zalo-chat-widget', $html);
        $this->assertStringContainsString('position: fixed;', $html);
        $this->assertStringContainsString('left: auto !important;', $html);
        $this->assertStringContainsString('right: 16px !important;', $html);
        $this->assertStringContainsString('bottom: 16px !important;', $html);
        $this->assertStringContainsString('data-left-side="false"', $html);
        $this->assertStringContainsString('width: 60px;', $html);
        $this->assertStringContainsString('contain: layout size;', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
        $this->assertStringContainsString('fetchpriority="low"', $html);
    }

    public function test_end_body_html_defers_zalo_sdk_until_interaction_or_idle_fallback(): void
    {
        $html = SitewideHtmlSnippets::endBodyHtml('<script src="https://sp.zalo.me/plugins/sdk.js"></script>');

        $this->assertStringNotContainsString('<script src="https://sp.zalo.me/plugins/sdk.js"></script>', $html);
        $this->assertStringContainsString("script.src = 'https://sp.zalo.me/plugins/sdk.js';", $html);
        $this->assertStringContainsString("['pointerdown', 'touchstart', 'keydown', 'scroll']", $html);
        $this->assertStringContainsString('window.setTimeout(loadZalo, 12000)', $html);
    }

    public function test_zalo_widget_stays_on_the_right_without_repeating_its_position_style(): void
    {
        $html = '<div class="zalo-chat-widget" data-left-side="true" style="left: 52px;"></div>';

        $once = SitewideHtmlSnippets::endBodyHtml($html);
        $twice = SitewideHtmlSnippets::endBodyHtml($once);

        $this->assertStringNotContainsString('data-left-side="true"', $twice);
        $this->assertSame(1, substr_count($twice, 'data-left-side="false"'));
        $this->assertSame(1, substr_count($twice, 'right: 16px !important;'));
    }
}
