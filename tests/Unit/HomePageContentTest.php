<?php

namespace Tests\Unit;

use App\Support\HomePageContent;
use PHPUnit\Framework\TestCase;

class HomePageContentTest extends TestCase
{
    public function test_prepare_config_sanitizes_home_hero_title_and_description_as_inline_rich_text(): void
    {
        $config = HomePageContent::prepareConfig([
            'hero' => [
                'slides' => [
                    [
                        'uuid' => 'hero-1',
                        'eyebrow' => 'Trang chủ',
                        'title' => '<p>Thi công <span style="color: #E21B23; position: fixed;">đúng chuẩn</span></p>',
                        'description' => '<p>Dòng một</p><p>Dòng hai</p>',
                        'primary_label' => 'Nhận báo giá',
                        'primary_url' => '#consultation',
                        'secondary_label' => 'Xem dự án',
                        'secondary_url' => '/du-an',
                        'image_alt' => 'Hero',
                    ],
                ],
            ],
        ]);

        $slide = $config['hero']['slides'][0];

        $this->assertSame('Thi công <span style="color: #E21B23">đúng chuẩn</span>', $slide['title']);
        $this->assertSame('Dòng một<br>Dòng hai', $slide['description']);
    }
}
