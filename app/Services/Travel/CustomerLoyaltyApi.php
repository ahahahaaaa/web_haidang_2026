<?php

namespace App\Services\Travel;

use App\Services\Cms\SiteSettingsManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\SiteSetting;
use Throwable;

class CustomerLoyaltyApi
{
    public function __construct(protected SiteSettingsManager $siteSettings) {}

    public function configured(): bool
    {
        return filled($this->baseUrl()) && (filled($this->storedBearerToken()) || $this->hasLoginCredentials());
    }

    public function refreshToken(bool $force = false): string
    {
        if (! $force && $this->storedBearerTokenIsFresh()) {
            return (string) $this->settings()->customer_loyalty_api_token;
        }

        if (! $this->hasLoginCredentials()) {
            $fallbackToken = trim((string) config('customer_loyalty.token'));

            if ($fallbackToken !== '') {
                return $fallbackToken;
            }

            throw new CustomerLoyaltyApiException('Chưa cấu hình tài khoản đăng nhập API quà trong Theme Settings.');
        }

        $request = $this->baseRequest();
        $lastResponse = null;

        foreach ($this->loginPayloads() as $payload) {
            try {
                $response = $this->sendRequestWithLocalFallback(
                    $request,
                    'POST',
                    (string) config('customer_loyalty.login_path', '/DashboardLogin'),
                    $payload,
                );
            } catch (ConnectionException $exception) {
                report($exception);

                throw new CustomerLoyaltyApiException('Không kết nối được cổng API quà. Vui lòng thử lại sau.');
            }

            $lastResponse = $response;

            if ($response->failed() && ! in_array($response->status(), [400, 401, 422], true)) {
                break;
            }

            $json = $this->json($response);
            $token = $this->tokenFromLoginPayload($json);

            if ($response->successful() && filled($token)) {
                $this->storeBearerToken((string) $token, $json);

                return (string) $token;
            }
        }

        throw new CustomerLoyaltyApiException(
            $lastResponse instanceof Response
                ? $this->errorMessage($lastResponse)
                : 'Không lấy được token API quà. Vui lòng kiểm tra tài khoản trong Theme Settings.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $phone): array
    {
        $payload = $this->json($this->send(
            (string) config('customer_loyalty.lookup_method', 'GET'),
            (string) config('customer_loyalty.lookup_path', '/frontstore/customer-points'),
            ['phone' => $phone],
        ));

        $this->ensureBusinessSuccess($payload);

        return $this->normalizeLookupPayload($payload, $phone);
    }

    /**
     * @return array<string, mixed>
     */
    public function giftCatalog(int $customerPoints = 0): array
    {
        $payload = $this->json($this->send(
            (string) config('customer_loyalty.gifts_method', 'GET'),
            (string) config('customer_loyalty.gifts_path', '/frontstore/gifts'),
            [],
        ));

        $this->ensureBusinessSuccess($payload);

        return [
            'gifts' => $this->normalizeGifts($this->firstList($payload, $this->giftPaths()), $customerPoints),
            'sections' => [
                'gifts' => $this->hasAnyPath($payload, $this->giftPaths()),
            ],
            'raw' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function requestRedemption(array $validated): array
    {
        $payload = [
            'gift_id' => $this->numericOrString($validated['gift_id']),
            'amount' => (int) ($validated['amount'] ?? 1),
        ];

        if (filled($validated['customer_id'] ?? null)) {
            $payload['customer_id'] = $this->numericOrString($validated['customer_id']);
        } else {
            $payload['phone'] = $validated['phone'];
        }

        if (filled($validated['expire'] ?? null)) {
            $payload['expire'] = $validated['expire'];
        }

        $response = $this->json($this->send(
            (string) config('customer_loyalty.redeem_method', 'POST'),
            (string) config('customer_loyalty.redeem_path', '/frontstore/gift-redemption-requests'),
            array_filter($payload, fn (mixed $value): bool => filled($value)),
        ));

        $this->ensureBusinessSuccess($response);

        return [
            'message' => $this->firstString($response, [
                'message',
                'data.message',
                'result.message',
            ]) ?: 'Yêu cầu đổi quà đã được ghi nhận. Hải Đăng Travel sẽ liên hệ để xác nhận.',
            'reference' => $this->firstString($response, [
                'data.request.id',
                'data.request.code',
                'data.reference',
                'data.code',
                'reference',
                'code',
                'id',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeLookupPayload(array $payload, string $phone): array
    {
        $data = $this->dataEnvelope($payload);
        $customer = $this->firstArray($payload, [
            'data.customer',
            'customer',
            'data.user',
            'user',
        ]) ?: $data;

        $points = $this->firstInt($payload, [
            'data.points',
            'data.total_points',
            'data.total_point',
            'data.point',
            'data.customer.points',
            'data.customer.point',
            'customer.points',
            'customer.point',
            'points',
            'total_points',
            'total_point',
            'point',
        ]) ?? 0;
        $orderPaths = [
            'data.orders',
            'data.order_list',
            'data.customer.orders',
            'orders',
            'order_list',
        ];
        $redemptionPaths = [
            'data.redemptions',
            'data.redemption_history',
            'data.redeem_history',
            'data.history',
            'data.customer.redemptions',
            'redemptions',
            'redemption_history',
            'redeem_history',
            'history',
        ];
        $giftPaths = $this->giftPaths();

        return [
            'customer' => [
                'id' => $this->firstString(['customer' => $customer], ['customer.id', 'customer.customer_id', 'customer.user_id']),
                'name' => $this->firstString(['customer' => $customer], ['customer.fullname', 'customer.full_name', 'customer.name', 'customer.customer_name']),
                'phone' => $this->firstString(['customer' => $customer], ['customer.phone', 'customer.customer_phone', 'customer.mobile']) ?: $phone,
                'email' => $this->firstString(['customer' => $customer], ['customer.email', 'customer.customer_email']),
                'member_card' => $this->firstString(['customer' => $customer], ['customer.member_card']),
                'member_card_type' => $this->firstString(['customer' => $customer], ['customer.member_card_type']),
            ],
            'points' => $points,
            'orders' => $this->normalizeOrders($this->firstList($payload, $orderPaths)),
            'redemptions' => $this->normalizeRedemptions($this->firstList($payload, $redemptionPaths)),
            'gifts' => $this->normalizeGifts($this->firstList($payload, $giftPaths), $points),
            'sections' => [
                'orders' => $this->hasAnyPath($payload, $orderPaths),
                'redemptions' => $this->hasAnyPath($payload, $redemptionPaths),
                'gifts' => $this->hasAnyPath($payload, $giftPaths),
            ],
            'lookup_token' => $this->firstString($payload, [
                'data.lookup_token',
                'data.token',
                'lookup_token',
                'token',
            ]),
            'raw' => $payload,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function giftPaths(): array
    {
        return [
            'data.gifts',
            'data.available_gifts',
            'data.rewards',
            'gifts',
            'available_gifts',
            'rewards',
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeOrders(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item): array {
                return [
                    'id' => $this->firstString(['item' => $item], ['item.id', 'item.order_id', 'item.code', 'item.order_code']),
                    'code' => $this->firstString(['item' => $item], ['item.code', 'item.order_code', 'item.booking_code']) ?: 'Đơn hàng',
                    'title' => $this->firstString(['item' => $item], ['item.title', 'item.name', 'item.tour_name', 'item.service_name']) ?: 'Chương trình du lịch',
                    'date' => $this->firstString(['item' => $item], ['item.date', 'item.departure_date', 'item.start_date', 'item.created_at']),
                    'status' => $this->firstString(['item' => $item], ['item.status', 'item.order_status', 'item.state']),
                    'total' => $this->firstNumeric(['item' => $item], ['item.total', 'item.amount', 'item.grand_total', 'item.payment']),
                    'points' => $this->firstInt(['item' => $item], ['item.points', 'item.point', 'item.earned_points']),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRedemptions(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item): array {
                $status = $this->firstString(['item' => $item], ['item.approval_status', 'item.status', 'item.state']);

                return [
                    'id' => $this->firstString(['item' => $item], ['item.id', 'item.code', 'item.redemption_code']),
                    'gift_name' => $this->firstString(['item' => $item], ['item.gift_title', 'item.gift_name', 'item.reward_name', 'item.name', 'item.title']) ?: 'Quà tặng',
                    'points' => $this->firstInt(['item' => $item], ['item.required_point', 'item.required_points', 'item.points', 'item.point']),
                    'status' => $status,
                    'status_label' => $this->redemptionStatusLabel($status),
                    'requested_at' => $this->firstString(['item' => $item], ['item.requested_at', 'item.created_at', 'item.date']),
                    'note' => $this->firstString(['item' => $item], ['item.note', 'item.message']),
                ];
            })
            ->values()
            ->all();
    }

    protected function redemptionStatusLabel(?string $status): string
    {
        $rawStatus = trim((string) $status);

        if ($rawStatus === '') {
            return 'Đang cập nhật';
        }

        $normalized = Str::of($rawStatus)
            ->lower()
            ->replace(['_', ' '], '-')
            ->value();

        return match ($normalized) {
            '0', 'pending', 'pending-approval', 'waiting', 'waiting-approval', 'wait-confirm', 'waiting-confirmation' => 'Chờ duyệt',
            '1', 'approved', 'approve', 'accepted', 'confirmed' => 'Đã duyệt',
            '2', 'rejected', 'reject', 'declined', 'deny', 'denied' => 'Từ chối',
            '3', 'cancelled', 'canceled', 'cancel' => 'Đã hủy',
            '4', 'completed', 'complete', 'done', 'success', 'fulfilled' => 'Hoàn tất',
            'processing', 'in-progress', 'contacting' => 'Đang xử lý',
            default => $rawStatus,
        };
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeGifts(array $items, int $customerPoints): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item) use ($customerPoints): array {
                $requiredPoints = $this->firstInt(['item' => $item], [
                    'item.required_points',
                    'item.required_point',
                    'item.points',
                    'item.point',
                    'item.price_points',
                    'item.exchange_points',
                ]) ?? 0;
                $apiCanRedeem = data_get($item, 'can_redeem');

                return [
                    'id' => $this->firstString(['item' => $item], ['item.id', 'item.gift_id', 'item.reward_id', 'item.code']),
                    'title' => $this->firstString(['item' => $item], ['item.title', 'item.name', 'item.gift_title', 'item.gift_name', 'item.reward_name']) ?: 'Quà tặng',
                    'description' => $this->firstString(['item' => $item], ['item.description', 'item.summary', 'item.note']),
                    'image_url' => $this->normalizeApiAssetUrl($this->firstString(['item' => $item], [
                        'item.image_url',
                        'item.image',
                        'item.thumbnail_url',
                        'item.thumbnail',
                        'item.photo_url',
                        'item.photo',
                    ])),
                    'required_points' => $requiredPoints,
                    'can_redeem' => is_bool($apiCanRedeem) ? $apiCanRedeem : $customerPoints >= $requiredPoints,
                    'status' => $this->firstString(['item' => $item], ['item.status', 'item.state']),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function send(string $method, string $path, array $payload): Response
    {
        if (! $this->configured()) {
            throw new CustomerLoyaltyApiException('Cổng API quà chưa được cấu hình trong Theme Settings.');
        }

        $response = $this->makeRequest($method, $path, $payload, $this->bearerToken());

        if ($response->status() === 401 && $this->hasLoginCredentials()) {
            $response = $this->makeRequest($method, $path, $payload, $this->refreshToken(force: true));
        }

        if ($response->failed()) {
            throw new CustomerLoyaltyApiException($this->errorMessage($response));
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function makeRequest(string $method, string $path, array $payload, string $token): Response
    {
        $request = $this->baseRequest()->withHeaders($this->authHeaders($token));

        try {
            return $this->sendRequestWithLocalFallback($request, $method, $path, $payload);
        } catch (ConnectionException $exception) {
            report($exception);

            throw new CustomerLoyaltyApiException('Không kết nối được cổng API quà. Vui lòng thử lại sau.');
        }
    }

    protected function baseRequest(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) config('customer_loyalty.connect_timeout_seconds', 5))
            ->timeout((int) config('customer_loyalty.timeout_seconds', 20))
            ->retry(1, 250);

        if (! (bool) config('customer_loyalty.verify_ssl', true)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendRequestWithLocalFallback(PendingRequest $request, string $method, string $path, array $payload): Response
    {
        $url = $this->url($path);

        try {
            return $this->dispatchRequest($request, $method, $url, $payload);
        } catch (ConnectionException $exception) {
            $fallbackUrl = $this->localFallbackUrl($path);

            if (! $fallbackUrl || $fallbackUrl === $url) {
                throw $exception;
            }

            return $this->dispatchRequest($request, $method, $fallbackUrl, $payload);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchRequest(PendingRequest $request, string $method, string $url, array $payload): Response
    {
        return match (Str::upper($method)) {
            'GET' => $request->get($url, $payload),
            'PUT' => $request->put($url, $payload),
            'PATCH' => $request->patch($url, $payload),
            default => $request->post($url, $payload),
        };
    }

    protected function bearerToken(): string
    {
        if ($this->storedBearerTokenIsFresh()) {
            return (string) $this->settings()->customer_loyalty_api_token;
        }

        return $this->refreshToken();
    }

    protected function storedBearerToken(): ?string
    {
        $token = $this->settings()->customer_loyalty_api_token;

        if (filled($token)) {
            return trim((string) $token);
        }

        $fallbackToken = trim((string) config('customer_loyalty.token'));

        return $fallbackToken !== '' ? $fallbackToken : null;
    }

    protected function storedBearerTokenIsFresh(): bool
    {
        $token = $this->storedBearerToken();

        if (blank($token)) {
            return false;
        }

        $expiresAt = $this->settings()->customer_loyalty_api_token_expires_at;

        if (! $expiresAt) {
            return true;
        }

        $bufferSeconds = (int) config('customer_loyalty.token_refresh_buffer_seconds', 300);

        return now()->addSeconds($bufferSeconds)->lt($expiresAt);
    }

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

    protected function resolveTokenExpiresAt(array $payload): \DateTimeInterface
    {
        $expiresIn = $this->firstInt($payload, [
            'data.expires_in',
            'data.0.expires_in',
            'expires_in',
            'data.ttl',
            'data.0.ttl',
            'ttl',
        ]);

        if ($expiresIn && $expiresIn > 0) {
            return now()->addSeconds($expiresIn);
        }

        $expiresAt = $this->firstString($payload, [
            'data.expires_at',
            'data.0.expires_at',
            'expires_at',
            'data.expired_at',
            'data.0.expired_at',
            'expired_at',
        ]);

        if ($expiresAt) {
            try {
                return Carbon::parse($expiresAt);
            } catch (Throwable) {
                // Fall back to the configured TTL below.
            }
        }

        return now()->addMinutes((int) config('customer_loyalty.token_ttl_minutes', 55));
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function loginPayloads(): array
    {
        $username = $this->username();
        $password = $this->password();
        $primaryKey = str_contains($username, '@') ? 'email' : 'username';

        return collect([
            ['UserName' => $username, 'Password' => $password],
            [$primaryKey => $username, 'password' => $password],
            ['email' => $username, 'password' => $password],
            ['username' => $username, 'password' => $password],
            ['account' => $username, 'password' => $password],
        ])
            ->unique(fn (array $payload): string => implode('|', array_keys($payload)))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(string $token): array
    {
        $header = trim((string) config('customer_loyalty.token_header', 'Authorization'));
        $prefix = trim((string) config('customer_loyalty.token_prefix', 'Bearer'));

        if ($token === '' || $header === '') {
            return [];
        }

        return [
            $header => trim(($prefix !== '' ? $prefix.' ' : '').$token),
        ];
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
            'errors.email.0',
            'errors.username.0',
            'errors.password.0',
        ]);

        if ($message) {
            return $message;
        }

        if ($response->status() === 401) {
            return 'Token API quà không hợp lệ hoặc đã hết hạn. Hệ thống đã thử đăng nhập lại nhưng chưa thành công.';
        }

        return 'Cổng API quà trả về lỗi. Vui lòng thử lại sau.';
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
            throw new CustomerLoyaltyApiException(
                $this->firstString($payload, ['message', 'error', 'data.message'])
                    ?: 'Cổng API quà trả về trạng thái chưa thành công.',
            );
        }
    }

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

    protected function url(string $path): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    protected function localFallbackUrl(string $path): ?string
    {
        $parts = parse_url($this->baseUrl());

        if (! is_array($parts)) {
            return null;
        }

        $host = (string) ($parts['host'] ?? '');

        if ($host === '' || ! Str::endsWith($host, '.local')) {
            return null;
        }

        $scheme = (string) ($parts['scheme'] ?? 'http');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $basePath = trim((string) ($parts['path'] ?? ''), '/');
        $requestPath = trim($path, '/');
        $fullPath = collect([$basePath, $requestPath])
            ->filter(fn (string $segment): bool => $segment !== '')
            ->implode('/');

        return $scheme.'://127.0.0.1'.$port.($fullPath !== '' ? '/'.$fullPath : '');
    }

    protected function normalizeApiAssetUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (Str::startsWith(Str::lower($url), ['http://', 'https://'])) {
            return $this->normalizeAbsoluteApiAssetUrl($url);
        }

        if (Str::startsWith(Str::lower($url), 'data:image/')) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            $scheme = parse_url($this->baseUrl(), PHP_URL_SCHEME) ?: 'https';

            return $scheme.':'.$url;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            return null;
        }

        return rtrim($this->apiOriginUrl(), '/').'/'.ltrim($url, '/');
    }

    protected function normalizeAbsoluteApiAssetUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (! $this->shouldRouteThroughApiThumb($host, $path)) {
            return $url;
        }

        return rtrim($this->apiOriginUrl(), '/').'/image/thumb/'.rawurlencode(basename($path));
    }

    protected function shouldRouteThroughApiThumb(string $host, string $path): bool
    {
        if ($host === '' || $path === '' || Str::startsWith($path, '/image/')) {
            return false;
        }

        $apiHost = Str::lower((string) (parse_url($this->apiOriginUrl(), PHP_URL_HOST) ?: ''));
        $isApiHost = $host === $apiHost;
        $isLocalHost = in_array($host, ['127.0.0.1', 'localhost'], true);
        $isRootImageFile = ltrim($path, '/') === basename($path)
            && preg_match('/\.(jpe?g|png|webp|gif)$/i', $path);

        return ($isApiHost || $isLocalHost) && (bool) $isRootImageFile;
    }

    protected function apiOriginUrl(): string
    {
        $baseUrl = $this->baseUrl();
        $parts = parse_url($baseUrl);

        if (! is_array($parts) || blank($parts['host'] ?? null)) {
            return rtrim($baseUrl, '/');
        }

        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$host.$port;
    }

    protected function baseUrl(): string
    {
        $baseUrl = $this->settings()->customer_loyalty_api_base_url ?: config('customer_loyalty.base_url');

        return rtrim(trim((string) $baseUrl), '/');
    }

    protected function username(): string
    {
        return trim((string) ($this->settings()->customer_loyalty_api_username ?: config('customer_loyalty.username')));
    }

    protected function password(): string
    {
        return trim((string) ($this->settings()->customer_loyalty_api_password ?: config('customer_loyalty.password')));
    }

    protected function hasLoginCredentials(): bool
    {
        return filled($this->username()) && filled($this->password());
    }

    protected function settings(): SiteSetting
    {
        return $this->siteSettings->current();
    }

    protected function numericOrString(mixed $value): int|string
    {
        return is_numeric($value) ? (int) $value : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function dataEnvelope(array $payload): array
    {
        $data = data_get($payload, 'data');

        return is_array($data) ? $data : $payload;
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

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    protected function firstNumeric(array $payload, array $paths): int|float|string|null
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_numeric($value)) {
                return $value + 0;
            }

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
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

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     * @return array<string, mixed>|null
     */
    protected function firstArray(array $payload, array $paths): ?array
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_array($value) && ! array_is_list($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     * @return array<int, mixed>
     */
    protected function firstList(array $payload, array $paths): array
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_array($value)) {
                return array_is_list($value) ? $value : Arr::wrap($value);
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    protected function hasAnyPath(array $payload, array $paths): bool
    {
        foreach ($paths as $path) {
            if (Arr::has($payload, $path)) {
                return true;
            }
        }

        return false;
    }
}
