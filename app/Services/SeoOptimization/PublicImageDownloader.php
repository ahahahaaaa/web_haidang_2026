<?php

namespace App\Services\SeoOptimization;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class PublicImageDownloader
{
    public function download(string $url): UploadedFile
    {
        $host = $this->validatedHost($url);
        $addresses = $this->resolve($host);
        if ($addresses === [] || collect($addresses)->contains(fn (string $ip) => ! $this->isPublicIp($ip))) {
            throw ValidationException::withMessages(['image_url' => 'NEED_DATA: URL ảnh phải trỏ tới máy chủ HTTPS công khai.']);
        }

        abort_unless(extension_loaded('curl'), 503, 'Máy chủ cần PHP cURL để tải ảnh an toàn.');
        $maximum = $this->maxBytes();
        $temporary = tempnam(sys_get_temp_dir(), 'seo-image-');
        abort_if($temporary === false, 503, 'Không tạo được tệp ảnh tạm.');
        $address = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];

        try {
            $response = Http::connectTimeout(5)->timeout(25)->withOptions([
                'allow_redirects' => false,
                'proxy' => '',
                'sink' => $temporary,
                'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$address], CURLOPT_PROTOCOLS => CURLPROTO_HTTPS],
                'on_headers' => function (ResponseInterface $response) use ($maximum): void {
                    if ((int) $response->getHeaderLine('Content-Length') > $maximum) {
                        throw ValidationException::withMessages(['image_url' => 'Ảnh vượt quá dung lượng cho phép.']);
                    }
                },
                'progress' => function ($downloadTotal, $downloaded) use ($maximum): void {
                    if ($downloadTotal > $maximum || $downloaded > $maximum) {
                        throw ValidationException::withMessages(['image_url' => 'Ảnh vượt quá dung lượng cho phép.']);
                    }
                },
            ])->get($url);

            if (! $response->successful() || $response->status() !== 200) {
                throw ValidationException::withMessages(['image_url' => 'NEED_DATA: Không tải được ảnh gốc; cung cấp URL HTTPS trực tiếp, không chuyển hướng.']);
            }
            if (! is_file($temporary) || filesize($temporary) < 1 || filesize($temporary) > $maximum) {
                throw ValidationException::withMessages(['image_url' => 'Ảnh rỗng hoặc vượt quá dung lượng cho phép.']);
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
            $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (! $extension) {
                throw ValidationException::withMessages(['image_url' => 'Chỉ nhận ảnh JPEG, PNG hoặc WebP thật; không nhận SVG hay nội dung HTML.']);
            }

            return new UploadedFile($temporary, 'seo-import-'.hash_file('sha256', $temporary).'.'.$extension, $mime, null, true);
        } catch (Throwable $exception) {
            if (is_file($temporary)) {
                unlink($temporary);
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            throw ValidationException::withMessages(['image_url' => 'NEED_DATA: Tải ảnh thất bại hoặc bị chặn bởi giới hạn an toàn. Cung cấp URL ảnh trực tiếp khác.']);
        }
    }

    public function validatedHost(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['fragment'])
            || preg_match('/[\x00-\x20\\\\]/', $url) || strlen($url) > 2048
            || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages(['image_url' => 'NEED_DATA: Dùng URL HTTPS trực tiếp, không có tài khoản, cổng riêng hoặc fragment.']);
        }

        return strtolower(trim($parts['host'], '[]'));
    }

    protected function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        return array_values(array_unique(array_filter(array_map(fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null, $records ?: []))));
    }

    public function isPublicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        if (str_contains($ip, ':')) {
            $binary = inet_pton($ip);

            return $binary !== false && (ord($binary[0]) & 0xE0) === 0x20
                && ! str_starts_with(strtolower($ip), '2001:db8:')
                && ! str_starts_with(strtolower($ip), '2001:0:')
                && ! str_starts_with(strtolower($ip), '2002:');
        }

        $octets = array_map('intval', explode('.', $ip));

        return ! ($octets[0] === 100 && $octets[1] >= 64 && $octets[1] <= 127)
            && ! ($octets[0] === 198 && in_array($octets[1], [18, 19], true))
            && ! ($octets[0] === 192 && $octets[1] === 0)
            && ! ($octets[0] === 198 && $octets[1] === 51 && $octets[2] === 100)
            && ! ($octets[0] === 203 && $octets[1] === 0 && $octets[2] === 113);
    }

    public function maxBytes(): int
    {
        return max(1024, min(10485760, (int) config('seo_optimization.media_max_bytes', 10485760)));
    }
}
