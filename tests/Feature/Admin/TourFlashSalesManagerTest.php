<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\TourFlashSalesManager;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSale;
use Tests\TestCase;

class TourFlashSalesManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_departure_specific_flash_sale(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);
        $this->actingAs(User::query()->where('email', 'test@example.com')->firstOrFail());

        $this->get(route('admin.tour-flash-sales'))
            ->assertOk()
            ->assertSeeText('Flash Sale tour');

        $this->get(route('admin.tour-flash-sales.create'))
            ->assertOk()
            ->assertSee('min="1" step="1" wire:model="form.items.0.flash_price"', false)
            ->assertSee('wire:model="form.items.0.ticket_quantity"', false)
            ->assertDontSee('min="1" step="1000"', false);

        $tour = Tour::query()->create([
            'title' => 'Tour Singapore Flash Sale',
            'slug' => 'tour-singapore-flash-sale',
            'status' => 'published',
            'scope' => 'international',
            'published_at' => now()->subDay(),
        ]);
        $departure = TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(10)->toDateString(),
            'base_price' => 9900000,
            'sale_price' => 8990000,
            'status' => 'scheduled',
        ]);

        Livewire::test(TourFlashSalesManager::class)
            ->set('form.title', 'Flash Sale Singapore')
            ->set('form.slug', 'flash-sale-singapore')
            ->set('form.starts_at', '2026-09-21T09:00')
            ->set('form.ends_at', '2026-09-21T20:00')
            ->set('form.is_active', true)
            ->set('form.items', [[
                'tour_departure_id' => $departure->getKey(),
                'flash_price' => 6990000,
                'ticket_quantity' => 24,
                'sort_order' => 0,
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tour_flash_sales', [
            'slug' => 'flash-sale-singapore',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('tour_flash_sale_items', [
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_price' => 6990000,
            'ticket_quantity' => 24,
        ]);
    }

    public function test_admin_rejects_flash_price_not_lower_than_current_departure_price(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);
        $this->actingAs(User::query()->where('email', 'test@example.com')->firstOrFail());
        $tour = Tour::query()->create([
            'title' => 'Tour Nhật Bản',
            'slug' => 'tour-nhat-ban-flash-sale',
            'status' => 'published',
            'scope' => 'international',
            'published_at' => now()->subDay(),
        ]);
        $departure = TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(10)->toDateString(),
            'sale_price' => 15000000,
            'status' => 'scheduled',
        ]);

        Livewire::test(TourFlashSalesManager::class)
            ->set('form.title', 'Flash Sale không hợp lệ')
            ->set('form.slug', 'flash-sale-khong-hop-le')
            ->set('form.starts_at', '2026-09-21T09:00')
            ->set('form.ends_at', '2026-09-21T20:00')
            ->set('form.items', [[
                'tour_departure_id' => $departure->getKey(),
                'flash_price' => 15000000,
                'ticket_quantity' => 10,
                'sort_order' => 0,
            ]])
            ->call('save')
            ->assertHasErrors(['form.items.0.flash_price']);
    }

    public function test_admin_preserves_booked_quantity_and_rejects_ticket_quantity_below_booked_count(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);
        $this->actingAs(User::query()->where('email', 'test@example.com')->firstOrFail());
        $tour = Tour::query()->create([
            'title' => 'Tour Hàn Quốc giữ quota',
            'slug' => 'tour-han-quoc-giu-quota',
            'status' => 'published',
            'scope' => 'international',
            'published_at' => now()->subDay(),
        ]);
        $departure = TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(10)->toDateString(),
            'sale_price' => 12000000,
            'status' => 'scheduled',
        ]);
        $campaign = TourFlashSale::query()->create([
            'title' => 'Flash Sale giữ quota',
            'slug' => 'flash-sale-giu-quota',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
            'is_active' => true,
        ]);
        $item = $campaign->items()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_price' => 9000000,
            'ticket_quantity' => 5,
            'booked_quantity' => 3,
        ]);

        Livewire::test(TourFlashSalesManager::class, ['flashSale' => $campaign])
            ->set('form.items.0.ticket_quantity', 2)
            ->call('save')
            ->assertHasErrors(['form.items.0.ticket_quantity']);

        $this->assertSame(5, $item->fresh()->ticket_quantity);
        $this->assertSame(3, $item->fresh()->booked_quantity);

        Livewire::test(TourFlashSalesManager::class, ['flashSale' => $campaign])
            ->set('form.items.0.ticket_quantity', 8)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(8, $item->fresh()->ticket_quantity);
        $this->assertSame(3, $item->fresh()->booked_quantity);
    }
}
