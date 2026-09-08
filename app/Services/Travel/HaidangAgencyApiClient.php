<?php

namespace App\Services\Travel;

use App\Services\Cms\SiteSettingsManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\SiteSetting;
use Throwable;

class HaidangAgencyApiClient
{
    public function __construct(protected SiteSettingsManager $siteSettings) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function tours(array $filters = []): array
    {
        return $this->send('GET', (string) config('tour_sync.tours_path'), $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function startdates(array $filters = []): array
    {
        return $this->send('GET', (string) config('tour_sync.startdates_path'), $filters);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function pushTourUpdate(array $payload): array
    {
        $path = trim((string) config('tour_sync.push_path'));

        if ($path === '') {
            throw new HaidangAgencyApiException('Chưa cấu hình endpoint nhận đồng bộ tour. Kiểm tra TOUR_SYNC_PUSH_PATH.', [
                'remote_path' => $path,
            ]);
        }

        $response = $this->send('POST', $path, $payload);
        $status = Str::lower((string) ($this->firstString($response, ['status', 'data.status']) ?? ''));

        if ($response === [] || ! in_array($status, ['success', 'ok'], true)) {
            throw new HaidangAgencyApiException('Cổng API đồng bộ tour trả về phản hồi không hợp lệ hoặc thiếu trạng thái success.', [
                'remote_path' => $path,
                'remote_response' => $response,
                'remote_business_status' => $status ?: 'missing',
            ]);
        }

        return $response;
    }

    public function configured(): bool
    {
        return $this->baseUrl() !== '' && ($this->storedBearerTokenIsFresh() || $this->hasLoginCredentials());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $payload = [], bool $retryAfterUnauthorized = true): array
    {
        if (! $this->configured()) {
            throw new HaidangAgencyApiException('Chưa cấu hình cổng API đồng bộ khởi hành trong Theme Settings.');
        }

        $response = $this->dispatch($method, $path, $payload, $this->bearerToken());

        if ($response->status() === 401 && $retryAfterUnauthorized && $this->hasLoginCredentials()) {
            Cache::forget($this->tokenCacheKey());
            $response = $this->dispatch($method, $path, $payload, $this->loginToken(force: true));
        }

        if ($response->failed()) {
            throw new HaidangAgencyApiException($this->errorMessage($response), $this->responseContext($response, $method, $path));
        }

        $json = $this->json($response);
        $this->ensureBusinessSuccess($json);

        return $json;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatch(string $method, string $path, array $payload, string $token): Response
    {
        $request = $this->baseRequest()->withToken($token);

        try {
            return match (Str::upper($method)) {
                'POST' => $request->post($this->url($path), $payload),
                default => $request->get($this->url($path), $payload),
            };
        } catch (ConnectionException $exception) {
            report($exception);

            throw new HaidangAgencyApiException('Không kết nối được cổng API đồng bộ khởi hành.', [
                'remote_method' => Str::upper($method),
                'remote_path' => $path,
                'remote_url' => $this->url($path),
            ]);
        }
    }

    protected function bearerToken(): string
    {
        if ($this->storedBearerTokenIsFresh()) {
            return (string) $this->storedBearerToken();
        }

        return $this->loginToken();
    }

    protected function loginToken(bool $force = false): string
    {
        if ($force) {
            Cache::forget($this->tokenCacheKey());
        }

        if (! $this->hasLoginCredentials()) {
            $fallbackToken = $this->fallbackBearerToken();

            if ($fallbackToken !== null) {
                return $fallbackToken;
            }

            throw new HaidangAgencyApiException('Chưa cấu hình tài khoản đăng nhập API đồng bộ khởi hành trong Theme Settings.');
        }

        if (! $force && $this->storedBearerTokenIsFresh()) {
            return (string) $this->storedBearerToken();
        }

        return Cache::remember($this->tokenCacheKey(), now()->addMinutes((int) config('tour_sync.token_ttl_minutes', 55)), function (): string {
            $response = $this->baseRequest()->post($this->url((string) config('tour_sync.login_path')), [
                'UserName' => $this->username(),
                'Password' => $this->password(),
            ]);

            if ($response->failed()) {
                throw new HaidangAgencyApiException(
                    $this->errorMessage($response),
                    $this->responseContext($response, 'POST', (string) config('tour_sync.login_path')),
                );
            }

            $payload = $this->json($response);
            $token = $this->tokenFromLoginPayload($payload);

            if ($token === null) {
                throw new HaidangAgencyApiException('Cổng API đồng bộ không trả về bearer token hợp lệ.');
            }

            $this->storeBearerToken($token, $payload);

            return $token;
        });
    }

    protected function baseRequest(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) config('tour_sync.connect_timeout_seconds', 5))
            ->timeout((int) config('tour_sync.timeout_seconds', 20))
            ->retry(1, 250);

        if (! (bool) config('tour_sync.verify_ssl', true)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    protected function errorMessage(Response $response): string
    {
        $payload = $this->json($response);
        $message = $this->firstString($payload, [
            'message',
            'error',
            'data.message',
            'data.0.message',
            'errors.0',
        ]);

        if ($message !== null) {
            return $message;
        }

        return match ($response->status()) {
            401 => 'Token API đồng bộ không hợp lệ hoặc đã hết hạn.',
            403 => 'Tài khoản API không có quyền đồng bộ tour.',
            404 => 'Không tìm thấy endpoint API đồng bộ khởi hành.',
            default => 'Cổng API đồng bộ khởi hành trả về lỗi '.$response->status().'.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseContext(Response $response, string $method, string $path): array
    {
        return [
            'remote_method' => Str::upper($method),
            'remote_path' => $path,
            'remote_url' => $this->url($path),
            'remote_http_status' => $response->status(),
            'remote_response' => $this->json($response),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(Response $response): array
    {
        try {
            $payload = $response->json();
        } catch (Throwable) {
            $payload = [];
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function ensureBusinessSuccess(array $payload): void
    {
        $status = Str::lower((string) ($this->firstString($payload, ['status', 'data.status']) ?? ''));

        if ($status !== '' && ! in_array($status, ['success', 'ok'], true)) {
            throw new HaidangAgencyApiException(
                $this->firstString($payload, ['message', 'error', 'data.message'])
                    ?: 'Cổng API đồng bộ trả về trạng thái chưa thành công.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function tokenFromLoginPayload(array $payload): ?string
    {
        return $this->firstString($payload, [
            'data.token',
            'data.0.token',
            'data.access_token',
            'data.0.access_token',
            'data.bearer_token',
            'data.0.bearer_token',
            'data.authorization.token',
            'data.0.authorization.token',
            'token',
            'access_token',
            'bearer_token',
            'authorization.token',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    protected function firstString(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    protected function url(string $path): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    protected function baseUrl(): string
    {
        $baseUrl = $this->settings()->customer_loyalty_api_base_url ?: config('tour_sync.base_url');

        return rtrim(trim((string) $baseUrl), '/');
    }

    protected function settingsBearerToken(): ?string
    {
        $token = $this->settings()->customer_loyalty_api_token;

        if (is_scalar($token) && trim((string) $token) !== '') {
            return trim((string) $token);
        }

        return null;
    }

    protected function fallbackBearerToken(): ?string
    {
        $token = config('tour_sync.token');

        if (is_scalar($token) && trim((string) $token) !== '') {
            return trim((string) $token);
        }

        return null;
    }

    protected function storedBearerToken(): ?string
    {
        return $this->settingsBearerToken() ?: $this->fallbackBearerToken();
    }

    protected function storedBearerTokenIsFresh(): bool
    {
        $settingsToken = $this->settingsBearerToken();

        if ($settingsToken !== null) {
            $expiresAt = $this->settings()->customer_loyalty_api_token_expires_at;

            if (! $expiresAt) {
                return true;
            }

            $bufferSeconds = (int) config('customer_loyalty.token_refresh_buffer_seconds', 300);

            return now()->addSeconds($bufferSeconds)->lt($expiresAt);
        }

        return $this->fallbackBearerToken() !== null;
    }

    protected function username(): string
    {
        return trim((string) ($this->settings()->customer_loyalty_api_username ?: config('tour_sync.username')));
    }

    protected function password(): string
    {
        return trim((string) ($this->settings()->customer_loyalty_api_password ?: config('tour_sync.password')));
    }

    protected function hasLoginCredentials(): bool
    {
        return $this->username() !== '' && $this->password() !== '';
    }

    protected function settings(): SiteSetting
    {
        return $this->siteSettings->current();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function storeBearerToken(string $token, array $payload): void
    {
        $settings = $this->settings();

        if (! $settings->exists) {
            return;
        }

        $settings->forceFill([
            'customer_loyalty_api_token' => $token,
            'customer_loyalty_api_token_expires_at' => $this->resolveTokenExpiresAt($payload),
            'customer_loyalty_api_token_refreshed_at' => now(),
        ])->saveQuietly();

        $this->siteSettings->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveTokenExpiresAt(array $payload): \DateTimeInterface
    {
        $expiresIn = $this->firstInt($payload, [
            'data.expires_in',
            'data.0.expires_in',
            'expires_in',
        ]);

        if ($expiresIn !== null && $expiresIn > 0) {
            return now()->addSeconds($expiresIn);
        }

        return now()->addMinutes((int) config('tour_sync.token_ttl_minutes', 55));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    protected function firstInt(array $payload, array $paths): ?int
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    protected function tokenCacheKey(): string
    {
        return 'tour-sync-api-token:'.sha1($this->baseUrl().':'.$this->username());
    }
}
