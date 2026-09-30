<?php

namespace App\Modules\LegacyMigration\Services;

use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Services\SeoOptimization\PublicImageDownloader;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LegacyImageDownloader extends PublicImageDownloader
{
    public function normalizeUrl(string $url, bool $decodeEntities = true): string
    {
        $url = trim($decodeEntities ? html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $url);
        $url = explode('#', $url, 2)[0];

        if (preg_match('/[\x00-\x1f\\\\]/', $url)) {
            throw new InvalidArgumentException('Nguồn ảnh chứa ký tự điều khiển hoặc backslash không hợp lệ.');
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        $url = preg_replace_callback(
            '~\A(https?://[^/?#]+)(/[^?#]*)~i',
            fn (array $matches): string => $matches[1].preg_replace_callback(
                '/[ \x80-\xff]/',
                fn (array $character): string => rawurlencode($character[0]),
                $matches[2],
            ),
            $url,
        ) ?? $url;
        $url = preg_replace_callback('~\Ahttps?://~i', fn (array $matches): string => strtolower($matches[0]), $url) ?? $url;

        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\\\\]/', $url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Nguồn ảnh chứa ký tự hoặc độ dài URL không hợp lệ.');
        }

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? '';
        $port = $scheme === 'https' ? 443 : 80;

        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== $port)) {
            throw new InvalidArgumentException('Nguồn ảnh phải là URL HTTP/HTTPS, không có tài khoản hoặc cổng ngoài 80/443 chuẩn.');
        }

        return $url;
    }

    public function validatedHost(string $url): string
    {
        $url = $this->normalizeUrl($url, false);
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));
        $allowedHosts = array_map(fn (mixed $item): string => strtolower(trim((string) $item)), (array) config('legacy_migration.media.allowed_hosts', []));

        if (! (bool) config('legacy_migration.media.allow_any_public_host', true) && ! in_array($host, $allowedHosts, true)) {
            throw new InvalidArgumentException('Nguồn ảnh không thuộc danh sách host migration được phép.');
        }

        return $host;
    }

    public function download(string $url, ?float $objectDeadline = null): UploadedFile
    {
        $url = $this->normalizeUrl($url, false);
        abort_unless(extension_loaded('curl'), 503, 'Máy chủ cần PHP cURL để tải ảnh an toàn.');
        $maximum = $this->maxBytes();
        $temporary = tempnam(sys_get_temp_dir(), 'legacy-image-');
        abort_if($temporary === false, 503, 'Không tạo được tệp ảnh tạm.');
        $deadline = min(microtime(true) + 25, $objectDeadline ?? INF);
        $visited = [];

        try {
            for ($hop = 0; $hop <= 5; $hop++) {
                if (isset($visited[$url])) {
                    throw new InvalidArgumentException('Nguồn ảnh chuyển hướng thành vòng lặp.');
                }

                $visited[$url] = true;
                $host = $this->validatedHost($url);
                if (microtime(true) >= $deadline) {
                    throw new LegacyImageDownloadFailure('Đã hết ngân sách tải ảnh trước khi phân giải DNS.', $url, 'timeout', true);
                }
                $addresses = $this->resolve($host);
                if ($addresses === []) {
                    throw new LegacyImageDownloadFailure('Nguồn ảnh không phân giải được DNS/IP.', $url, 'dns', true);
                }
                if (collect($addresses)->contains(fn (string $ip): bool => ! $this->isPublicIp($ip))) {
                    throw new InvalidArgumentException('Nguồn ảnh trỏ tới IP nội bộ hoặc IP dành riêng; chỉ cho phép máy chủ Internet công khai.');
                }

                $remaining = $deadline - microtime(true);
                if ($remaining <= 0) {
                    throw new LegacyImageDownloadFailure('Tải ảnh vượt quá ngân sách HTTP 25 giây.', $url, 'timeout', true);
                }

                $response = $this->request($url, $host, $addresses, $temporary, $maximum, $deadline);

                if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                    $location = (string) $response->header('Location');
                    if ($location === '' || strlen($location) > 2048 || preg_match('/[\x00-\x1f\\\\]/', $location)) {
                        throw new InvalidArgumentException('Nguồn ảnh trả chuyển hướng thiếu Location hoặc Location không hợp lệ.');
                    }
                    if ($hop === 5) {
                        throw new InvalidArgumentException('Nguồn ảnh vượt quá giới hạn 5 lần chuyển hướng.');
                    }

                    $url = $this->normalizeUrl((string) UriResolver::resolve(new Uri($url), new Uri($location)), false);

                    continue;
                }

                if ($response->status() !== 200) {
                    $message = 'Không tải được ảnh gốc: máy chủ nguồn trả HTTP '.$response->status().'.';
                    throw new LegacyImageDownloadFailure($message, $url, 'http_'.$response->status(), $response->status() === 429 || $response->serverError());
                }
                clearstatcache(true, $temporary);
                if (! is_file($temporary) || filesize($temporary) < 1 || filesize($temporary) > $maximum) {
                    throw new InvalidArgumentException('Ảnh rỗng hoặc vượt quá dung lượng cho phép.');
                }

                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
                $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'][$mime] ?? null;
                if (! $extension) {
                    throw new LegacyImageDownloadFailure('Nguồn tải không trả ảnh JPEG, PNG, WebP hoặc AVIF thật (MIME: '.($mime ?: 'không xác định').'); không nhận SVG hoặc HTML.', $url, 'invalid_mime');
                }

                return new UploadedFile($temporary, 'legacy-import-'.hash_file('sha256', $temporary).'.'.$extension, $mime, null, true);
            }
        } catch (Throwable $exception) {
            if (is_file($temporary)) {
                unlink($temporary);
            }
            if ($exception instanceof InvalidArgumentException || $exception instanceof ValidationException) {
                throw $exception;
            }
            for ($cause = $exception->getPrevious(); $cause !== null; $cause = $cause->getPrevious()) {
                if ($cause instanceof InvalidArgumentException) {
                    throw new LegacyImageDownloadFailure($cause->getMessage(), $url, 'download_limit');
                }
            }

            preg_match('/cURL error (\d+)/', $exception->getMessage(), $matches);
            $code = (int) ($matches[1] ?? 0);
            $reason = match ($code) {
                6 => 'không phân giải được DNS',
                7 => 'không kết nối được máy chủ nguồn',
                28 => 'quá thời gian tải',
                35 => 'lỗi bắt tay TLS với máy chủ nguồn (cURL 35)',
                60 => 'không xác thực được chứng chỉ máy chủ nguồn (cURL 60); kiểm tra chứng chỉ nguồn và CA bundle trên server',
                77 => 'không đọc được CA bundle trên server (cURL 77)',
                default => 'lỗi kết nối hoặc giới hạn tải ảnh',
            };

            $detail = preg_replace_callback('~https?://[^\s)]+~i', fn (array $match): string => LegacyImageDownloadFailure::redactedUrl($match[0]), $exception->getMessage());

            throw new LegacyImageDownloadFailure('Tải ảnh thất bại: '.$reason.'.', $url, 'curl_'.$code, ! in_array($code, [60, 77], true), mb_substr((string) $detail, 0, 1000));
        }
    }

    protected function resolve(string $host): array
    {
        return $this->dnsAddresses($host) ?: $this->systemAddresses($host);
    }

    protected function dnsAddresses(string $host): array
    {
        return @parent::resolve($host);
    }

    protected function systemAddresses(string $host): array
    {
        return array_values(array_unique(@gethostbynamel($host) ?: []));
    }

    private function request(string $url, string $host, array $addresses, string $temporary, int $maximum, float $deadline): Response
    {
        $https = parse_url($url, PHP_URL_SCHEME) === 'https';
        $port = $https ? 443 : 80;
        $verify = trim((string) config('legacy_migration.media.ca_bundle', ''));

        if ($verify !== '' && (! is_file($verify) || ! is_readable($verify))) {
            throw new LegacyImageDownloadFailure('CA bundle migration không tồn tại hoặc không đọc được trên server.', $url, 'ca_bundle');
        }

        $addresses = array_slice($addresses, 0, 3);

        foreach ($addresses as $index => $ip) {
            for ($refererAttempt = 0; $refererAttempt < 2; $refererAttempt++) {
                $remaining = $deadline - microtime(true);

                if ($remaining <= 0) {
                    throw new LegacyImageDownloadFailure('Tải ảnh vượt quá ngân sách HTTP 25 giây.', $url, 'timeout', true);
                }

                $address = str_contains($ip, ':') ? '['.$ip.']' : $ip;
                $headers = ['User-Agent' => 'HaidangTravelLegacyMigration/1.0', 'Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/*;q=0.8'];

                if ($refererAttempt > 0) {
                    $headers['Referer'] = ($https ? 'https' : 'http').'://'.parse_url($url, PHP_URL_HOST).'/';
                }

                try {
                    $response = Http::connectTimeout(min(5, $remaining))->timeout($remaining)->withHeaders($headers)->withOptions([
                        'allow_redirects' => false,
                        'proxy' => '',
                        'verify' => $verify !== '' ? $verify : true,
                        'sink' => $temporary,
                        'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.$address], CURLOPT_PROTOCOLS => $https ? CURLPROTO_HTTPS : CURLPROTO_HTTP],
                        'on_headers' => function (ResponseInterface $response) use ($maximum): void {
                            if ((int) $response->getHeaderLine('Content-Length') > $maximum) {
                                throw new InvalidArgumentException('Ảnh vượt quá dung lượng cho phép.');
                            }
                        },
                        'progress' => function ($downloadTotal, $downloaded) use ($maximum): void {
                            if ($downloadTotal > $maximum || $downloaded > $maximum) {
                                throw new InvalidArgumentException('Ảnh vượt quá dung lượng cho phép.');
                            }
                        },
                    ])->get($url);
                } catch (Throwable $exception) {
                    preg_match('/cURL error (\d+)/', $exception->getMessage(), $matches);

                    if (in_array((int) ($matches[1] ?? 0), [7, 28, 35], true) && $index < count($addresses) - 1) {
                        break;
                    }

                    throw $exception;
                }

                if ($response->status() !== 403 || $refererAttempt > 0) {
                    return $response;
                }
            }
        }

        throw new LegacyImageDownloadFailure('Không kết nối được các IP công khai của nguồn ảnh.', $url, 'connect', true);
    }
}
