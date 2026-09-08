<?php

namespace Tests\Unit;

use App\Services\Cms\EstimateFormulaConfigService;
use PHPUnit\Framework\TestCase;

class EstimateFormulaConfigServiceTest extends TestCase
{
    protected string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'estimate-config-'.uniqid();

        mkdir($this->rootPath.DIRECTORY_SEPARATOR.'examples', 0777, true);
        mkdir($this->rootPath.DIRECTORY_SEPARATOR.'configs', 0777, true);

        file_put_contents($this->rootPath.DIRECTORY_SEPARATOR.'examples'.DIRECTORY_SEPARATOR.'formula.json', json_encode([
            'meta' => [
                'code' => 'default',
                'version' => 'v1',
                'name' => 'Default',
                'supports_levels' => ['level_1', 'level_2'],
            ],
            'derived' => [
                'base_area' => 'length * width',
            ],
            'components' => [
                [
                    'key' => 'ground_floor',
                    'section' => 'floor',
                    'label' => 'Tổng Trệt',
                    'area' => 'base_area',
                    'coefficient' => 1,
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        file_put_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'default-pricing.json', json_encode([
            'meta' => [
                'code' => 'default',
                'version' => 'v1',
            ],
            'packages' => [
                [
                    'key' => 'construction_standard',
                    'label' => 'Thi công tiêu chuẩn',
                    'unit_price' => 4200000,
                    'currency' => 'VND',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        file_put_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'field-catalog.json', json_encode([
            'groups' => [
                [
                    'key' => 'geometry',
                    'label' => 'Hình học',
                    'fields' => ['length', 'width'],
                ],
            ],
            'fields' => [
                'length' => ['type' => 'number', 'required_levels' => ['level_1']],
                'width' => ['type' => 'number', 'required_levels' => ['level_1']],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        file_put_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'estimation-levels.json', json_encode([
            'levels' => [
                'level_1' => [
                    'name' => 'Dự toán nhanh',
                    'purpose' => 'quick estimate',
                    'fields' => ['length', 'width'],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function tearDown(): void
    {
        @unlink($this->rootPath.DIRECTORY_SEPARATOR.'examples'.DIRECTORY_SEPARATOR.'formula.json');
        @unlink($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'default-pricing.json');
        @unlink($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'field-catalog.json');
        @unlink($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'estimation-levels.json');
        @rmdir($this->rootPath.DIRECTORY_SEPARATOR.'examples');
        @rmdir($this->rootPath.DIRECTORY_SEPARATOR.'configs');
        @rmdir($this->rootPath);

        parent::tearDown();
    }

    public function test_it_can_persist_estimator_configs_from_admin_form(): void
    {
        $service = new EstimateFormulaConfigService($this->rootPath);
        $form = $service->loadForm();

        $form['formula_meta']['version'] = 'v2';
        $form['derived_rows'][] = [
            'key' => 'roof_effective_area',
            'expression' => 'roof_area ?? base_area',
        ];
        $form['components'][] = [
            'key' => 'roof',
            'section' => 'roof',
            'label' => 'Mái',
            'condition' => 'roof_type != null',
            'area' => 'roof_effective_area',
            'coefficient_mode' => 'option',
            'coefficient' => '',
            'coefficient_expression' => '',
            'option_field' => 'roof_type',
            'coefficient_options_text' => "btct_no_tile=0.5\nbtct_tile=0.7",
        ];
        $form['packages'][0]['unit_price'] = '4300000';
        $form['levels'][0]['fields_text'] = "length\nwidth\nfloors";
        $form['field_entries'][] = [
            'key' => 'floors',
            'type' => 'integer',
            'required_levels_text' => 'level_1',
            'guide_title' => 'Số tầng là gì?',
            'guide_content' => 'Nhập tổng số tầng bao gồm cả tầng trệt.',
        ];
        $form['field_entries'][0]['guide_title'] = 'Chiều dài công trình';
        $form['field_entries'][0]['guide_content'] = 'Nhập chiều dài phủ bì thực tế theo hồ sơ.';
        $form['field_groups'][0]['fields_text'] = "length\nwidth\nfloors";

        $service->saveForm($form);

        $formula = json_decode((string) file_get_contents($this->rootPath.DIRECTORY_SEPARATOR.'examples'.DIRECTORY_SEPARATOR.'formula.json'), true);
        $pricing = json_decode((string) file_get_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'default-pricing.json'), true);
        $fieldCatalog = json_decode((string) file_get_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'field-catalog.json'), true);
        $levels = json_decode((string) file_get_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'estimation-levels.json'), true);

        $this->assertSame('v2', data_get($formula, 'meta.version'));
        $this->assertSame('roof_area ?? base_area', data_get($formula, 'derived.roof_effective_area'));
        $this->assertSame('roof_type', data_get($formula, 'components.1.option_field'));
        $this->assertSame(0.7, data_get($formula, 'components.1.coefficient_from_option.btct_tile'));
        $this->assertSame(4300000, data_get($pricing, 'packages.0.unit_price'));
        $this->assertSame(['length', 'width', 'floors'], data_get($levels, 'levels.level_1.fields'));
        $this->assertSame('integer', data_get($fieldCatalog, 'fields.floors.type'));
        $this->assertSame('Chiều dài công trình', data_get($fieldCatalog, 'fields.length.guide_title'));
        $this->assertSame('Nhập chiều dài phủ bì thực tế theo hồ sơ.', data_get($fieldCatalog, 'fields.length.guide_content'));
        $this->assertSame('Số tầng là gì?', data_get($fieldCatalog, 'fields.floors.guide_title'));
        $this->assertSame('Nhập tổng số tầng bao gồm cả tầng trệt.', data_get($fieldCatalog, 'fields.floors.guide_content'));
        $this->assertSame(['length', 'width', 'floors'], data_get($fieldCatalog, 'groups.0.fields'));
    }

    public function test_it_loads_field_entries_from_groups_and_level_profiles_even_when_catalog_types_are_missing(): void
    {
        file_put_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'field-catalog.json', json_encode([
            'groups' => [
                [
                    'key' => 'program',
                    'label' => 'Công năng',
                    'fields' => ['usage_type'],
                ],
            ],
            'fields' => [
                'length' => ['type' => 'number', 'required_levels' => ['level_1']],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        file_put_contents($this->rootPath.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'estimation-levels.json', json_encode([
            'levels' => [
                'level_1' => [
                    'name' => 'Dự toán nhanh',
                    'purpose' => 'quick estimate',
                    'fields' => ['length'],
                ],
                'level_2' => [
                    'name' => 'Dự toán nâng cao',
                    'purpose' => 'advanced estimate',
                    'extends' => 'level_1',
                    'fields' => ['usage_type', 'include_permit'],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $service = new EstimateFormulaConfigService($this->rootPath);
        $form = $service->loadForm();
        $entries = collect($form['field_entries']);

        $this->assertTrue($entries->contains(fn (array $field) => $field['key'] === 'usage_type' && $field['type'] === 'select'));
        $this->assertTrue($entries->contains(fn (array $field) => $field['key'] === 'include_permit' && $field['type'] === 'boolean'));
    }
}
