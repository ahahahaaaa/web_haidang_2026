<?php

namespace Tests\Feature\SeoOptimization;

use App\Services\SeoOptimization\OptimizationSheetGateway;
use App\Services\SeoOptimization\PublicImageDownloader;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SheetGatewaySafetyTest extends OptimizationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['seo_optimization.spreadsheet_id' => 'test-sheet',
            'seo_optimization.sheet_endpoint' => 'https://script.google.com/macros/s/test/exec',
            'seo_optimization.sheet_secret' => str_repeat('test-only-', 4)]);
    }

    public function test_gateway_rejects_wrong_workbook(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'spreadsheet_id' => 'another-sheet', 'data' => ['row' => null]])]);
        $this->expectException(ValidationException::class);
        app(OptimizationSheetGateway::class)->getNext('haidang-test', ['service']);
    }

    public function test_gateway_rejects_wrong_event_ack(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'spreadsheet_id' => 'test-sheet', 'data' => ['ack' => true, 'event_id' => 'other-event']])]);
        $this->expectException(ValidationException::class);
        app(OptimizationSheetGateway::class)->syncEvent('expected-event', []);
    }

    public function test_redirect_cannot_forward_secret_to_another_host(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://attacker.example/macros/echo'])]);
        try {
            app(OptimizationSheetGateway::class)->getNext('haidang-test', ['service']);
            $this->fail('Untrusted redirect must be rejected.');
        } catch (ValidationException) {
            Http::assertSentCount(1);
        }
    }

    public function test_google_content_redirect_is_get_without_signed_payload(): void
    {
        Http::fake([
            'https://script.google.com/*' => Http::response('', 302, ['Location' => 'https://script.googleusercontent.com/macros/echo?key=test']),
            'https://script.googleusercontent.com/*' => Http::response(['ok' => true, 'spreadsheet_id' => 'test-sheet', 'data' => ['row' => null]]),
        ]);
        $this->assertNull(app(OptimizationSheetGateway::class)->getNext('haidang-test', ['service']));
        Http::assertSent(fn ($request) => $request->method() === 'GET' && str_contains($request->url(), 'googleusercontent.com') && ! isset($request['signature']) && ! isset($request['payload']));
    }

    public function test_image_downloader_rejects_private_dns_before_network(): void
    {
        $downloader = new class extends PublicImageDownloader
        {
            protected function resolve(string $host): array
            {
                return ['8.8.8.8', '127.0.0.1'];
            }
        };
        try {
            $downloader->download('https://images.example/photo.png');
            $this->fail('Mixed public/private DNS must be rejected.');
        } catch (ValidationException) {
            Http::assertNothingSent();
        }
    }

    public function test_image_downloader_blocks_non_public_addresses_and_unsafe_urls(): void
    {
        $downloader = app(PublicImageDownloader::class);
        foreach (['127.0.0.1', '10.0.0.1', '169.254.169.254', '100.64.0.1', '192.0.2.1', '198.18.0.1', '203.0.113.1', '::1', '::ffff:127.0.0.1', 'fc00::1', '2001:db8::1'] as $ip) {
            $this->assertFalse($downloader->isPublicIp($ip), $ip);
        }
        $this->assertTrue($downloader->isPublicIp('8.8.8.8'));
        foreach (['http://images.example/a.jpg', 'file:///tmp/a.jpg', 'https://user:pass@images.example/a.jpg', 'https://images.example:8443/a.jpg', 'https://images.example/a.jpg#fragment'] as $url) {
            try {
                $downloader->validatedHost($url);
                $this->fail('Unsafe URL accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
