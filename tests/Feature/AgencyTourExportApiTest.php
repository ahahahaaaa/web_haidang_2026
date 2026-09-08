<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Tests\TestCase;

class AgencyTourExportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_tour_export_requires_configured_token(): void
    {
        config()->set('agency_export.token', null);

        $this->getJson(route('api.v1.agency.tours.index'))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Chưa cấu hình token API export cho agency.');
    }

    public function test_agency_tour_export_requires_bearer_token(): void
    {
        $this->configureAgencyExportToken();

        $this->getJson(route('api.v1.agency.tours.index'))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Thiếu bearer token API agency.');
    }

    public function test_agency_tour_export_rejects_invalid_bearer_token(): void
    {
        $this->configureAgencyExportToken();

        $this->withHeader('Authorization', 'Bearer wrong-token')
            ->getJson(route('api.v1.agency.tours.index'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Bearer token API agency không hợp lệ.');
    }

    public function test_agency_can_export_all_tours_with_all_departure_statuses(): void
    {
        $this->configureAgencyExportToken();

        $manager = User::query()->create([
            'name' => 'Nguyễn Sale',
            'email' => 'sale@example.test',
            'phone' => '0909 111 222',
            'password' => 'password',
            'is_active' => true,
        ]);
        $publishedTour = $this->createTour([
            'title' => 'Tour Đà Lạt mùa hoa',
            'slug' => 'tour-da-lat-mua-hoa',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'transport' => 'Xe du lịch',
            'standard_label' => 'Khách sạn 4 sao',
            'duration_days' => 4,
            'duration_nights' => 3,
            'base_price' => 5990000,
            'sale_price' => 5490000,
            'published_at' => Carbon::parse('2026-05-20 10:00:00'),
            'managed_by_user_id' => $manager->getKey(),
        ]);
        $this->createDeparture($publishedTour, [
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
            'sort_order' => 1,
        ]);
        $this->createDeparture($publishedTour, [
            'departure_date' => '2026-07-10',
            'status' => 'cancelled',
            'sort_order' => 2,
        ]);

        $draftTour = $this->createTour([
            'title' => 'Tour nháp cho agency lọc',
            'slug' => 'tour-nhap-cho-agency-loc',
            'status' => 'draft',
            'scope' => TourScope::International->value,
        ]);
        $this->createDeparture($draftTour, [
            'departure_date' => '2026-08-15',
            'status' => 'sold_out',
        ]);

        $response = $this->authorizedAgencyGet(route('api.v1.agency.tours.index'));

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.tour_id', $publishedTour->id)
            ->assertJsonPath('data.0.title', 'Tour Đà Lạt mùa hoa')
            ->assertJsonPath('data.0.scope', 'domestic')
            ->assertJsonPath('data.0.status', 'published')
            ->assertJsonPath('data.0.transport', 'Xe du lịch')
            ->assertJsonPath('data.0.standard_label', 'Khách sạn 4 sao')
            ->assertJsonPath('data.0.duration_days', 4)
            ->assertJsonPath('data.0.duration_nights', 3)
            ->assertJsonPath('data.0.price', 5490000)
            ->assertJsonPath('data.0.base_price', 5490000)
            ->assertJsonPath('data.0.sale_price', 5490000)
            ->assertJsonPath('data.0.manager_email', 'sale@example.test')
            ->assertJsonPath('data.0.manager.email', 'sale@example.test')
            ->assertJsonPath('data.0.manager.name', 'Nguyễn Sale')
            ->assertJsonPath('data.0.manager.phone', '0909 111 222')
            ->assertJsonPath('data.0.departures.0.tour_id', $publishedTour->id)
            ->assertJsonPath('data.0.departures.0.departure_date', '2026-06-20')
            ->assertJsonPath('data.0.departures.0.return_date', '2026-06-24')
            ->assertJsonPath('data.0.departures.0.departure_location', 'TP. Hồ Chí Minh')
            ->assertJsonPath('data.0.departures.0.transport_label', 'Xe giường nằm')
            ->assertJsonPath('data.0.departures.0.standard_label', 'Khách sạn 3 sao')
            ->assertJsonPath('data.0.departures.0.adult_price', 5490000)
            ->assertJsonPath('data.0.departures.0.price', 5490000)
            ->assertJsonPath('data.0.departures.0.base_price', 5490000)
            ->assertJsonPath('data.0.departures.0.sale_price', 5490000)
            ->assertJsonPath('data.0.departures.0.available_slots', 12)
            ->assertJsonPath('data.0.departures.0.pricing_note', 'Giá áp dụng cho khách lẻ')
            ->assertJsonPath('data.0.departures.0.status', 'published')
            ->assertJsonPath('data.0.departures.1.status', 'cancelled')
            ->assertJsonPath('data.1.status', 'draft')
            ->assertJsonPath('data.1.departures.0.status', 'sold_out');
    }

    public function test_agency_tour_export_filters_by_tour_id(): void
    {
        $this->configureAgencyExportToken();

        $this->createTour(['title' => 'Tour không lấy', 'slug' => 'tour-khong-lay']);
        $targetTour = $this->createTour(['title' => 'Tour cần lấy', 'slug' => 'tour-can-lay']);

        $response = $this->authorizedAgencyGet(route('api.v1.agency.tours.index', [
            'tour_id' => $targetTour->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tour_id', $targetTour->id)
            ->assertJsonPath('data.0.title', 'Tour cần lấy');
    }

    public function test_agency_tour_export_filters_by_tour_or_departure_updated_since(): void
    {
        $this->configureAgencyExportToken();

        $oldTime = Carbon::parse('2026-05-20 08:00:00');
        $newTime = Carbon::parse('2026-05-22 08:00:00');
        $cutoff = '2026-05-21T00:00:00+07:00';

        $oldTour = $this->createTour(['title' => 'Tour cũ', 'slug' => 'tour-cu']);
        $oldDeparture = $this->createDeparture($oldTour, ['departure_date' => '2026-06-01']);
        $this->setTimestamps($oldTour, $oldTime);
        $this->setTimestamps($oldDeparture, $oldTime);

        $changedTour = $this->createTour(['title' => 'Tour mới cập nhật', 'slug' => 'tour-moi-cap-nhat']);
        $this->setTimestamps($changedTour, $newTime);

        $tourWithChangedDeparture = $this->createTour(['title' => 'Tour có ngày mới', 'slug' => 'tour-co-ngay-moi']);
        $changedDeparture = $this->createDeparture($tourWithChangedDeparture, ['departure_date' => '2026-07-01']);
        $this->setTimestamps($tourWithChangedDeparture, $oldTime);
        $this->setTimestamps($changedDeparture, $newTime);

        $response = $this->authorizedAgencyGet(route('api.v1.agency.tours.index', [
            'updated_since' => $cutoff,
        ]));

        $tourIds = collect($response->json('data'))->pluck('tour_id')->all();

        $response->assertOk()->assertJsonPath('meta.total', 2);
        $this->assertNotContains($oldTour->id, $tourIds);
        $this->assertContains($changedTour->id, $tourIds);
        $this->assertContains($tourWithChangedDeparture->id, $tourIds);
    }

    public function test_agency_tour_export_paginates_results(): void
    {
        $this->configureAgencyExportToken();

        $this->createTour(['title' => 'Tour 1', 'slug' => 'tour-1']);
        $this->createTour(['title' => 'Tour 2', 'slug' => 'tour-2']);
        $this->createTour(['title' => 'Tour 3', 'slug' => 'tour-3']);

        $response = $this->authorizedAgencyGet(route('api.v1.agency.tours.index', [
            'page' => 2,
            'per_page' => 2,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tour 3')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    protected function configureAgencyExportToken(): void
    {
        config()->set('agency_export.token', 'agency-secret');
    }

    protected function authorizedAgencyGet(string $url): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer agency-secret')->getJson($url);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createTour(array $overrides = []): Tour
    {
        return Tour::query()->create($overrides + [
            'title' => 'Tour mẫu',
            'slug' => 'tour-mau-'.Tour::query()->count(),
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createDeparture(Tour $tour, array $overrides = []): TourDeparture
    {
        return $tour->departures()->create($overrides + [
            'departure_date' => '2026-06-20',
            'status' => 'published',
            'sort_order' => 0,
        ]);
    }

    protected function setTimestamps(Tour|TourDeparture $model, Carbon $timestamp): void
    {
        DB::table($model->getTable())
            ->where('id', $model->getKey())
            ->update([
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
    }
}
