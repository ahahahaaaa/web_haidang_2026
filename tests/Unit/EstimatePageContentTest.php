<?php

namespace Tests\Unit;

use App\Support\EstimatePageContent;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatePageContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CmsEstimateBootstrapSeeder::class);
    }

    public function test_estimator_ui_groups_conditional_mezzanine_and_basement_fields_with_structure_controls(): void
    {
        $ui = EstimatePageContent::estimatorUi(EstimatePageContent::defaultConfig());

        $geometryGroup = collect($ui['groups'])->firstWhere('key', 'geometry');
        $structureGroup = collect($ui['groups'])->firstWhere('key', 'structure');

        $this->assertIsArray($geometryGroup);
        $this->assertIsArray($structureGroup);
        $this->assertContains('has_terrace', $geometryGroup['fields']);
        $this->assertNotContains('mezzanine_area', $geometryGroup['fields']);
        $this->assertNotContains('void_area', $geometryGroup['fields']);
        $this->assertNotContains('basement_area', $geometryGroup['fields']);
        $this->assertSame('has_terrace', data_get($ui, 'fields.tum_area.depends_on.field'));
        $this->assertTrue(data_get($ui, 'fields.tum_area.depends_on.value'));
        $this->assertSame('has_terrace', data_get($ui, 'fields.terrace_area.depends_on.field'));
        $this->assertTrue(data_get($ui, 'fields.terrace_area.depends_on.value'));
        $this->assertSame('has_mezzanine', data_get($ui, 'fields.mezzanine_area.depends_on.field'));
        $this->assertTrue(data_get($ui, 'fields.mezzanine_area.depends_on.value'));
        $this->assertSame('has_mezzanine', data_get($ui, 'fields.void_area.depends_on.field'));
        $this->assertTrue(data_get($ui, 'fields.void_area.depends_on.value'));
        $this->assertSame('has_basement', data_get($ui, 'fields.basement_area.depends_on.field'));
        $this->assertTrue(data_get($ui, 'fields.basement_area.depends_on.value'));
        $this->assertSame('has_basement', data_get($ui, 'fields.basement_type.depends_on.field'));
        $this->assertTrue(data_get($ui, 'fields.basement_type.depends_on.value'));
        $this->assertSame('Có lửng', data_get($ui, 'fields.has_mezzanine.guide_title'));
        $this->assertNotSame('', trim((string) data_get($ui, 'fields.has_mezzanine.guide_content')));
        $hasMezzanineIndex = array_search('has_mezzanine', $structureGroup['fields'], true);
        $mezzanineAreaIndex = array_search('mezzanine_area', $structureGroup['fields'], true);
        $voidAreaIndex = array_search('void_area', $structureGroup['fields'], true);
        $hasBasementIndex = array_search('has_basement', $structureGroup['fields'], true);
        $basementAreaIndex = array_search('basement_area', $structureGroup['fields'], true);
        $basementTypeIndex = array_search('basement_type', $structureGroup['fields'], true);

        $this->assertIsInt($hasMezzanineIndex);
        $this->assertIsInt($mezzanineAreaIndex);
        $this->assertIsInt($voidAreaIndex);
        $this->assertIsInt($hasBasementIndex);
        $this->assertIsInt($basementAreaIndex);
        $this->assertIsInt($basementTypeIndex);
        $this->assertLessThan($mezzanineAreaIndex, $hasMezzanineIndex);
        $this->assertLessThan($voidAreaIndex, $mezzanineAreaIndex);
        $this->assertLessThan($basementAreaIndex, $hasBasementIndex);
        $this->assertLessThan($basementTypeIndex, $basementAreaIndex);
    }

    public function test_estimator_level_profiles_expose_extended_fields_for_level_two_and_three(): void
    {
        $levelTwoFields = EstimatePageContent::estimatorVisibleFieldKeys('level_2');
        $levelThreeFields = EstimatePageContent::estimatorVisibleFieldKeys('level_3');
        $ui = EstimatePageContent::estimatorUi(EstimatePageContent::defaultConfig());

        $this->assertContains('usage_type', $levelTwoFields);
        $this->assertContains('bedroom_count', $levelTwoFields);
        $this->assertContains('district_zone', $levelTwoFields);
        $this->assertContains('facade_finish_type', $levelTwoFields);
        $this->assertContains('has_terrace', $levelTwoFields);
        $this->assertContains('elevator_service_floors', $levelTwoFields);
        $this->assertContains('pool_area', $levelTwoFields);
        $this->assertContains('pool_type', $levelTwoFields);
        $this->assertContains('include_permit', $levelThreeFields);
        $this->assertContains('include_pile', $levelThreeFields);
        $this->assertContains('include_completion', $levelThreeFields);
        $this->assertContains('formula_set', $levelThreeFields);
        $this->assertContains('internal_notes', $levelThreeFields);
        $this->assertSame('Dự toán nâng cao', data_get($ui, 'levels.1.name'));
        $this->assertSame('Dự toán nội bộ', data_get($ui, 'levels.2.name'));
        $this->assertSame('Công năng & nội thất', collect(data_get($ui, 'groups', []))->firstWhere('key', 'building_program')['label'] ?? null);
        $this->assertSame('Kiểm soát nội bộ', collect(data_get($ui, 'groups', []))->firstWhere('key', 'commercial')['label'] ?? null);
        $this->assertNotEmpty(data_get($ui, 'room_templates'));
        $this->assertNotEmpty(data_get($ui, 'price_books'));
        $this->assertSame('floors', data_get($ui, 'dynamic_dependencies.fields.elevator_service_floors.default_from'));
    }

    public function test_prepare_config_includes_estimate_hero_slider_defaults_and_normalizes_effect(): void
    {
        $config = EstimatePageContent::prepareConfig([
            'hero_slider' => [
                'slides' => [
                    [
                        'uuid' => 'hero-one',
                        'eyebrow' => 'SLIDE 1',
                        'title' => '<script>alert(1)</script><strong>Tiêu đề</strong>',
                        'description' => '<p>Mô tả <em>đậm hơn</em></p>',
                        'text_effect' => 'not-valid',
                    ],
                ],
            ],
        ]);

        $this->assertNotEmpty(data_get($config, 'hero_slider.slides'));
        $this->assertSame('hero-one', data_get($config, 'hero_slider.slides.0.uuid'));
        $this->assertStringNotContainsString('<script>', (string) data_get($config, 'hero_slider.slides.0.title'));
        $this->assertSame('animate__fadeInUp', data_get($config, 'hero_slider.slides.0.text_effect'));
        $this->assertSame('estimate-hero-slide-hero-one', EstimatePageContent::heroSlideCollection('hero one'));
    }

    public function test_estimator_formula_exposes_auto_terrace_keys_for_breakdown_and_illustration(): void
    {
        $ui = EstimatePageContent::estimatorUi(EstimatePageContent::defaultConfig());
        $terraceComponent = collect($ui['formula']['components'])->firstWhere('key', 'terrace');

        $this->assertArrayHasKey('typical_floor_count', $ui['formula']['derived']);
        $this->assertArrayHasKey('terrace_rooftop_area', $ui['formula']['derived']);
        $this->assertArrayHasKey('terrace_default_tum_area', $ui['formula']['derived']);
        $this->assertArrayHasKey('terrace_default_area', $ui['formula']['derived']);
        $this->assertArrayHasKey('tum_effective_area', $ui['formula']['derived']);
        $this->assertArrayHasKey('terrace_effective_area', $ui['formula']['derived']);
        $this->assertSame('has_terrace == true ? Math.max((floors ?? 0) - 2, 0) : Math.max((floors ?? 0) - 1, 0)', data_get($ui, 'formula.derived.typical_floor_count'));
        $this->assertSame('has_terrace == true ? (base_area * 1.25) : 0', data_get($ui, 'formula.derived.terrace_rooftop_area'));
        $this->assertSame('has_terrace == true ? (terrace_rooftop_area * 0.3) : 0', data_get($ui, 'formula.derived.terrace_default_tum_area'));
        $this->assertSame('has_terrace == true ? (terrace_rooftop_area - terrace_default_tum_area) : 0', data_get($ui, 'formula.derived.terrace_default_area'));
        $this->assertSame("terrace_cover_type == 'covered' ? 1 : (has_terrace == true ? 0.5 : 0.7)", data_get($terraceComponent, 'coefficient_from_expression'));
    }

    public function test_estimator_payload_validation_requires_terrace_split_to_match_rooftop_basis(): void
    {
        $errors = EstimatePageContent::estimatorInputPayloadErrors(
            EstimatePageContent::defaultConfig(),
            [
                ['slug' => 'length', 'value' => 4],
                ['slug' => 'width', 'value' => 14],
                ['slug' => 'floors', 'value' => 2],
                ['slug' => 'has_terrace', 'value' => true],
                ['slug' => 'tum_area', 'value' => 20],
                ['slug' => 'terrace_area', 'value' => 40],
                ['slug' => 'terrace_cover_type', 'value' => 'uncovered'],
                ['slug' => 'roof_type', 'value' => 'btct_no_tile'],
                ['slug' => 'foundation_type', 'value' => 'strip_or_pile'],
                ['slug' => 'finish_package', 'value' => 'standard_plus'],
            ],
            'level_1',
        );

        $this->assertContains(
            'Tổng diện tích tum và sân thượng phải bằng diện tích sân thượng quy đổi (125% diện tích sàn xây dựng).',
            $errors,
        );
    }
}
