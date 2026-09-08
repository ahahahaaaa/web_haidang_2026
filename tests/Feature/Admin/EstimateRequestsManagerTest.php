<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\EstimateRequest;
use Tests\TestCase;

class EstimateRequestsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_estimate_requests_manager(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        EstimateRequest::query()->create([
            'tier_code' => 'co-ban',
            'tier_name' => 'Dự toán cơ bản',
            'requires_key' => false,
            'delivery_channel' => 'admin_email_and_customer_email_and_collection',
            'mail_status' => 'sent',
            'customer_mail_status' => 'sent',
            'status' => 'submitted',
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000111',
            'customer_email' => 'customer@example.com',
            'project_location' => 'Thu Duc',
            'project_overview' => 'Can xem nhanh tinh trang du toan.',
            'mailed_to' => 'hello@phongthanhdat.vn',
            'customer_mailed_to' => 'customer@example.com',
            'input_payload' => [
                ['label' => 'Loại công trình', 'slug' => 'project_type', 'value' => 'nha-pho', 'display_value' => 'Nhà phố'],
            ],
            'result_snapshot' => [
                'title' => 'Bộ kết quả sơ bộ theo thông số bạn vừa nhập',
                'summary' => 'Nhận khung phạm vi, mức độ ưu tiên và gợi ý cách chuẩn bị brief cho bước tiếp theo.',
                'bullets' => ['Tóm tắt nhu cầu từ form đầu vào'],
                'delivery_text' => 'Kết quả được lưu vào collection và gửi email cho bộ phận phụ trách.',
            ],
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.estimate-requests'))
            ->assertOk()
            ->assertSeeText('Phiếu yêu cầu dự toán')
            ->assertSeeText('Nguyen Van A')
            ->assertSeeText('Bộ kết quả sơ bộ theo thông số bạn vừa nhập');
    }
}
