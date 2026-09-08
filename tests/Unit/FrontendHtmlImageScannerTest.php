<?php

namespace Tests\Unit;

use App\Services\Admin\FrontendHtmlImageScanner;
use Tests\TestCase;

class FrontendHtmlImageScannerTest extends TestCase
{
    public function test_it_scans_unique_remote_images_from_code_html_files(): void
    {
        $scanner = app(FrontendHtmlImageScanner::class);

        $images = $scanner->scanDirectory(base_path('tests/Fixtures/front_end/importable'));

        $this->assertCount(2, $images);
        $this->assertSame('https://example.com/images/hero.png', $images[0]['src']);
        $this->assertSame('Duplicate hero', $images[0]['alt']);
        $this->assertSame(2, $images[0]['occurrences']);
        $this->assertSame([
            'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'front_end'.DIRECTORY_SEPARATOR.'importable'.DIRECTORY_SEPARATOR.'blog'.DIRECTORY_SEPARATOR.'code.html',
            'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'front_end'.DIRECTORY_SEPARATOR.'importable'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.'code.html',
        ], $images[0]['source_files']);
        $this->assertSame('https://example.com/images/team.png', $images[1]['src']);
        $this->assertSame('Team shot', $images[1]['alt']);
    }
}
