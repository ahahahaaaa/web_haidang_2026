<?php

namespace Tests\Unit;

use App\Support\LandingPageHtml;
use Tests\TestCase;

class LandingPageHtmlTest extends TestCase
{
    public function test_document_becomes_a_fragment_without_losing_styles_scripts_or_body_attributes(): void
    {
        $html = '<html><head><title>Metadata cũ</title><link rel="stylesheet" href="/widget.css"><style>.widget h1 { font-size: 48px; }</style></head><body class="widget" data-widget="team"><h1>Hải Đăng Travel</h1><script>window.widgetMarkup = "<body><meta name=\'description\'>";</script></body></html>';
        $fragment = LandingPageHtml::render($html, allowPrimaryHeading: true);

        $this->assertStringContainsString('<link rel="stylesheet" href="/widget.css">', $fragment);
        $this->assertStringContainsString('.widget h1 { font-size: 48px; }', $fragment);
        $this->assertStringContainsString('<div class="widget" data-widget="team">', $fragment);
        $this->assertStringContainsString('<h1>Hải Đăng Travel</h1>', $fragment);
        $this->assertStringContainsString('window.widgetMarkup = "<body><meta name=\'description\'>";', $fragment);
        $this->assertStringNotContainsString('Metadata cũ', $fragment);
    }

    public function test_svg_accessible_title_and_primary_heading_are_preserved(): void
    {
        $fragment = LandingPageHtml::render('<section><h1>Du lịch Hải Đăng</h1><svg role="img"><title>Biểu tượng du lịch</title><path d="M0 0"></path></svg></section>', allowPrimaryHeading: true);

        $this->assertStringContainsString('<title>Biểu tượng du lịch</title>', $fragment);
        $this->assertTrue(LandingPageHtml::hasPrimaryHeading($fragment));
        $this->assertFalse(LandingPageHtml::hasPrimaryHeading('<script>window.template = "<h1>";</script>'));
    }
}
