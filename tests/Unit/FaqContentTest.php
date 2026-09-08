<?php

namespace Tests\Unit;

use App\Support\FaqContent;
use PHPUnit\Framework\TestCase;

class FaqContentTest extends TestCase
{
    public function test_normalize_items_keeps_safe_links_and_strips_unsafe_markup(): void
    {
        $items = FaqContent::normalizeItems([
            [
                'question' => '<strong>Cần chuẩn bị gì?</strong>',
                'answer' => '<p>Xem <a href="https://example.com/checklist" onclick="alert(1)">checklist</a> trước khi đi.</p><script>alert(1)</script>',
            ],
        ]);

        $this->assertSame('Cần chuẩn bị gì?', data_get($items, '0.question'));
        $this->assertStringContainsString('href="https://example.com/checklist"', (string) data_get($items, '0.answer'));
        $this->assertStringNotContainsString('onclick=', (string) data_get($items, '0.answer'));
        $this->assertStringNotContainsString('<script>', (string) data_get($items, '0.answer'));
    }
}
