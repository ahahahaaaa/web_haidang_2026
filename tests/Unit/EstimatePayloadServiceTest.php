<?php

namespace Tests\Unit;

use App\Services\Estimator\EstimatePayloadService;
use App\Support\EstimatePageContent;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatePayloadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flattens_and_normalizes_grouped_payload_for_storage(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $payload = [
            'base_inputs' => [
                'length' => 5,
                'width' => 12,
                'floors' => 4,
                'finish_package' => 'standard_plus',
            ],
            'technical_inputs' => [
                'building_type' => 'villa',
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
            ],
            'design_options' => ['design_architecture_basic'],
            'special_addons' => ['smart_home_basic'],
            'internal_controls' => [
                'price_set' => 'internal-2026-v1',
                'formula_set' => 'default',
                'discount_percent' => 5,
            ],
        ];

        $service = app(EstimatePayloadService::class);
        $flattened = $service->flattenForValidation($payload);
        $normalized = $service->normalizeForStorage(
            EstimatePageContent::defaultConfig(),
            $payload,
            'level_3',
        );
        $internalControlPriceSet = collect(data_get($normalized, 'internal_controls', []))
            ->firstWhere('slug', 'price_set');

        $this->assertSame(5, data_get($flattened, 'length'));
        $this->assertSame('villa', data_get($flattened, 'building_type'));
        $this->assertSame('internal-2026-v1', data_get($flattened, 'price_set'));
        $this->assertFalse(data_get($normalized, 'legacy'));
        $this->assertSame('Phòng ngủ master', data_get($normalized, 'room_program.0.room_type_label'));
        $this->assertSame('Mẫu phòng ngủ master', data_get($normalized, 'room_program.0.template_label'));
        $this->assertSame('Bổ sung loose furniture', data_get($normalized, 'room_program.0.extra_items.0.label'));
        $this->assertSame('Thiết kế kiến trúc cơ bản', data_get($normalized, 'design_options.0.label'));
        $this->assertSame('Smart home cơ bản', data_get($normalized, 'special_addons.0.label'));
        $this->assertSame('Bảng giá áp dụng', data_get($internalControlPriceSet, 'label'));
        $this->assertSame('internal-2026-v1', data_get($internalControlPriceSet, 'value'));
    }
}
