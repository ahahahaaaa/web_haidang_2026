<?php

namespace Tests\Feature;

use App\Jobs\Travel\PushTourToAgencyJob;
use App\Models\User;
use App\Services\Cms\SiteSettingsManager;
use App\Services\Travel\HaidangAgencyApiException;
use App\Services\Travel\HaidangAgencyApiClient;
use App\Services\Travel\TourAgencyPushSyncRunService;
use App\Services\Travel\TourAgencyPushSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourAgencyPushSyncRun;
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Tests\TestCase;

class AgencyTourPushSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_sync_posts_tour_departure_payload_and_stores_returned_startdate_mapping(): void
    {
        $this->configureAgencySyncSettings();

        $manager = User::query()->create([
            'name' => 'Sale CMS',
            'email' => 'sale-cms@example.test',
            'phone' => '0911 222 333',
            'password' => 'password',
            'is_active' => true,
        ]);
        $tour = Tour::query()->create([
            'title' => 'Tour Đà Lạt cập nhật từ CMS',
            'slug' => 'tour-da-lat-cap-nhat-tu-cms',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'managed_by_user_id' => $manager->getKey(),
            'excerpt' => 'Lịch trình đã được CMS cập nhật.',
            'transport' => 'Xe du lịch',
            'standard_label' => 'Khách sạn 3 sao',
            'duration_days' => 4,
            'duration_nights' => 3,
            'base_price' => 5990000,
            'sale_price' => 5490000,
        ]);
        $existingDeparture = $tour->departures()->create([
            'departure_date' => '2026-06-20',
            'return_date' => '2026-06-24',
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Xe giường nằm',
            'standard_label' => 'Khách sạn 3 sao',
            'base_price' => 5990000,
            'sale_price' => 5490000,
            'available_slots' => 12,
            'pricing_note' => 'Giá áp dụng cho khách lẻ',
            'status' => 'published',
        ]);
        $newDeparture = $tour->departures()->create([
            'departure_date' => '2026-07-10',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'base_price' => 7990000,
            'sale_price' => 7490000,
            'available_slots' => 8,
            'status' => 'scheduled',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $existingDeparture->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => 23288,
            'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([
                'status' => 'success',
                'data' => [
                    'tour_id' => 998,
                    'tour_code' => 'HDL998',
                    'startdates' => [
                        ['cms_departure_id' => $existingDeparture->getKey(), 'startdate_id' => 23288],
                        ['cms_departure_id' => $newDeparture->getKey(), 'startdate_id' => 30001],
                    ],
                ],
            ]),
        ]);
        Log::spy();

        $summary = app(TourAgencyPushSyncService::class)->push($tour->getKey(), [
            [
                'cms_departure_id' => 777,
                'tour_id' => 998,
                'tour_code' => 'HDL998',
                'startdate_id' => 23290,
            ],
        ]);

        $this->assertFalse($summary['skipped']);
        $this->assertSame(2, $summary['departures']);
        $this->assertSame(1, $summary['deleted_departures']);
        $this->assertSame('success', $summary['response_status']);
        $this->assertSame(998, $summary['response_tour_id']);
        $this->assertSame('HDL998', $summary['response_tour_code']);
        $this->assertSame(2, $summary['returned_mappings_count']);
        $this->assertSame([23288, 30001], $summary['returned_startdate_ids']);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($tour): bool {
            return $message === 'tour_sync.master_data_dashboard.payload_prepared'
                && $context['cms_tour_id'] === $tour->getKey()
                && $context['source_tour_id'] === 998
                && $context['tour_code'] === 'HDL998'
                && $context['tour_slug'] === 'tour-da-lat-cap-nhat-tu-cms'
                && $context['transport'] === 'Xe du lịch'
                && $context['duration_days'] === 4
                && $context['duration_nights'] === 3
                && $context['selling_price'] === 5490000
                && $context['departures_count'] === 2
                && $context['deleted_startdates_count'] === 1
                && $context['departure_prices'] === [5490000, 7490000]
                && data_get($context, 'payload.tour.slug') === 'tour-da-lat-cap-nhat-tu-cms'
                && data_get($context, 'payload.tour.price') === 5490000
                && data_get($context, 'payload.tour.transport') === 'Xe du lịch'
                && data_get($context, 'payload.tour.duration_days') === 4
                && data_get($context, 'payload.departures.0.traffic') === 'Xe giường nằm';
        });
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($tour): bool {
            return $message === 'tour_sync.master_data_dashboard.request_sending'
                && $context['cms_tour_id'] === $tour->getKey()
                && $context['push_path'] === '/tour/agency/sync/cms-updates';
        });
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($tour): bool {
            return $message === 'tour_sync.master_data_dashboard.response_received'
                && $context['cms_tour_id'] === $tour->getKey()
                && $context['response_status'] === 'success'
                && $context['returned_mappings_count'] === 2
                && data_get($context, 'response.data.tour_id') === 998;
        });

        Http::assertSent(function ($request) use ($tour, $existingDeparture, $newDeparture): bool {
            return $request->url() === 'https://agency.example.test/api/tour/agency/sync/cms-updates'
                && $request->hasHeader('Authorization', 'Bearer staff-token')
                && $request['source'] === 'haidangtravel_cms'
                && is_string($request['client_sync_log_id'])
                && $request['payload_schema_version'] === 'cms_legacy_agency_v3'
                && $request['tour']['cms_tour_id'] === $tour->getKey()
                && $request['tour']['tour_id'] === 998
                && $request['tour']['tour_name'] === 'Tour Đà Lạt cập nhật từ CMS'
                && $request['tour']['slug'] === 'tour-da-lat-cap-nhat-tu-cms'
                && $request['tour']['transport'] === 'Xe du lịch'
                && $request['tour']['standard_label'] === 'Khách sạn 3 sao'
                && $request['tour']['price'] === 5490000
                && $request['tour']['base_price'] === 5490000
                && $request['tour']['sale_price'] === 5490000
                && $request['tour']['duration_days'] === 4
                && $request['tour']['duration_nights'] === 3
                && $request['tour']['scope'] === 'domestic'
                && $request['tour']['isOutbound'] === 0
                && $request['tour']['tour_type'] === 0
                && $request['tour']['seodescription'] === 'Lịch trình đã được CMS cập nhật.'
                && $request['tour']['manager_email'] === 'sale-cms@example.test'
                && $request['tour']['manager']['email'] === 'sale-cms@example.test'
                && $request['tour']['manager']['name'] === 'Sale CMS'
                && $request['tour']['manager']['phone'] === '0911 222 333'
                && $request['departures'][0]['cms_departure_id'] === $existingDeparture->getKey()
                && $request['departures'][0]['startdate_id'] === 23288
                && $request['departures'][0]['startdate'] === '2026-06-20'
                && $request['departures'][0]['traffic'] === 'Xe giường nằm'
                && $request['departures'][0]['adult_price'] === 5490000
                && $request['departures'][0]['price'] === 5490000
                && $request['departures'][0]['base_price'] === 5490000
                && $request['departures'][0]['sale_price'] === 5490000
                && $request['departures'][0]['seat'] === 12
                && $request['departures'][0]['total_seat'] === 12
                && $request['departures'][0]['save_agency'] === 12
                && $request['departures'][0]['is_agency'] === 1
                && $request['departures'][1]['cms_departure_id'] === $newDeparture->getKey()
                && $request['departures'][1]['startdate_id'] === null
                && $request['departures'][1]['price'] === 7490000
                && $request['deleted_startdates'][0]['startdate_id'] === 23290;
        });

        $this->assertDatabaseHas('tour_departure_sync_states', [
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $newDeparture->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => 30001,
        ]);
    }

    public function test_push_sync_normalizes_payload_for_legacy_agency_columns(): void
    {
        $this->configureAgencySyncSettings();

        $longExcerpt = str_repeat('Mô tả tour rất dài ', 30);
        $longPricingNote = str_repeat('Ghi chú giá agency ', 30);

        $tour = Tour::query()->create([
            'title' => 'Tour quốc tế đã lưu trữ',
            'slug' => 'tour-quoc-te-da-luu-tru',
            'status' => 'archived',
            'scope' => TourScope::International->value,
            'excerpt' => $longExcerpt,
            'transport' => str_repeat('Máy bay ', 40),
            'standard_label' => 'Khách sạn 5 sao',
            'duration_days' => 5,
            'duration_nights' => 4,
            'base_price' => '12990000',
            'sale_price' => '11990000',
        ]);
        $departure = $tour->departures()->create([
            'departure_date' => '2026-09-10',
            'return_date' => '2026-09-15',
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => str_repeat('Máy bay ', 40),
            'standard_label' => 'Khách sạn 5 sao',
            'base_price' => '12990000',
            'sale_price' => '11990000',
            'available_slots' => 0,
            'pricing_note' => $longPricingNote,
            'status' => 'sold_out',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'source_tour_id' => 1200,
            'tour_code' => 'HD012002026',
            'source_startdate_id' => 40001,
            'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([
                'status' => 'success',
                'data' => [
                    'tour_id' => 1200,
                    'tour_code' => 'HD012002026',
                    'startdates' => [
                        ['cms_departure_id' => $departure->getKey(), 'startdate_id' => 40001],
                    ],
                ],
            ]),
        ]);

        app(TourAgencyPushSyncService::class)->push($tour->getKey());

        Http::assertSent(function ($request) use ($tour, $departure, $longExcerpt, $longPricingNote): bool {
            return $request->url() === 'https://agency.example.test/api/tour/agency/sync/cms-updates'
                && $request['tour']['cms_tour_id'] === $tour->getKey()
                && $request['tour']['scope'] === 'international'
                && $request['tour']['isOutbound'] === 1
                && $request['tour']['tour_type'] === 1
                && $request['tour']['status'] === 'inactive'
                && $request['tour']['price'] === 11990000
                && $request['tour']['base_price'] === 11990000
                && $request['tour']['sale_price'] === 11990000
                && $request['tour']['excerpt'] === Str::limit($longExcerpt, 255, '')
                && $request['tour']['seodescription'] === Str::limit($longExcerpt, 255, '')
                && Str::length($request['tour']['transport']) <= 255
                && $request['departures'][0]['cms_departure_id'] === $departure->getKey()
                && $request['departures'][0]['status'] === 'closed'
                && $request['departures'][0]['status_label'] === 'closed'
                && $request['departures'][0]['is_agency'] === 0
                && $request['departures'][0]['adult_price'] === 11990000
                && $request['departures'][0]['price'] === 11990000
                && $request['departures'][0]['base_price'] === 11990000
                && $request['departures'][0]['sale_price'] === 11990000
                && $request['departures'][0]['seat'] === 0
                && $request['departures'][0]['total_seat'] === 0
                && $request['departures'][0]['save_agency'] === 0
                && $request['departures'][0]['notice_agency'] === Str::limit($longPricingNote, 255, '')
                && Str::length($request['departures'][0]['traffic']) <= 255;
        });
    }

    public function test_push_sync_omits_null_optional_strings_and_fills_required_departure_fallbacks(): void
    {
        $this->configureAgencySyncSettings();

        $manager = User::query()->create([
            'name' => 'Sale No Phone',
            'email' => 'sale-no-phone@example.test',
            'phone' => null,
            'password' => 'password',
            'is_active' => true,
        ]);
        $tour = Tour::query()->create([
            'title' => 'Tour departure thiếu field',
            'slug' => 'tour-departure-thieu-field',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'managed_by_user_id' => $manager->getKey(),
            'excerpt' => null,
            'transport' => 'Xe Limousine',
            'duration_days' => 3,
            'duration_nights' => 2,
            'base_price' => 3990000,
            'sale_price' => 3590000,
        ]);
        $departure = $tour->departures()->create([
            'departure_date' => '2026-08-01',
            'return_date' => null,
            'transport_label' => null,
            'base_price' => null,
            'sale_price' => null,
            'available_slots' => 6,
            'status' => 'scheduled',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'source_tour_id' => 1300,
            'tour_code' => 'HD013002026',
            'source_startdate_id' => 50001,
            'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([
                'status' => 'success',
                'data' => [
                    'tour_id' => 1300,
                    'tour_code' => 'HD013002026',
                    'startdates' => [
                        ['cms_departure_id' => $departure->getKey(), 'startdate_id' => 50001],
                    ],
                ],
            ]),
        ]);

        app(TourAgencyPushSyncService::class)->push($tour->getKey());

        Http::assertSent(function ($request) use ($departure): bool {
            $payload = $request->data();

            return $request->url() === 'https://agency.example.test/api/tour/agency/sync/cms-updates'
                && ! array_key_exists('phone', $payload['tour']['manager'])
                && ! array_key_exists('excerpt', $payload['tour'])
                && ! array_key_exists('seodescription', $payload['tour'])
                && $payload['departures'][0]['cms_departure_id'] === $departure->getKey()
                && $payload['departures'][0]['departure_date'] === '2026-08-01'
                && $payload['departures'][0]['return_date'] === '2026-08-03'
                && $payload['departures'][0]['traffic'] === 'Xe Limousine'
                && $payload['departures'][0]['adult_price'] === 3590000
                && $payload['departures'][0]['price'] === 3590000
                && $payload['departures'][0]['base_price'] === 3590000
                && $payload['departures'][0]['sale_price'] === 3590000;
        });
    }

    public function test_push_sync_rejects_empty_agency_api_response(): void
    {
        $this->configureAgencySyncSettings();

        $tour = Tour::query()->create([
            'title' => 'Tour API response rỗng',
            'slug' => 'tour-api-response-rong',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
        $departure = $tour->departures()->create([
            'departure_date' => '2026-06-20',
            'status' => 'published',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'source_tour_id' => 1085,
            'tour_code' => 'HD010852026',
            'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([]),
        ]);

        $this->expectException(HaidangAgencyApiException::class);
        $this->expectExceptionMessage('Cổng API đồng bộ tour trả về phản hồi không hợp lệ hoặc thiếu trạng thái success.');

        app(TourAgencyPushSyncService::class)->push($tour->getKey());
    }

    public function test_push_sync_logs_remote_api_error_context(): void
    {
        $this->configureAgencySyncSettings();

        $tour = Tour::query()->create([
            'title' => 'Tour remote API lỗi',
            'slug' => 'tour-remote-api-loi',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
        $departure = $tour->departures()->create([
            'departure_date' => '2026-06-20',
            'status' => 'published',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'source_tour_id' => 1085,
            'tour_code' => 'HD010852026',
            'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([
                'message' => 'Remote API logging context error.',
            ], 500),
        ]);
        Log::spy();

        try {
            app(TourAgencyPushSyncService::class)->push($tour->getKey());
            $this->fail('Expected remote API exception was not thrown.');
        } catch (HaidangAgencyApiException $exception) {
            $this->assertSame('Remote API logging context error.', $exception->getMessage());
        }

        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context) use ($tour): bool {
                return $message === 'tour_sync.master_data_dashboard.response_failed'
                    && (int) data_get($context, 'cms_tour_id') === $tour->getKey()
                    && data_get($context, 'remote_method') === 'POST'
                    && data_get($context, 'remote_path') === '/tour/agency/sync/cms-updates'
                    && data_get($context, 'remote_url') === 'https://agency.example.test/api/tour/agency/sync/cms-updates'
                    && (int) data_get($context, 'remote_http_status') === 500
                    && data_get($context, 'remote_response.message') === 'Remote API logging context error.';
            });
    }

    public function test_push_path_uses_default_when_env_value_is_blank(): void
    {
        $original = $_ENV['TOUR_SYNC_PUSH_PATH'] ?? null;
        $_ENV['TOUR_SYNC_PUSH_PATH'] = '';

        $config = require base_path('config/tour_sync.php');

        $this->assertSame('/tour/agency/sync/cms-updates', $config['push_path']);

        if ($original === null) {
            unset($_ENV['TOUR_SYNC_PUSH_PATH']);
        } else {
            $_ENV['TOUR_SYNC_PUSH_PATH'] = $original;
        }
    }

    public function test_push_client_rejects_blank_push_path_runtime_config(): void
    {
        $this->configureAgencySyncSettings();
        config()->set('tour_sync.push_path', '');

        $this->expectException(HaidangAgencyApiException::class);
        $this->expectExceptionMessage('Chưa cấu hình endpoint nhận đồng bộ tour. Kiểm tra TOUR_SYNC_PUSH_PATH.');

        app(HaidangAgencyApiClient::class)->pushTourUpdate([]);
    }

    public function test_existing_only_resync_sends_only_mapped_departures(): void
    {
        $this->configureAgencySyncSettings();

        $tour = Tour::query()->create([
            'title' => 'Tour sync lại chỉ dữ liệu đã map',
            'slug' => 'tour-sync-lai-chi-du-lieu-da-map',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'transport' => 'Xe du lịch',
            'duration_days' => 2,
            'duration_nights' => 1,
            'base_price' => 3990000,
            'sale_price' => 3590000,
        ]);
        $mappedDeparture = $tour->departures()->create([
            'departure_date' => '2026-07-10',
            'transport_label' => 'Xe du lịch',
            'base_price' => 3990000,
            'sale_price' => 3590000,
            'status' => 'published',
        ]);
        $unmappedDeparture = $tour->departures()->create([
            'departure_date' => '2026-07-20',
            'transport_label' => 'Limousine',
            'base_price' => 4990000,
            'sale_price' => 4590000,
            'status' => 'published',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $mappedDeparture->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => 23288,
            'last_synced_at' => now(),
        ]);
        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $unmappedDeparture->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => null,
            'last_synced_at' => now(),
        ]);

        $run = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'trigger' => 'manual_resync_existing',
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
            'queue_name' => 'default',
            'queued_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([
                'status' => 'success',
                'data' => [
                    'tour_id' => 998,
                    'tour_code' => 'HDL998',
                    'startdates' => [
                        ['cms_departure_id' => $mappedDeparture->getKey(), 'startdate_id' => 23288],
                    ],
                ],
            ]),
        ]);

        app(TourAgencyPushSyncRunService::class)->execute($run);

        $run->refresh();

        $this->assertSame(TourAgencyPushSyncRun::STATUS_SUCCEEDED, $run->status);
        $this->assertSame('existing_only', data_get($run->summary, 'sync_mode'));
        $this->assertSame(1, data_get($run->summary, 'departures'));

        Http::assertSent(function ($request) use ($mappedDeparture, $unmappedDeparture): bool {
            $payload = $request->data();

            return $request->url() === 'https://agency.example.test/api/tour/agency/sync/cms-updates'
                && data_get($payload, 'sync_mode') === 'existing_only'
                && count($payload['departures']) === 1
                && data_get($payload, 'departures.0.cms_departure_id') === $mappedDeparture->getKey()
                && data_get($payload, 'departures.0.startdate_id') === 23288
                && ! collect($payload['departures'])->pluck('cms_departure_id')->contains($unmappedDeparture->getKey());
        });
    }

    public function test_tracked_push_job_does_not_fallback_when_run_row_was_deleted(): void
    {
        $this->configureAgencySyncSettings();

        Http::fake();
        Log::spy();

        $job = new PushTourToAgencyJob(12345, [], 99999);
        $job->handle(app(TourAgencyPushSyncService::class), app(TourAgencyPushSyncRunService::class));

        Http::assertNothingSent();
        Log::shouldHaveReceived('notice')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'tour_sync.master_data_dashboard.run_missing'
                    && (int) data_get($context, 'run_id') === 99999
                    && (int) data_get($context, 'cms_tour_id') === 12345;
            });
    }

    protected function configureAgencySyncSettings(): void
    {
        Cache::flush();

        config()->set('tour_sync.username', null);
        config()->set('tour_sync.password', null);
        config()->set('tour_sync.token', null);
        config()->set('tour_sync.push_enabled', true);
        config()->set('tour_sync.push_path', '/tour/agency/sync/cms-updates');

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'site_name' => 'Haidang Travel',
            'customer_loyalty_api_base_url' => 'https://agency.example.test/api',
            'customer_loyalty_api_username' => 'staff-sync',
            'customer_loyalty_api_password' => 'secret',
            'customer_loyalty_api_token' => null,
            'customer_loyalty_api_token_expires_at' => null,
            'customer_loyalty_api_token_refreshed_at' => null,
        ]);

        app(SiteSettingsManager::class)->refresh();
    }
}
