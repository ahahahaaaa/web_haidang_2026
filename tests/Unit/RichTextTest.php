<?php

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    public function test_normalize_plain_strips_html_back_to_text(): void
    {
        $value = RichText::normalizePlain('<p>Đoạn <strong>một</strong></p><p><img src="/storage/demo.jpg" alt="demo"></p><p>Đoạn hai</p>');

        $this->assertSame("Đoạn một\n\nĐoạn hai", $value);
    }

    public function test_render_wraps_plain_text_into_paragraphs(): void
    {
        $html = (string) RichText::render("Dòng một\n\nDòng hai");

        $this->assertStringContainsString('<p>Dòng một</p>', $html);
        $this->assertStringContainsString('<p>Dòng hai</p>', $html);
    }

    public function test_sanitize_keeps_safe_image_attributes_and_removes_unsafe_bits(): void
    {
        $html = RichText::sanitize('<p><img src="/storage/demo.jpg" alt="demo" class="rounded-3xl" style="max-width: 100%; position: fixed; background-image: url(javascript:alert(1));" onclick="alert(1)" data-media-id="12"></p>');

        $this->assertStringContainsString('src="/storage/demo.jpg"', $html);
        $this->assertStringContainsString('class="rounded-3xl"', $html);
        $this->assertStringContainsString('data-media-id="12"', $html);
        $this->assertStringContainsString('style="max-width: 100%"', $html);
        $this->assertStringNotContainsString('onclick=', $html);
        $this->assertStringNotContainsString('position: fixed', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
    }

    public function test_sanitize_keeps_safe_table_markup_and_removes_unsafe_bits(): void
    {
        $html = RichText::sanitize('<table id="tour-price-table" style="width: 100%; border-collapse: collapse; position: fixed;" onclick="alert(1)"><thead><tr><th scope="COL" style="background-color: #fff; background-image: url(javascript:alert(1));">Tên tour</th><th>Giá</th></tr></thead><tbody><tr><td data-row="row-ab12" colspan="2" rowspan="1" onmouseover="alert(1)">Hạ Long</td><td data-row="bad<script">Không hợp lệ</td></tr></tbody></table><table id="bad id"><tr><td>Bảng lỗi</td></tr></table>');

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('id="tour-price-table"', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
        $this->assertStringContainsString('<th scope="col" style="background-color: #fff">Tên tour</th>', $html);
        $this->assertStringContainsString('data-row="row-ab12"', $html);
        $this->assertStringContainsString('colspan="2"', $html);
        $this->assertStringContainsString('rowspan="1"', $html);
        $this->assertStringContainsString('style="width: 100%; border-collapse: collapse"', $html);
        $this->assertStringNotContainsString('onclick=', $html);
        $this->assertStringNotContainsString('onmouseover=', $html);
        $this->assertStringNotContainsString('position: fixed', $html);
        $this->assertStringNotContainsString('background-image', $html);
        $this->assertStringNotContainsString('bad&lt;script', $html);
        $this->assertStringNotContainsString('id="bad id"', $html);
    }

    public function test_sanitize_inline_keeps_safe_color_and_flattens_blocks(): void
    {
        $html = RichText::sanitizeInline('<p>Thi công <span style="color: #E21B23; position: fixed;">đúng chuẩn</span></p><p>Biệt thự cao cấp</p>');

        $this->assertSame('Thi công <span style="color: #E21B23">đúng chuẩn</span><br>Biệt thự cao cấp', $html);
    }

    public function test_sanitize_normalizes_bare_domain_links_to_https(): void
    {
        $html = RichText::sanitize('<p>Check link <a href="google.com">check</a></p>');

        $this->assertStringContainsString('href="https://google.com"', $html);
    }

    public function test_sanitize_converts_quill_two_lists_to_semantic_lists(): void
    {
        $html = RichText::sanitize('<ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Điểm nhấn tour</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Dịch vụ đi kèm</li><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Gửi yêu cầu</li></ol>');

        $this->assertStringContainsString('<ul><li>Điểm nhấn tour</li><li>Dịch vụ đi kèm</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>Gửi yêu cầu</li></ol>', $html);
        $this->assertStringNotContainsString('data-list=', $html);
        $this->assertStringNotContainsString('ql-ui', $html);
    }

    public function test_sanitize_converts_quill_two_code_blocks_to_pre(): void
    {
        $html = RichText::sanitize('<div class="ql-code-block-container" spellcheck="false"><div class="ql-code-block">const place = "Đà Nẵng";</div><div class="ql-code-block">return place;</div></div>');

        $this->assertStringContainsString('<pre>const place = "Đà Nẵng";'."\n".'return place;</pre>', $html);
        $this->assertStringNotContainsString('ql-code-block', $html);
    }
}
