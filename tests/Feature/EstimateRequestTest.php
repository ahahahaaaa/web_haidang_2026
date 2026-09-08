<?php

namespace Tests\Feature;

use App\Mail\EstimateCustomerResultMail;
use App\Mail\EstimateRequestMail;
use App\Services\Cms\EstimateRequestService;
use App\Support\EstimatePageContent;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Models\EstimateAccessKey;
use Src\Domains\Cms\Models\EstimateRequest;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class EstimateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_request_requires_key_for_premium_tier(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $this->from(route('estimate.show'))
            ->post(route('estimate.request.store'), [
                'selected_tier_code' => 'chuyen-sau',
                'selected_tier_name' => 'Dự toán nâng cao',
                'selected_estimator_level' => 'level_2',
                'input_payload' => $this->validGroupedInputPayload('level_2'),
                'result_payload' => $this->validGroupedResultPayload('level_2'),
                'customer_name' => 'Nguyen Van A',
                'customer_phone' => '0909000111',
                'customer_email' => 'customer@example.com',
                'project_overview' => 'Can bo du toan chi tiet hon de trinh noi bo.',
                'project_location' => 'Thu Duc',
            ])
            ->assertRedirect(route('estimate.show'))
            ->assertSessionHasErrors(['estimate_key'], null, 'estimateRequest');
    }

    public function test_estimate_request_service_sends_mail_to_admin_and_customer_and_stores_statuses(): void
    {
        Mail::fake();
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $landing = LandingPage::query()->where('page_key', 'estimate')->firstOrFail();
        $estimateConfig = EstimatePageContent::prepareConfig($landing->estimate_config);
        $settings = SiteSetting::query()->findOrFail(1);

        $request = app(EstimateRequestService::class)->submit([
            'selected_tier_code' => 'co-ban',
            'selected_tier_name' => 'Dự toán cơ bản',
            'selected_estimator_level' => 'level_1',
            'input_payload' => $this->validInputPayload('level_1'),
            'result_payload' => $this->validResultPayload('level_1'),
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000111',
            'customer_email' => 'customer@example.com',
            'company_name' => '',
            'project_overview' => 'Can bo du toan so bo de chuan bi quyet dinh.',
            'project_location' => 'Thu Duc',
            'page_url' => route('estimate.show'),
        ], $estimateConfig, $settings);

        Mail::assertSent(EstimateRequestMail::class, function (EstimateRequestMail $mail) {
            return $mail->estimateRequest->customer_email === 'customer@example.com';
        });

        Mail::assertSent(EstimateCustomerResultMail::class, function (EstimateCustomerResultMail $mail) {
            return $mail->estimateRequest->customer_email === 'customer@example.com'
                && $mail->estimateRequest->customer_name === 'Nguyen Van A';
        });

        $request = EstimateRequest::query()->findOrFail($request->getKey());

        $this->assertSame('sent', $request->mail_status);
        $this->assertSame('sent', $request->customer_mail_status);
        $this->assertSame('customer@example.com', $request->customer_mailed_to);
        $this->assertSame('hello@phongthanhdat.vn', $request->mailed_to);
        $this->assertSame('level_1', data_get($request->result_snapshot, 'level'));
        $this->assertSame(180, data_get($request->result_snapshot, 'estimate.totals.converted_area'));
    }

    public function test_grouped_estimate_request_requires_positive_pool_area_when_pool_is_enabled(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        EstimateAccessKey::query()->create([
            'label' => 'Advanced key',
            'code' => 'ADV-2026',
            'tier_codes' => ['chuyen-sau'],
            'is_active' => true,
            'used_count' => 0,
        ]);

        $payload = json_decode($this->validGroupedInputPayload('level_2'), true, 512, JSON_THROW_ON_ERROR);
        $payload['technical_inputs']['pool_area'] = 0;

        $this->from(route('estimate.show'))
            ->post(route('estimate.request.store'), [
                'selected_tier_code' => 'chuyen-sau',
                'selected_tier_name' => 'Dự toán nâng cao',
                'selected_estimator_level' => 'level_2',
                'estimate_key' => 'ADV-2026',
                'input_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'result_payload' => $this->validGroupedResultPayload('level_2'),
                'customer_name' => 'Nguyen Van A',
                'customer_phone' => '0909000111',
                'customer_email' => 'customer@example.com',
                'project_overview' => 'Can mo phong truong hop ho boi nhung chua co dien tich.',
                'project_location' => 'Thu Duc',
            ])
            ->assertRedirect(route('estimate.show'))
            ->assertSessionHasErrors(['input_payload'], null, 'estimateRequest');
    }

    public function test_estimate_request_service_stores_grouped_snapshot_for_internal_estimate(): void
    {
        Mail::fake();
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $accessKey = EstimateAccessKey::query()->create([
            'label' => 'Internal key',
            'code' => 'INT-2026',
            'tier_codes' => ['cao-cap'],
            'is_active' => true,
            'used_count' => 0,
        ]);

        $landing = LandingPage::query()->where('page_key', 'estimate')->firstOrFail();
        $estimateConfig = EstimatePageContent::prepareConfig($landing->estimate_config);
        $settings = SiteSetting::query()->findOrFail(1);

        $request = app(EstimateRequestService::class)->submit([
            'selected_tier_code' => 'cao-cap',
            'selected_tier_name' => 'Dự toán nội bộ',
            'selected_estimator_level' => 'level_3',
            'estimate_key' => 'INT-2026',
            'input_payload' => $this->validGroupedInputPayload('level_3'),
            'result_payload' => $this->validGroupedResultPayload('level_3'),
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000111',
            'customer_email' => 'customer@example.com',
            'company_name' => 'Phong Thanh Dat',
            'project_overview' => 'Can lap du toan noi bo co room program va price book.',
            'project_location' => 'Thu Duc',
            'page_url' => route('estimate.show'),
        ], $estimateConfig, $settings);

        $request = EstimateRequest::query()->findOrFail($request->getKey());
        $accessKey->refresh();

        $this->assertFalse((bool) data_get($request->input_payload, 'legacy'));
        $this->assertSame('Mẫu phòng ngủ master', data_get($request->input_payload, 'room_program.0.template_label'));
        $this->assertSame('Dự toán nội bộ', $request->tier_name);
        $this->assertSame('level_3', data_get($request->result_snapshot, 'level'));
        $this->assertSame('internal-2026-v1', data_get($request->result_snapshot, 'price_book_code'));
        $this->assertNotEmpty(data_get($request->result_snapshot, 'catalog_version'));
        $this->assertNotEmpty(data_get($request->result_snapshot, 'component_tree'));
        $this->assertSame(1, $accessKey->used_count);
    }

    protected function validInputPayload(string $level = 'level_1'): string
    {
        $payload = [
            ['slug' => 'length', 'value' => 5],
            ['slug' => 'width', 'value' => 12],
            ['slug' => 'floors', 'value' => 3],
            ['slug' => 'garden_type', 'value' => 'btct_yard'],
            ['slug' => 'garden_area', 'value' => 20],
            ['slug' => 'has_mezzanine', 'value' => true],
            ['slug' => 'mezzanine_area', 'value' => 36],
            ['slug' => 'void_area', 'value' => 12],
            ['slug' => 'terrace_area', 'value' => 24],
            ['slug' => 'terrace_cover_type', 'value' => 'uncovered'],
            ['slug' => 'roof_type', 'value' => 'btct_no_tile'],
            ['slug' => 'foundation_type', 'value' => 'strip_or_pile'],
            ['slug' => 'has_concrete_ground', 'value' => true],
            ['slug' => 'has_basement', 'value' => false],
            ['slug' => 'finish_package', 'value' => 'standard_plus'],
        ];

        if ($level !== 'level_1') {
            $payload = array_merge($payload, [
                ['slug' => 'front_yard_area', 'value' => 10],
                ['slug' => 'back_yard_area', 'value' => 8],
                ['slug' => 'balcony_area', 'value' => 12],
                ['slug' => 'balcony_open_sides', 'value' => 3],
                ['slug' => 'roof_area', 'value' => 60],
                ['slug' => 'roof_slope_factor', 'value' => 1],
                ['slug' => 'roof_count', 'value' => 1],
                ['slug' => 'building_type', 'value' => 'townhouse'],
                ['slug' => 'location_zone', 'value' => 'hcm_inner'],
                ['slug' => 'access_condition', 'value' => 'small_alley'],
                ['slug' => 'soil_type', 'value' => 'medium'],
                ['slug' => 'has_elevator', 'value' => false],
                ['slug' => 'has_pool', 'value' => false],
            ]);
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    protected function validResultPayload(string $level = 'level_1'): string
    {
        return json_encode([
            'level' => $level,
            'formula_version' => 'v1',
            'price_version' => 'v1',
            'derived' => [
                'base_area' => 60,
            ],
            'components' => [
                [
                    'key' => 'ground_floor',
                    'label' => 'Tổng Trệt',
                    'area' => 60,
                    'coefficient' => 1,
                    'converted_area' => 60,
                ],
                [
                    'key' => 'upper_floor_1',
                    'label' => 'Tầng 2 (Lầu 1)',
                    'area' => 60,
                    'coefficient' => 1,
                    'converted_area' => 60,
                ],
                [
                    'key' => 'upper_floor_2',
                    'label' => 'Tầng 3 (Lầu 2)',
                    'area' => 60,
                    'coefficient' => 1,
                    'converted_area' => 60,
                ],
            ],
            'totals' => [
                'converted_area' => 180,
            ],
            'pricing' => [
                'packages' => [
                    [
                        'key' => 'construction_standard',
                        'label' => 'Thi công phần thô + nhân công hoàn thiện tiêu chuẩn',
                        'unit_price' => 4200000,
                        'amount' => 756000000,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    protected function validGroupedInputPayload(string $level = 'level_2'): string
    {
        $payload = [
            'base_inputs' => [
                'length' => 5,
                'width' => 12,
                'floors' => 4,
                'finish_package' => 'standard_plus',
                'roof_type' => 'btct_no_tile',
                'foundation_type' => 'strip_or_pile',
            ],
            'technical_inputs' => [
                'building_type' => 'villa',
                'usage_type' => 'residential',
                'bedroom_count' => 4,
                'wc_count' => 5,
                'location_zone' => 'hcm_inner',
                'access_condition' => 'small_alley',
                'has_elevator' => true,
                'elevator_service_floors' => 4,
                'has_pool' => true,
                'pool_area' => 36,
                'pool_type' => 'overflow',
            ],
            'room_program' => [
                [
                    'room_type' => 'master_bedroom',
                    'count' => 2,
                    'template' => 'res_master_bedroom',
                    'extra_items' => ['loose_furniture_upgrade'],
                ],
                [
                    'room_type' => 'wc_room',
                    'count' => 5,
                    'template' => 'res_wc_standard',
                    'extra_items' => [],
                ],
            ],
            'design_options' => ['design_architecture_basic', 'design_interior_package'],
            'special_addons' => ['smart_home_basic', 'solar_roof_basic'],
            'internal_controls' => [],
        ];

        if ($level === 'level_3') {
            $payload['technical_inputs'] = array_merge($payload['technical_inputs'], [
                'soil_class' => 'class_b',
                'groundwater_level' => 'medium',
                'pile_type' => 'precast',
                'pile_depth' => 18,
                'pile_count' => 24,
            ]);
            $payload['internal_controls'] = [
                'contract_scope' => ['design', 'shell', 'interior'],
                'include_permit' => true,
                'include_pile' => true,
                'include_completion' => false,
                'vat_mode' => 'include',
                'discount_percent' => 3,
                'surcharge_percent' => 5,
                'price_set' => 'internal-2026-v1',
                'formula_set' => 'default',
                'internal_notes' => 'Gia dinh can doi room program truoc khi chot hop dong.',
            ];
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    protected function validGroupedResultPayload(string $level = 'level_2'): string
    {
        return json_encode([
            'level' => $level,
            'formula_version' => 'v1',
            'price_version' => $level === 'level_3' ? '2026.v1' : '2026.v1',
            'catalog_version' => 'seed-v1',
            'room_template_version' => 'seed-v1',
            'input' => [
                'length' => 5,
                'width' => 12,
                'floors' => 4,
                'has_elevator' => true,
                'elevator_service_floors' => 4,
                'has_pool' => true,
                'pool_area' => 36,
                'pool_type' => 'overflow',
            ],
            'converted_area_breakdown' => [
                'rows' => [
                    ['key' => 'ground_floor', 'label' => 'Tầng trệt', 'area' => 60, 'coefficient' => 1, 'converted_area' => 60, 'notes' => []],
                    ['key' => 'upper_floor_1', 'label' => 'Tầng 2 (Lầu 1)', 'area' => 60, 'coefficient' => 1, 'converted_area' => 60, 'notes' => []],
                    ['key' => 'upper_floor_2', 'label' => 'Tầng 3 (Lầu 2)', 'area' => 60, 'coefficient' => 1, 'converted_area' => 60, 'notes' => []],
                ],
                'total_converted_area' => 180,
                'total_base_area' => 60,
            ],
            'components' => [
                ['key' => 'ground_floor', 'label' => 'Tầng trệt', 'area' => 60, 'coefficient' => 1, 'converted_area' => 60],
            ],
            'derived' => [
                'base_area' => 60,
            ],
            'room_breakdown' => [
                [
                    'room_type' => 'master_bedroom',
                    'room_type_label' => 'Phòng ngủ master',
                    'count' => 2,
                    'template_code' => 'res_master_bedroom',
                    'template_label' => 'Mẫu phòng ngủ master',
                    'subtotal' => 256000000,
                    'items' => [
                        ['code' => 'master_bedroom_premium', 'label' => 'Nội thất phòng ngủ master', 'quantity' => 2, 'unit' => 'phòng', 'subtotal' => 196000000],
                        ['code' => 'loose_furniture_upgrade', 'label' => 'Bổ sung loose furniture', 'quantity' => 2, 'unit' => 'phòng', 'subtotal' => 60000000],
                    ],
                ],
            ],
            'component_tree_breakdown' => [
                [
                    'code' => 'room_interiors',
                    'name' => 'Nội thất theo phòng',
                    'subtotal' => 256000000,
                    'items' => [],
                    'children' => [
                        [
                            'code' => 'master_bedroom',
                            'name' => 'Phòng ngủ master',
                            'subtotal' => 256000000,
                            'items' => [
                                ['code' => 'master_bedroom_premium', 'label' => 'Nội thất phòng ngủ master', 'quantity' => 2, 'unit' => 'phòng', 'subtotal' => 196000000],
                            ],
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'totals' => [
                'converted_area' => 180,
                'overlay_amount' => 256000000,
            ],
            'pricing_summary' => [
                'price_book_code' => $level === 'level_3' ? 'internal-2026-v1' : 'advanced-2026-v1',
                'price_book_name' => $level === 'level_3' ? 'Bảng giá nội bộ 2026' : 'Bảng giá nâng cao 2026',
                'overlay_subtotal' => 256000000,
                'packages' => [
                    [
                        'key' => 'construction_standard',
                        'label' => 'Thi công phần thô + nhân công hoàn thiện tiêu chuẩn',
                        'suggested' => true,
                        'base_amount' => 684000000,
                        'overlay_amount' => 256000000,
                        'amount' => $level === 'level_3' ? 999428000 : 940000000,
                    ],
                ],
            ],
            'pricing' => [
                'packages' => [
                    [
                        'key' => 'construction_standard',
                        'label' => 'Thi công phần thô + nhân công hoàn thiện tiêu chuẩn',
                        'amount' => $level === 'level_3' ? 999428000 : 940000000,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
