<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateFormulaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_estimate_formula_manager(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.estimate-formulas'))
            ->assertOk()
            ->assertSeeText('Công thức & quy đổi dự toán')
            ->assertSeeText('Formula set')
            ->assertSeeText('Field catalog')
            ->assertSeeText('Tiêu đề hướng dẫn')
            ->assertSeeText('Nội dung hướng dẫn dùng chung')
            ->assertSeeText('usage_type')
            ->assertSeeText('include_permit')
            ->assertSee('data-admin-quill', false)
            ->assertSee('data-mode="rich"', false)
            ->assertSee('data-allow-images="true"', false);
    }
}
