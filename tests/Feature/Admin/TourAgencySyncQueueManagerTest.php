<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\TourAgencySyncQueueManager;
use App\Models\User;
use App\Services\Cms\SiteSettingsManager;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourAgencyPushSyncRun;
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Tests\TestCase;

class TourAgencySyncQueueManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_run_all_existing_agency_push_queue_items_immediately(): void
    {
        $this->seed(CmsBootstrapSeeder::class);
        $this->configureAgencySyncSettings();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour pending push',
            'slug' => 'tour-pending-push',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'transport' => 'Xe du lịch',
            'standard_label' => 'Khách sạn 4 sao',
            'duration_days' => 2,
            'duration_nights' => 1,
        ]);
        $departure = $tour->departures()->create([
            'departure_date' => '2026-07-10',
            'transport_label' => 'Xe du lịch',
            'standard_label' => 'Khách sạn 4 sao',
            'base_price' => 3990000,
            'sale_price' => 3590000,
            'available_slots' => 10,
            'status' => 'published',
        ]);

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'source_startdate_id' => 23288,
            'last_synced_at' => now(),
        ]);
        $pendingRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
            'queue_name' => 'default',
            'queued_at' => now(),
        ]);
        $failedRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_FAILED,
            'queue_name' => 'default',
            'attempts' => 2,
            'last_error' => 'Lỗi API trước đó',
            'queued_at' => now(),
            'started_at' => now()->subMinute(),
            'finished_at' => now()->subMinute(),
        ]);
        $skippedRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_SKIPPED,
            'queue_name' => 'default',
            'attempts' => 1,
            'queued_at' => now(),
            'finished_at' => now()->subMinute(),
        ]);
        $runningRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_RUNNING,
            'queue_name' => 'default',
            'attempts' => 3,
            'queued_at' => now(),
            'started_at' => now(),
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/cms-updates' => Http::response([
                'status' => 'success',
                'data' => [
                    'tour_id' => 1090,
                    'tour_code' => 'HD010902026',
                    'startdates' => [
                        ['cms_departure_id' => $departure->getKey(), 'startdate_id' => 23288],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($admin);
        Log::spy();

        Livewire::test(TourAgencySyncQueueManager::class)
            ->assertSee('Tour pending push')
            ->call('runAllExistingNow')
            ->assertSee('Đã xử lý ngay 3 hàng chờ hiện có');

        $pendingRun->refresh();
        $failedRun->refresh();
        $skippedRun->refresh();
        $runningRun->refresh();

        $this->assertSame(TourAgencyPushSyncRun::STATUS_SUCCEEDED, $pendingRun->status);
        $this->assertSame(TourAgencyPushSyncRun::STATUS_SUCCEEDED, $failedRun->status);
        $this->assertSame(TourAgencyPushSyncRun::STATUS_SUCCEEDED, $skippedRun->status);
        $this->assertSame(TourAgencyPushSyncRun::STATUS_RUNNING, $runningRun->status);
        $this->assertSame(1, $pendingRun->attempts);
        $this->assertSame(3, $failedRun->attempts);
        $this->assertSame(2, $skippedRun->attempts);
        $this->assertSame(3, $runningRun->attempts);
        $this->assertSame(1, data_get($pendingRun->summary, 'departures'));
        $this->assertSame('success', data_get($pendingRun->summary, 'response_status'));
        $this->assertSame(1090, data_get($pendingRun->summary, 'response_tour_id'));
        $this->assertSame('HD010902026', data_get($pendingRun->summary, 'response_tour_code'));
        $this->assertSame(1, data_get($pendingRun->summary, 'returned_mappings_count'));
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($pendingRun): bool {
                return $message === 'tour_sync.master_data_dashboard.processing_started'
                    && (int) data_get($context, 'run_id') === $pendingRun->getKey()
                    && data_get($context, 'status') === TourAgencyPushSyncRun::STATUS_RUNNING
                    && (int) data_get($context, 'source_tour_id') === 1090
                    && data_get($context, 'queue_name') === 'default';
            });
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($pendingRun): bool {
                return $message === 'tour_sync.master_data_dashboard.processing_finished'
                    && (int) data_get($context, 'run_id') === $pendingRun->getKey()
                    && data_get($context, 'status') === TourAgencyPushSyncRun::STATUS_SUCCEEDED
                    && (int) data_get($context, 'summary.departures') === 1
                    && data_get($context, 'summary.response_status') === 'success'
                    && (int) data_get($context, 'summary.returned_mappings_count') === 1;
            });
        $pushRequests = Http::recorded(fn ($request, $response): bool => $request->url() === 'https://agency.example.test/api/tour/agency/sync/cms-updates');

        $this->assertCount(3, $pushRequests);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://agency.example.test/api/tour/agency/sync/cms-updates'
            && $request['tour']['tour_id'] === 1090
            && $request['tour']['tour_code'] === 'HD010902026'
            && $request['tour']['transport'] === 'Xe du lịch'
            && $request['tour']['duration_days'] === 2
            && $request['departures'][0]['startdate_id'] === 23288);
    }

    public function test_admin_can_delete_finished_agency_push_queue_items(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour cleanup queue',
            'slug' => 'tour-cleanup-queue',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
        $succeededRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_SUCCEEDED,
            'queue_name' => 'default',
            'queued_at' => now()->subMinute(),
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
        $failedRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1091,
            'tour_code' => 'HD010912026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_FAILED,
            'queue_name' => 'default',
            'queued_at' => now(),
            'last_error' => 'API timeout',
        ]);
        $pendingRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1092,
            'tour_code' => 'HD010922026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
            'queue_name' => 'default',
            'queued_at' => now(),
        ]);

        $this->actingAs($admin);
        Log::spy();

        Livewire::test(TourAgencySyncQueueManager::class)
            ->assertSee('Xóa')
            ->call('deleteFinished', $pendingRun->getKey())
            ->assertSee('Chỉ có thể xóa hàng chờ đã kết thúc: thành công, lỗi hoặc bỏ qua.')
            ->call('deleteFinished', $failedRun->getKey())
            ->assertSee('Đã xóa hàng chờ đã kết thúc #'.$failedRun->getKey().' khỏi database.')
            ->call('deleteFinished', $succeededRun->getKey())
            ->assertSee('Đã xóa hàng chờ đã kết thúc #'.$succeededRun->getKey().' khỏi database.');

        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'id' => $succeededRun->getKey(),
        ]);
        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'id' => $failedRun->getKey(),
        ]);
        $this->assertDatabaseHas('tour_agency_push_sync_runs', [
            'id' => $pendingRun->getKey(),
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
        ]);
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($succeededRun, $admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queue_deleted'
                    && (int) data_get($context, 'run_id') === $succeededRun->getKey()
                    && data_get($context, 'status') === TourAgencyPushSyncRun::STATUS_SUCCEEDED
                    && (int) data_get($context, 'deleted_by_user_id') === $admin->getKey();
            });
    }

    public function test_admin_can_delete_all_finished_agency_push_queue_items(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour bulk cleanup queue',
            'slug' => 'tour-bulk-cleanup-queue',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
        $firstSucceededRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_SUCCEEDED,
            'queue_name' => 'default',
            'queued_at' => now()->subMinutes(2),
            'started_at' => now()->subMinutes(2),
            'finished_at' => now()->subMinute(),
        ]);
        $secondSucceededRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1091,
            'tour_code' => 'HD010912026',
            'trigger' => 'tour_saved',
            'status' => TourAgencyPushSyncRun::STATUS_SUCCEEDED,
            'queue_name' => 'default',
            'queued_at' => now()->subMinutes(2),
            'started_at' => now()->subMinutes(2),
            'finished_at' => now()->subMinute(),
        ]);
        $pendingRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1092,
            'tour_code' => 'HD010922026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
            'queue_name' => 'default',
            'queued_at' => now(),
        ]);
        $failedRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1093,
            'tour_code' => 'HD010932026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_FAILED,
            'queue_name' => 'default',
            'queued_at' => now(),
            'last_error' => 'API timeout',
        ]);
        $skippedRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1094,
            'tour_code' => 'HD010942026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_SKIPPED,
            'queue_name' => 'default',
            'queued_at' => now(),
            'summary' => ['skipped' => true, 'reason' => 'missing_source_mapping'],
        ]);

        $this->actingAs($admin);
        Log::spy();

        Livewire::test(TourAgencySyncQueueManager::class)
            ->assertSee('Xóa tất cả đã kết thúc')
            ->call('deleteAllFinished')
            ->assertSee('Đã xóa 4 hàng chờ đã kết thúc khỏi database.');

        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'id' => $firstSucceededRun->getKey(),
        ]);
        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'id' => $secondSucceededRun->getKey(),
        ]);
        $this->assertDatabaseHas('tour_agency_push_sync_runs', [
            'id' => $pendingRun->getKey(),
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'id' => $failedRun->getKey(),
        ]);
        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'id' => $skippedRun->getKey(),
        ]);
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($firstSucceededRun, $admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queue_deleted'
                    && data_get($context, 'delete_mode') === 'bulk'
                    && (int) data_get($context, 'run_id') === $firstSucceededRun->getKey()
                    && (int) data_get($context, 'deleted_by_user_id') === $admin->getKey();
            });
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($secondSucceededRun, $admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queue_deleted'
                    && data_get($context, 'delete_mode') === 'bulk'
                    && (int) data_get($context, 'run_id') === $secondSucceededRun->getKey()
                    && (int) data_get($context, 'deleted_by_user_id') === $admin->getKey();
            });
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queue_bulk_deleted'
                    && (int) data_get($context, 'deleted_count') === 4
                    && (int) data_get($context, 'deleted_by_user_id') === $admin->getKey();
            });
    }

    public function test_admin_can_unlock_stale_running_agency_push_queue_items(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour stale running queue',
            'slug' => 'tour-stale-running-queue',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
        $staleRunningRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1090,
            'tour_code' => 'HD010902026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_RUNNING,
            'queue_name' => 'default',
            'attempts' => 1,
            'queued_at' => now()->subMinutes(30),
            'started_at' => now()->subMinutes(20),
        ]);
        $freshRunningRun = TourAgencyPushSyncRun::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'source_tour_id' => 1091,
            'tour_code' => 'HD010912026',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_RUNNING,
            'queue_name' => 'default',
            'attempts' => 1,
            'queued_at' => now(),
            'started_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($admin);
        Log::spy();

        Livewire::test(TourAgencySyncQueueManager::class)
            ->assertSee('Mở khóa đang chạy kẹt')
            ->call('unlockStaleRunning')
            ->assertSee('Đã mở khóa 1 hàng chờ đang chạy kẹt.');

        $staleRunningRun->refresh();
        $freshRunningRun->refresh();

        $this->assertSame(TourAgencyPushSyncRun::STATUS_FAILED, $staleRunningRun->status);
        $this->assertSame('Hàng chờ đang chạy quá 15 phút đã được admin mở khóa để retry.', $staleRunningRun->last_error);
        $this->assertNotNull($staleRunningRun->finished_at);
        $this->assertSame(TourAgencyPushSyncRun::STATUS_RUNNING, $freshRunningRun->status);

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context) use ($staleRunningRun, $admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queue_unlocked'
                    && (int) data_get($context, 'run_id') === $staleRunningRun->getKey()
                    && (int) data_get($context, 'unlocked_by_user_id') === $admin->getKey()
                    && (int) data_get($context, 'stale_after_minutes') === 15;
            });
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queue_bulk_unlocked'
                    && (int) data_get($context, 'unlocked_count') === 1
                    && (int) data_get($context, 'unlocked_by_user_id') === $admin->getKey();
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
