<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Tests\TestCase;

class CustomerLoyaltyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_loyalty_page_renders_lookup_data_from_api(): void
    {
        $this->seedSiteSettings();
        $this->configureApi(['customer_loyalty.history_enabled' => true]);

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
            'https://loyalty.example.test/api/frontstore/customer-points*' => Http::response([
                'status' => 'success',
                'data' => [
                    'customer' => [
                        'id' => 123,
                        'fullname' => 'Nguyễn Văn A',
                        'phone' => '0909794299',
                        'point' => 215566,
                    ],
                    'orders' => [
                        [
                            'order_code' => 'HD001',
                            'tour_name' => 'Tour Đà Lạt 3N2Đ',
                            'departure_date' => '20/06/2026',
                            'earned_points' => 1500,
                            'status' => 'completed',
                        ],
                    ],
                    'redemption_history' => [
                        [
                            'gift_name' => 'Voucher 50.000đ',
                            'points' => 10000,
                            'status' => 'pending',
                            'created_at' => '01/05/2026',
                        ],
                    ],
                    'gifts' => [
                        [
                            'id' => 5,
                            'name' => 'Voucher giảm giá 50.000 cho mỗi khách',
                            'point' => 10000,
                            'image_url' => 'http://127.0.0.1:8282/voucher.jpg',
                        ],
                    ],
                    'lookup_token' => 'lookup-token-demo',
                ],
            ]),
        ]);

        $this->get(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->assertOk()
            ->assertSeeText('Nguyễn Văn A')
            ->assertSeeText('215.566')
            ->assertSeeText('Tour Đà Lạt 3N2Đ')
            ->assertSeeText('Voucher giảm giá 50.000 cho mỗi khách')
            ->assertSeeText('Chờ duyệt')
            ->assertDontSeeText('pending')
            ->assertSeeText('Đổi quà ngay')
            ->assertSee('href="#customer-loyalty-gifts"', false)
            ->assertSee('id="customer-loyalty-gifts"', false)
            ->assertSee('data-customer-loyalty-points-card', false)
            ->assertSee('data-customer-loyalty-redemptions', false)
            ->assertSee('data-customer-loyalty-gifts', false)
            ->assertSee('data-customer-loyalty-redemption-modal', false)
            ->assertSee('data-customer-loyalty-redemption-form', false)
            ->assertSee('data-customer-loyalty-gift-title="Voucher giảm giá 50.000 cho mỗi khách"', false)
            ->assertSee('data-customer-loyalty-phone="0909794299"', false)
            ->assertSee('src="https://loyalty.example.test/image/thumb/voucher.jpg"', false)
            ->assertSee('data-frontsite-recaptcha-action="customer_loyalty_redeem"', false)
            ->assertDontSee('data-frontsite-confirm-message', false)
            ->assertSessionHas('customer_loyalty.lookup_tokens.'.hash('sha256', '0909794299'), 'lookup-token-demo')
            ->assertDontSee('fresh-token', false)
            ->assertSee('name="robots" content="noindex,follow"', false);

        $this->assertSame('fresh-token', SiteSetting::query()->findOrFail(1)->customer_loyalty_api_token);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://loyalty.example.test/api/frontstore/customer-points')
                && $request->hasHeader('Authorization', 'Bearer fresh-token');
        });
    }

    public function test_customer_loyalty_history_sections_are_hidden_by_default(): void
    {
        $this->seedSiteSettings();
        $this->configureApi();

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
            'https://loyalty.example.test/api/frontstore/customer-points*' => Http::response([
                'status' => 'success',
                'data' => [
                    'customer' => [
                        'id' => 123,
                        'fullname' => 'Nguyễn Văn A',
                        'phone' => '0909794299',
                        'point' => 215566,
                    ],
                    'orders' => [
                        [
                            'order_code' => 'HD001',
                            'tour_name' => 'Tour Đà Lạt 3N2Đ',
                            'departure_date' => '20/06/2026',
                            'earned_points' => 1500,
                            'status' => 'completed',
                        ],
                    ],
                    'redemption_history' => [
                        [
                            'gift_name' => 'Voucher 50.000đ',
                            'points' => 10000,
                            'status' => 'pending',
                            'created_at' => '01/05/2026',
                        ],
                    ],
                    'gifts' => [
                        [
                            'id' => 5,
                            'name' => 'Voucher giảm giá 50.000 cho mỗi khách',
                            'point' => 10000,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->get(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->assertOk()
            ->assertSeeText('Nguyễn Văn A')
            ->assertSeeText('215.566')
            ->assertSeeText('Voucher giảm giá 50.000 cho mỗi khách')
            ->assertDontSeeText('Đơn hàng đã có')
            ->assertDontSeeText('Lịch sử đổi quà')
            ->assertDontSeeText('Tour Đà Lạt 3N2Đ')
            ->assertDontSeeText('Chờ duyệt')
            ->assertDontSee('data-customer-loyalty-orders', false)
            ->assertDontSee('data-customer-loyalty-redemptions', false)
            ->assertSee('data-customer-loyalty-gifts', false)
            ->assertSee('name="robots" content="noindex,follow"', false);
    }

    public function test_customer_loyalty_page_shows_gift_catalog_and_featured_tours_without_phone(): void
    {
        $this->seedSiteSettings();
        $this->configureApi();

        Tour::query()->create([
            'title' => 'Tour Phú Quốc nổi bật',
            'slug' => 'tour-phu-quoc-noi-bat',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'transport' => 'Máy bay',
            'departure_location' => 'TP.HCM',
            'duration_days' => 3,
            'duration_nights' => 2,
            'base_price' => 4990000,
            'is_featured' => true,
            'published_at' => now(),
        ]);
        Tour::query()->create([
            'title' => 'Tour Singapore nổi bật',
            'slug' => 'tour-singapore-noi-bat',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'transport' => 'Máy bay',
            'departure_location' => 'TP.HCM',
            'duration_days' => 4,
            'duration_nights' => 3,
            'base_price' => 12990000,
            'is_featured' => true,
            'published_at' => now(),
        ]);

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
            'https://loyalty.example.test/api/frontstore/gifts' => Http::response([
                'status' => 'success',
                'data' => [
                    'gifts' => [
                        [
                            'id' => 7,
                            'name' => 'Voucher khách mới 100.000đ',
                            'description' => 'Tham khảo trước khi tra cứu điểm.',
                            'point' => 12000,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->get(route('customer-loyalty.index'))
            ->assertOk()
            ->assertSeeText('Danh sách quà có thể đổi')
            ->assertSeeText('Voucher khách mới 100.000đ')
            ->assertSeeText('Nhập SĐT để đổi')
            ->assertSeeText('Tour nổi bật')
            ->assertSeeText('Tour Phú Quốc nổi bật')
            ->assertSeeText('Tour Singapore nổi bật')
            ->assertSee('data-card-carousel', false)
            ->assertSee('data-desktop-slider="true"', false)
            ->assertSee('--desktop-columns: 4', false)
            ->assertSee('--desktop-card-width: calc((100% - 3rem) / 4)', false)
            ->assertDontSee('data-customer-loyalty-redemption-form', false)
            ->assertDontSee('data-customer-loyalty-redemption-modal', false);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://loyalty.example.test/api/frontstore/gifts'
                && $request->hasHeader('Authorization', 'Bearer fresh-token');
        });

        Http::assertNotSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://loyalty.example.test/api/frontstore/customer-points');
        });
    }

    public function test_customer_loyalty_redeem_posts_pending_request_to_api(): void
    {
        $this->seedSiteSettings();
        $this->configureApi();

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
            'https://loyalty.example.test/api/frontstore/gift-redemption-requests' => Http::response([
                'status' => 'success',
                'message' => 'Đã tạo yêu cầu đổi quà chờ duyệt.',
                'data' => [
                    'request' => [
                        'id' => 88,
                        'approval_status' => 'pending',
                        'gift_title' => 'Voucher 100k',
                    ],
                ],
            ]),
        ]);

        $response = $this
            ->withSession(['customer_loyalty.lookup_tokens.'.hash('sha256', '0909794299') => 'lookup-token-demo'])
            ->post(route('customer-loyalty.redeem'), [
                'phone' => '0909794299',
                'gift_id' => 5,
                'gift_name' => 'Voucher giảm giá 50.000 cho mỗi khách',
                'customer_id' => 123,
                'amount' => 1,
            ]);

        $response
            ->assertRedirect(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->assertSessionHas('customer_loyalty_status', 'Đã tạo yêu cầu đổi quà chờ duyệt. Mã tham chiếu: 88.');

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://loyalty.example.test/api/frontstore/gift-redemption-requests'
                && $request->hasHeader('Authorization', 'Bearer fresh-token')
                && $request['customer_id'] === 123
                && $request['gift_id'] === 5
                && $request['amount'] === 1
                && ! isset($request['phone']);
        });
    }

    public function test_customer_loyalty_redeem_returns_json_for_ajax_request(): void
    {
        $this->seedSiteSettings();
        $this->configureApi();

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
            'https://loyalty.example.test/api/frontstore/gift-redemption-requests' => Http::response([
                'status' => 'success',
                'message' => 'Đã tạo yêu cầu đổi quà chờ duyệt.',
                'data' => [
                    'request' => [
                        'id' => 88,
                        'approval_status' => 'pending',
                        'gift_title' => 'Voucher 100k',
                    ],
                ],
            ]),
        ]);

        $response = $this
            ->withSession(['customer_loyalty.lookup_tokens.'.hash('sha256', '0909794299') => 'lookup-token-demo'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('customer-loyalty.redeem'), [
                'phone' => '0909794299',
                'gift_id' => 5,
                'gift_name' => 'Voucher giảm giá 50.000 cho mỗi khách',
                'customer_id' => 123,
                'amount' => 1,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Đã tạo yêu cầu đổi quà chờ duyệt. Mã tham chiếu: 88.',
                'refresh_url' => route('customer-loyalty.index', ['phone' => '0909794299']),
            ]);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://loyalty.example.test/api/frontstore/gift-redemption-requests'
                && $request->hasHeader('Authorization', 'Bearer fresh-token')
                && $request['customer_id'] === 123
                && $request['gift_id'] === 5
                && $request['amount'] === 1;
        });
    }

    public function test_customer_loyalty_redeem_requires_google_recaptcha_when_enabled(): void
    {
        $this->seedSiteSettings([
            'google_recaptcha_v3_enabled' => true,
            'google_recaptcha_v3_site_key' => 'site-key-demo',
            'google_recaptcha_v3_secret_key' => 'secret-key-demo',
        ]);
        $this->configureApi();

        Http::preventStrayRequests();

        $this
            ->withSession(['customer_loyalty.lookup_tokens.'.hash('sha256', '0909794299') => 'lookup-token-demo'])
            ->from(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->post(route('customer-loyalty.redeem'), [
                'phone' => '0909794299',
                'gift_id' => 5,
                'gift_name' => 'Voucher giảm giá 50.000 cho mỗi khách',
                'customer_id' => 123,
                'amount' => 1,
            ])
            ->assertRedirect(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->assertSessionHasErrors('redemption');
    }

    public function test_customer_loyalty_orders_are_paginated_by_ten_per_page(): void
    {
        $this->seedSiteSettings();
        $this->configureApi(['customer_loyalty.history_enabled' => true]);

        $orders = collect(range(1, 12))
            ->map(fn (int $index): array => [
                'order_code' => 'HD'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'tour_name' => 'Tour kiểm tra '.$index,
                'departure_date' => '2026-06-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'earned_points' => $index * 100,
                'status' => 'completed',
            ])
            ->all();

        Http::fake([
            'https://loyalty.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'token' => 'fresh-token',
                        'expires_in' => 3600,
                    ],
                ],
            ]),
            'https://loyalty.example.test/api/frontstore/customer-points*' => Http::response([
                'status' => 'success',
                'data' => [
                    'customer' => [
                        'id' => 123,
                        'fullname' => 'Nguyễn Văn A',
                        'phone' => '0909794299',
                        'point' => 215566,
                    ],
                    'orders' => $orders,
                    'redemption_history' => [],
                    'gifts' => [],
                ],
            ]),
        ]);

        $this->get(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->assertOk()
            ->assertSeeText('12 đơn')
            ->assertSeeText('Hiển thị 1-10 / 12 đơn.')
            ->assertSeeText('HD001')
            ->assertSeeText('HD010')
            ->assertDontSeeText('HD011')
            ->assertSee('orders_page=2', false);

        $this->get(route('customer-loyalty.index', ['phone' => '0909794299', 'orders_page' => 2]))
            ->assertOk()
            ->assertSeeText('Hiển thị 11-12 / 12 đơn.')
            ->assertSeeText('HD011')
            ->assertSeeText('HD012')
            ->assertDontSeeText('HD001');
    }

    public function test_customer_loyalty_local_api_falls_back_to_loopback_host(): void
    {
        $this->seedSiteSettings([
            'customer_loyalty_api_base_url' => 'https://loyalty.local/api',
        ]);
        $this->configureApi();

        Http::fake(function ($request) {
            if (str_starts_with($request->url(), 'https://loyalty.local/api/')) {
                throw new ConnectionException('Could not connect to local hostname.');
            }

            if ($request->url() === 'https://127.0.0.1/api/DashboardLogin') {
                return Http::response([
                    'status' => 'success',
                    'data' => [
                        [
                            'token' => 'loopback-token',
                            'expires_in' => 3600,
                        ],
                    ],
                ]);
            }

            if (str_starts_with($request->url(), 'https://127.0.0.1/api/frontstore/customer-points')) {
                return Http::response([
                    'status' => 'success',
                    'data' => [
                        'customer' => [
                            'id' => 123,
                            'fullname' => 'Nguyễn Văn Loopback',
                            'phone' => '0909794299',
                            'point' => 215566,
                        ],
                    ],
                ]);
            }

            return Http::response(['status' => 'error'], 404);
        });

        $this->get(route('customer-loyalty.index', ['phone' => '0909794299']))
            ->assertOk()
            ->assertSeeText('Nguyễn Văn Loopback')
            ->assertSeeText('215.566');

        $this->assertSame('loopback-token', SiteSetting::query()->findOrFail(1)->customer_loyalty_api_token);
    }

    private function configureApi(array $overrides = []): void
    {
        config(array_merge([
            'customer_loyalty.base_url' => 'https://loyalty.example.test',
            'customer_loyalty.lookup_path' => '/frontstore/customer-points',
            'customer_loyalty.lookup_method' => 'GET',
            'customer_loyalty.gifts_path' => '/frontstore/gifts',
            'customer_loyalty.gifts_method' => 'GET',
            'customer_loyalty.redeem_path' => '/frontstore/gift-redemption-requests',
            'customer_loyalty.redeem_method' => 'POST',
            'customer_loyalty.login_path' => '/DashboardLogin',
            'customer_loyalty.verify_ssl' => true,
            'customer_loyalty.token' => null,
            'customer_loyalty.history_enabled' => false,
        ], $overrides));
    }

    private function seedSiteSettings(array $overrides = []): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            array_merge([
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Tư vấn tour và dịch vụ du lịch.',
                'seo_description' => 'Tư vấn tour và dịch vụ du lịch.',
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'frontsite@example.com',
                'mail_contact_recipient' => 'sales@example.com',
                'customer_loyalty_api_base_url' => 'https://loyalty.example.test/api',
                'customer_loyalty_api_username' => 'staff@example.com',
                'customer_loyalty_api_password' => 'secret',
            ], $overrides),
        );
    }
}
