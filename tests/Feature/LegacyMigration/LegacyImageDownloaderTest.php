<?php

namespace Tests\Feature\LegacyMigration;

use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Services\LegacyImageDownloader;
use App\Services\SeoOptimization\PublicImageDownloader;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyImageDownloaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('legacy_migration.media.allow_any_public_host', true);
        Http::preventStrayRequests();
    }

    #[DataProvider('publicWebUrls')]
    public function test_public_http_and_https_downloads_pin_the_validated_address(string $url, int $port, int $protocol): void
    {
        $png = $this->png();
        $requests = [];
        Http::fake(function (Request $request, array $options) use ($png, &$requests) {
            $requests[] = [$request, $options];

            return Http::response($png, 200, ['Content-Type' => 'image/png']);
        });
        $downloader = $this->downloader(['images.example.test' => ['8.8.8.8']]);

        $upload = $downloader->download($url);

        try {
            $this->assertSame($png, file_get_contents($upload->getPathname()));
            $this->assertSame('image/png', $upload->getMimeType());
            $this->assertCount(1, $requests);
            $options = $requests[0][1];
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame('', $options['proxy']);
            $this->assertSame(['images.example.test:'.$port.':8.8.8.8'], $options['curl'][CURLOPT_RESOLVE]);
            $this->assertSame($protocol, $options['curl'][CURLOPT_PROTOCOLS]);
            $this->assertLessThanOrEqual(25, $options['timeout']);
            $this->assertLessThanOrEqual(5, $options['connect_timeout']);
        } finally {
            unlink($upload->getPathname());
        }
    }

    public static function publicWebUrls(): array
    {
        return [
            'HTTP' => ['http://images.example.test/photo.jpg', 80, CURLPROTO_HTTP],
            'HTTPS' => ['https://images.example.test/photo.jpg', 443, CURLPROTO_HTTPS],
            'explicit HTTP port' => ['http://images.example.test:80/photo.jpg', 80, CURLPROTO_HTTP],
            'explicit HTTPS port' => ['https://images.example.test:443/photo.jpg', 443, CURLPROTO_HTTPS],
        ];
    }

    public function test_object_deadline_limits_the_http_timeout_without_disabling_tls_verification(): void
    {
        $png = $this->png();
        $requests = [];
        Http::fake(function (Request $request, array $options) use ($png, &$requests) {
            $requests[] = $options;

            return Http::response($png, 200);
        });
        $upload = $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/image.png', microtime(true) + 2);
        try {
            $this->assertCount(1, $requests);
            $this->assertGreaterThan(0, $requests[0]['timeout']);
            $this->assertLessThanOrEqual(2, $requests[0]['timeout']);
            $this->assertLessThanOrEqual(2, $requests[0]['connect_timeout']);
            $this->assertTrue($requests[0]['verify']);
        } finally {
            unlink($upload->getPathname());
        }
    }

    public function test_expired_object_deadline_does_not_initiate_dns_or_http(): void
    {
        $downloader = $this->downloader(['images.example.test' => ['8.8.8.8']]);
        try {
            $downloader->download('https://images.example.test/image.jpg', microtime(true) - 1);
            $this->fail('Expired deadline must prevent DNS and HTTP work.');
        } catch (LegacyImageDownloadFailure $exception) {
            $this->assertSame('timeout', $exception->reasonCode);
        }
        $this->assertSame([], $downloader->resolved);
        Http::assertNothingSent();
    }

    public function test_each_redirect_is_resolved_validated_and_pinned_without_forwarding_credentials(): void
    {
        $png = $this->png();
        $requests = [];
        Http::fake(function (Request $request, array $options) use ($png, &$requests) {
            $requests[] = [$request, $options];

            return match (count($requests)) {
                1 => Http::response('', 301, ['Location' => 'https://cdn.example.test/dir/start.jpg']),
                2 => Http::response('', 302, ['Location' => '../ảnh gốc.png?sig=a+b%2Fc&key=x%20y#photo']),
                default => Http::response($png, 200),
            };
        });
        $downloader = $this->downloader(['images.example.test' => ['8.8.8.8'], 'cdn.example.test' => ['1.1.1.1']]);

        $upload = $downloader->download('http://images.example.test/photo.jpg');

        try {
            $this->assertSame(['images.example.test', 'cdn.example.test', 'cdn.example.test'], $downloader->resolved);
            $this->assertSame('https://cdn.example.test/%E1%BA%A3nh%20g%E1%BB%91c.png?sig=a+b%2Fc&key=x%20y', $requests[2][0]->url());
            $this->assertSame(['images.example.test:80:8.8.8.8'], $requests[0][1]['curl'][CURLOPT_RESOLVE]);
            $this->assertSame(['cdn.example.test:443:1.1.1.1'], $requests[1][1]['curl'][CURLOPT_RESOLVE]);
            $this->assertSame(['cdn.example.test:443:1.1.1.1'], $requests[2][1]['curl'][CURLOPT_RESOLVE]);
            foreach ($requests as [$request, $options]) {
                $this->assertFalse($options['allow_redirects']);
                $this->assertFalse($request->hasHeader('Authorization'));
                $this->assertFalse($request->hasHeader('Cookie'));
                $this->assertFalse($request->hasHeader('Referer'));
            }
            $this->assertLessThanOrEqual($requests[0][1]['timeout'], $requests[2][1]['timeout']);
        } finally {
            unlink($upload->getPathname());
        }
    }

    public function test_scheme_relative_redirect_and_public_http_target_are_supported(): void
    {
        $png = $this->png();
        Http::fake([
            'https://images.example.test/start.jpg' => Http::response('', 307, ['Location' => '//cdn.example.test/next.jpg']),
            'https://cdn.example.test/next.jpg' => Http::response('', 308, ['Location' => 'http://cdn.example.test/final.png']),
            'http://cdn.example.test/final.png' => Http::response($png, 200),
        ]);

        $upload = $this->downloader(['images.example.test' => ['8.8.8.8'], 'cdn.example.test' => ['1.1.1.1']])->download('https://images.example.test/start.jpg');

        try {
            Http::assertSentCount(3);
            $this->assertSame($png, file_get_contents($upload->getPathname()));
        } finally {
            unlink($upload->getPathname());
        }
    }

    #[DataProvider('unsafeRedirectTargets')]
    public function test_unsafe_redirect_is_rejected_before_a_request_to_the_target(string $target): void
    {
        Http::fake(['https://images.example.test/start.jpg' => Http::response('', 302, ['Location' => $target])]);
        $downloader = $this->downloader(['images.example.test' => ['8.8.8.8'], 'internal.example.test' => ['8.8.8.8', '10.0.0.1']]);

        try {
            $downloader->download('https://images.example.test/start.jpg');
            $this->fail('Unsafe redirect was accepted.');
        } catch (InvalidArgumentException) {
            Http::assertSentCount(1);
        }
    }

    public static function unsafeRedirectTargets(): array
    {
        return [
            'loopback' => ['https://127.0.0.1/photo.jpg'],
            'private LAN' => ['http://10.0.0.1/photo.jpg'],
            'cloud metadata' => ['http://169.254.169.254/photo.jpg'],
            'IPv6 loopback' => ['https://[::1]/photo.jpg'],
            'IPv4 mapped IPv6' => ['https://[::ffff:127.0.0.1]/photo.jpg'],
            'mixed public/private DNS' => ['https://internal.example.test/photo.jpg'],
            'credentials' => ['https://user:password@cdn.example.test/photo.jpg'],
            'non-web scheme' => ['ftp://cdn.example.test/photo.jpg'],
            'nonstandard port' => ['https://cdn.example.test:8443/photo.jpg'],
            'header control character' => ["https://cdn.example.test/pho\tto.jpg"],
            'backslash' => ['https://cdn.example.test/photo\\name.jpg'],
        ];
    }

    #[DataProvider('nonPublicAddresses')]
    public function test_direct_non_public_address_is_rejected_without_http(string $ip): void
    {
        $downloader = $this->downloader(['images.example.test' => [$ip]]);

        try {
            $downloader->download('https://images.example.test/photo.jpg');
            $this->fail('Non-public address was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('IP nội bộ hoặc IP dành riêng', $exception->getMessage());
            Http::assertNothingSent();
        }
    }

    public static function nonPublicAddresses(): array
    {
        return array_map(fn (string $ip): array => [$ip], [
            '127.0.0.1', '10.0.0.1', '172.16.0.1', '192.168.1.1', '169.254.169.254',
            '100.64.0.1', '198.18.0.1', '192.0.0.1', '203.0.113.1', '::1', 'fc00::1',
            'fe80::1', '::ffff:8.8.8.8', '2001:db8::1', '2002::1',
        ]);
    }

    public function test_empty_dns_fails_before_http(): void
    {
        $this->expectExceptionMessage('Nguồn ảnh không phân giải được DNS/IP.');

        try {
            $this->downloader([])->download('https://images.example.test/photo.jpg');
        } finally {
            Http::assertNothingSent();
        }
    }

    #[DataProvider('invalidRedirects')]
    public function test_redirect_missing_location_or_loop_is_rejected(?string $location, string $message): void
    {
        Http::fake(['https://images.example.test/photo.jpg' => Http::response('', 302, $location === null ? [] : ['Location' => $location])]);
        $this->expectExceptionMessage($message);

        $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
    }

    public static function invalidRedirects(): array
    {
        return [
            'missing Location' => [null, 'thiếu Location'],
            'self loop' => ['/photo.jpg', 'vòng lặp'],
            'fragment only' => ['#photo', 'vòng lặp'],
        ];
    }

    public function test_redirect_chain_stops_at_five_hops(): void
    {
        $count = 0;
        Http::fake(function () use (&$count) {
            $count++;

            return Http::response('', 302, ['Location' => '/photo-'.$count.'.jpg']);
        });
        $this->expectExceptionMessage('giới hạn 5 lần chuyển hướng');

        try {
            $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
        } finally {
            Http::assertSentCount(6);
        }
    }

    public function test_allowlist_is_checked_again_at_the_redirect_target(): void
    {
        config()->set('legacy_migration.media.allow_any_public_host', false);
        config()->set('legacy_migration.media.allowed_hosts', ['images.example.test']);
        Http::fake(['https://images.example.test/photo.jpg' => Http::response('', 302, ['Location' => 'https://cdn.example.test/photo.png'])]);
        $this->expectExceptionMessage('danh sách host migration');

        try {
            $this->downloader(['images.example.test' => ['8.8.8.8'], 'cdn.example.test' => ['1.1.1.1']])->download('https://images.example.test/photo.jpg');
        } finally {
            Http::assertSentCount(1);
        }
    }

    #[DataProvider('failedResponses')]
    public function test_http_and_mime_errors_include_the_actual_reason_and_clean_the_temporary_file(int $status, string $body, string $message): void
    {
        $temporary = null;
        Http::fake(function (Request $request, array $options) use ($status, $body, &$temporary) {
            $temporary = $options['sink'];

            return Http::response($body, $status, ['Content-Type' => 'image/jpeg']);
        });
        $this->expectExceptionMessage($message);

        try {
            $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
        } finally {
            $this->assertNotNull($temporary);
            $this->assertFileDoesNotExist($temporary);
        }
    }

    public static function failedResponses(): array
    {
        return [
            'missing image' => [404, 'Not found', 'HTTP 404'],
            'blocked image' => [403, 'Forbidden', 'HTTP 403'],
            'HTML masquerading as JPEG' => [200, '<html><body>Blocked</body></html>', 'MIME: text/html'],
            'SVG' => [200, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" /></svg>', 'không nhận SVG hoặc HTML'],
        ];
    }

    public function test_connection_timeout_is_reported_explicitly(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timeout'));
        $this->expectException(LegacyImageDownloadFailure::class);
        $this->expectExceptionMessage('Tải ảnh thất bại: quá thời gian tải.');

        $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
    }

    public function test_transient_source_failure_remains_retryable_by_the_queue_worker(): void
    {
        Http::fake(['https://images.example.test/photo.jpg' => Http::response('', 503)]);
        $this->expectException(LegacyImageDownloadFailure::class);
        $this->expectExceptionMessage('HTTP 503');

        $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
    }

    #[DataProvider('transportFailures')]
    public function test_transport_failures_keep_the_exact_code_and_retry_policy_without_query_tokens(int $code, string $message, bool $retryable): void
    {
        $url = 'https://images.example.test/photo.jpg?signature=secret-token';
        Http::fake(fn () => throw new ConnectionException('cURL error '.$code.': failure for '.$url));

        try {
            $this->downloader(['images.example.test' => ['8.8.8.8']])->download($url);
            $this->fail('Transport failure was accepted.');
        } catch (LegacyImageDownloadFailure $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
            $this->assertSame('curl_'.$code, $exception->reasonCode);
            $this->assertSame($retryable, $exception->retryable);
            $this->assertSame('https://images.example.test/photo.jpg', $exception->context()['image_source']);
            $this->assertStringNotContainsString('secret-token', json_encode($exception->context()));
            $this->assertNull($exception->getPrevious());
        }
    }

    public static function transportFailures(): array
    {
        return [
            [35, 'lỗi bắt tay TLS', true],
            [60, 'không xác thực được chứng chỉ', false],
            [77, 'không đọc được CA bundle', false],
            [6, 'không phân giải được DNS', true],
            [7, 'không kết nối được máy chủ nguồn', true],
            [28, 'quá thời gian tải', true],
        ];
    }

    public function test_connection_failure_tries_another_validated_public_ip_with_ssl_verification(): void
    {
        $requests = [];
        $png = $this->png();
        Http::fake(function (Request $request, array $options) use (&$requests, $png) {
            $requests[] = $options;
            if (count($requests) === 1) {
                throw new ConnectionException('cURL error 7: connect failed');
            }

            return Http::response($png, 200);
        });

        $upload = $this->downloader(['images.example.test' => ['8.8.8.8', '1.1.1.1']])->download('https://images.example.test/photo.jpg');

        try {
            $this->assertCount(2, $requests);
            $this->assertSame(['images.example.test:443:8.8.8.8'], $requests[0]['curl'][CURLOPT_RESOLVE]);
            $this->assertSame(['images.example.test:443:1.1.1.1'], $requests[1]['curl'][CURLOPT_RESOLVE]);
            $this->assertTrue($requests[0]['verify']);
            $this->assertTrue($requests[1]['verify']);
            $this->assertLessThanOrEqual($requests[0]['timeout'], $requests[1]['timeout']);
        } finally {
            unlink($upload->getPathname());
        }
    }

    public function test_403_is_retried_once_with_only_the_current_image_origin_as_referer(): void
    {
        $requests = [];
        $png = $this->png();
        Http::fake(function (Request $request) use (&$requests, $png) {
            $requests[] = $request;

            return count($requests) === 1 ? Http::response('', 403) : Http::response($png, 200);
        });

        $upload = $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg?signature=secret-token');

        try {
            $this->assertCount(2, $requests);
            $this->assertFalse($requests[0]->hasHeader('Referer'));
            $this->assertSame(['https://images.example.test/'], $requests[1]->header('Referer'));
            $this->assertFalse($requests[1]->hasHeader('Authorization'));
            $this->assertFalse($requests[1]->hasHeader('Cookie'));
        } finally {
            unlink($upload->getPathname());
        }
    }

    public function test_404_is_terminal_and_does_not_retry_other_ips(): void
    {
        Http::fake(fn () => Http::response('Not found', 404));

        try {
            $this->downloader(['images.example.test' => ['8.8.8.8', '1.1.1.1']])->download('https://images.example.test/missing.jpg');
            $this->fail('Missing image was accepted.');
        } catch (LegacyImageDownloadFailure $exception) {
            $this->assertSame('http_404', $exception->reasonCode);
            $this->assertFalse($exception->retryable);
            Http::assertSentCount(1);
        }
    }

    public function test_configured_ca_bundle_is_used_without_disabling_verification(): void
    {
        $ca = UploadedFile::fake()->createWithContent('cacert.pem', 'test certificate bundle');
        config()->set('legacy_migration.media.ca_bundle', $ca->getPathname());
        $options = [];
        $png = $this->png();
        Http::fake(function (Request $request, array $requestOptions) use (&$options, $png) {
            $options = $requestOptions;

            return Http::response($png, 200);
        });
        $upload = $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');

        try {
            $this->assertSame($ca->getPathname(), $options['verify']);
        } finally {
            unlink($upload->getPathname());
        }
    }

    public function test_missing_ca_bundle_fails_before_http(): void
    {
        config()->set('legacy_migration.media.ca_bundle', __DIR__.'/missing-ca.pem');

        try {
            $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
            $this->fail('Missing CA bundle was accepted.');
        } catch (LegacyImageDownloadFailure $exception) {
            $this->assertSame('ca_bundle', $exception->reasonCode);
            $this->assertFalse($exception->retryable);
            Http::assertNothingSent();
        }
    }

    #[DataProvider('fallbackAddresses')]
    public function test_system_dns_fallback_is_still_subject_to_public_ip_validation(array $addresses, bool $allowed): void
    {
        $downloader = new class($addresses) extends LegacyImageDownloader
        {
            public function __construct(private array $addresses) {}

            protected function dnsAddresses(string $host): array
            {
                return [];
            }

            protected function systemAddresses(string $host): array
            {
                return $this->addresses;
            }
        };
        $png = $this->png();
        Http::fake(fn () => Http::response($png, 200));

        if (! $allowed) {
            $this->expectException(InvalidArgumentException::class);
            try {
                $downloader->download('https://images.example.test/photo.jpg');
            } finally {
                Http::assertNothingSent();
            }

            return;
        }

        $upload = $downloader->download('https://images.example.test/photo.jpg');
        try {
            $this->assertSame($png, file_get_contents($upload->getPathname()));
        } finally {
            unlink($upload->getPathname());
        }
    }

    public static function fallbackAddresses(): array
    {
        return [[['8.8.8.8'], true], [['127.0.0.1'], false], [['8.8.8.8', '10.0.0.1'], false]];
    }

    #[DataProvider('sizeChecks')]
    public function test_byte_limits_are_enforced_at_headers_progress_and_final_body(string $check): void
    {
        config()->set('seo_optimization.media_max_bytes', 1024);
        Http::fake(function (Request $request, array $options) use ($check) {
            if ($check === 'headers') {
                $options['on_headers'](new Response(200, ['Content-Length' => '1025']));
            } elseif ($check === 'progress') {
                $options['progress'](0, 1025);
            }

            return Http::response(str_repeat('a', 1025), 200);
        });
        $this->expectExceptionMessage('dung lượng cho phép');

        $this->downloader(['images.example.test' => ['8.8.8.8']])->download('https://images.example.test/photo.jpg');
    }

    public static function sizeChecks(): array
    {
        return [['headers'], ['progress'], ['body']];
    }

    #[DataProvider('strictSeoUrls')]
    public function test_shared_seo_downloader_remains_strict(string $url): void
    {
        $this->expectException(ValidationException::class);

        (new PublicImageDownloader)->validatedHost($url);
    }

    public static function strictSeoUrls(): array
    {
        return [['http://images.example.test/photo.jpg'], ['https://images.example.test:443/photo.jpg'], ['https://images.example.test/photo.jpg#photo']];
    }

    private function png(): string
    {
        $image = UploadedFile::fake()->image('photo.png', 8, 8);

        return file_get_contents($image->getPathname());
    }

    private function downloader(array $addresses): LegacyImageDownloader
    {
        return new class($addresses) extends LegacyImageDownloader
        {
            public array $resolved = [];

            public function __construct(private array $addresses) {}

            protected function resolve(string $host): array
            {
                $this->resolved[] = $host;

                return filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->addresses[$host] ?? []);
            }
        };
    }
}
