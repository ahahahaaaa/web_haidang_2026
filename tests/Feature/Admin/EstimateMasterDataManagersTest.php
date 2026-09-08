<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Estimator\EstimateCatalogManager;
use App\Livewire\Admin\Estimator\EstimatePriceBooksManager;
use App\Livewire\Admin\Estimator\EstimateRoomTemplatesManager;
use App\Models\User;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EstimateMasterDataManagersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_estimator_master_data_pages(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.estimate-catalog'))
            ->assertOk()
            ->assertSeeText('Catalog cấu phần dự toán')
            ->assertSeeText('Cây component node');

        $this->actingAs($user)
            ->get(route('admin.estimate-room-templates'))
            ->assertOk()
            ->assertSeeText('Template phòng & preset nội thất');

        $this->actingAs($user)
            ->get(route('admin.estimate-price-books'))
            ->assertOk()
            ->assertSeeText('Bảng giá dự toán');
    }

    public function test_admin_can_save_catalog_updates_via_livewire(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(EstimateCatalogManager::class)
            ->set('nodes.0.name', 'Chuẩn bị & ngoài nhà cập nhật')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('estimate_component_nodes', [
            'code' => 'site_preparation',
            'name' => 'Chuẩn bị & ngoài nhà cập nhật',
        ]);
    }

    public function test_admin_can_save_room_template_updates_via_livewire(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(EstimateRoomTemplatesManager::class)
            ->set('templates.0.name', 'Mẫu phòng khách nhà ở cập nhật')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('estimate_room_templates', [
            'code' => 'res_living_standard',
            'name' => 'Mẫu phòng khách nhà ở cập nhật',
        ]);
    }

    public function test_admin_can_save_price_book_updates_via_livewire(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(EstimatePriceBooksManager::class)
            ->set('books.0.name', 'Bảng giá nâng cao 2026 cập nhật')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('estimate_price_books', [
            'code' => 'advanced-2026-v1',
            'name' => 'Bảng giá nâng cao 2026 cập nhật',
        ]);
    }
}
