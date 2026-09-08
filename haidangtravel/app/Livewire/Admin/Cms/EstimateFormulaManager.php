<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Services\Cms\EstimateFormulaConfigService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Công thức dự toán')]
class EstimateFormulaManager extends Component
{
    use InteractsWithEditorContent;

    public array $form = [];
    public ?int $activeGuideEditorIndex = null;

    public function mount(EstimateFormulaConfigService $service): void
    {
        $this->form = $service->loadForm();
        $this->activeGuideEditorIndex = ($this->form['field_entries'] ?? []) !== [] ? 0 : null;
    }

    public function render()
    {
        return view('livewire.admin.cms.estimate-formula-manager');
    }

    public function save(EstimateFormulaConfigService $service): void
    {
        $validated = Validator::make(['form' => $this->form], [
            'form.formula_meta.code' => ['required', 'string', 'max:100'],
            'form.formula_meta.name' => ['required', 'string', 'max:255'],
            'form.formula_meta.version' => ['required', 'string', 'max:50'],
            'form.formula_meta.supports_levels_text' => ['nullable', 'string'],
            'form.derived_rows' => ['required', 'array', 'min:1'],
            'form.derived_rows.*.key' => ['required', 'string', 'max:100'],
            'form.derived_rows.*.expression' => ['required', 'string'],
            'form.components' => ['required', 'array', 'min:1'],
            'form.components.*.key' => ['required', 'string', 'max:100'],
            'form.components.*.section' => ['required', 'string', 'max:100'],
            'form.components.*.label' => ['required', 'string', 'max:255'],
            'form.components.*.condition' => ['nullable', 'string'],
            'form.components.*.area' => ['required', 'string'],
            'form.components.*.coefficient_mode' => ['required', Rule::in(['fixed', 'expression', 'option'])],
            'form.components.*.coefficient' => ['nullable', 'numeric'],
            'form.components.*.coefficient_expression' => ['nullable', 'string'],
            'form.components.*.option_field' => ['nullable', 'string', 'max:100'],
            'form.components.*.coefficient_options_text' => ['nullable', 'string'],
            'form.pricing_meta.code' => ['required', 'string', 'max:100'],
            'form.pricing_meta.version' => ['required', 'string', 'max:50'],
            'form.packages' => ['required', 'array', 'min:1'],
            'form.packages.*.key' => ['required', 'string', 'max:100'],
            'form.packages.*.label' => ['required', 'string', 'max:255'],
            'form.packages.*.unit_price' => ['required', 'numeric', 'min:0'],
            'form.packages.*.currency' => ['required', 'string', 'max:10'],
            'form.levels' => ['required', 'array', 'min:1'],
            'form.levels.*.code' => ['required', 'string', 'max:100'],
            'form.levels.*.name' => ['required', 'string', 'max:255'],
            'form.levels.*.purpose' => ['nullable', 'string'],
            'form.levels.*.extends' => ['nullable', 'string', 'max:100'],
            'form.levels.*.fields_text' => ['required', 'string'],
            'form.field_groups' => ['required', 'array', 'min:1'],
            'form.field_groups.*.key' => ['required', 'string', 'max:100'],
            'form.field_groups.*.label' => ['required', 'string', 'max:255'],
            'form.field_groups.*.fields_text' => ['required', 'string'],
            'form.field_entries' => ['required', 'array', 'min:1'],
            'form.field_entries.*.key' => ['required', 'string', 'max:100'],
            'form.field_entries.*.type' => ['required', 'string', 'max:50'],
            'form.field_entries.*.required_levels_text' => ['nullable', 'string'],
            'form.field_entries.*.guide_title' => ['nullable', 'string', 'max:255'],
            'form.field_entries.*.guide_content' => ['nullable', 'string'],
        ])->after(function ($validator) {
            $this->validateUniqueKeys($validator, $this->form['derived_rows'] ?? [], 'key', 'Biểu thức quy đổi');
            $this->validateUniqueKeys($validator, $this->form['components'] ?? [], 'key', 'Thành phần quy đổi');
            $this->validateUniqueKeys($validator, $this->form['packages'] ?? [], 'key', 'Gói đơn giá');
            $this->validateUniqueKeys($validator, $this->form['levels'] ?? [], 'code', 'Level dự toán');
            $this->validateUniqueKeys($validator, $this->form['field_groups'] ?? [], 'key', 'Nhóm field');
            $this->validateUniqueKeys($validator, $this->form['field_entries'] ?? [], 'key', 'Field catalog');

            foreach ($this->form['components'] ?? [] as $index => $component) {
                $mode = data_get($component, 'coefficient_mode', 'fixed');

                if ($mode === 'fixed' && trim((string) data_get($component, 'coefficient')) === '') {
                    $validator->errors()->add("form.components.{$index}.coefficient", 'Nhập hệ số cố định cho thành phần này.');
                }

                if ($mode === 'expression' && trim((string) data_get($component, 'coefficient_expression')) === '') {
                    $validator->errors()->add("form.components.{$index}.coefficient_expression", 'Nhập biểu thức hệ số cho thành phần này.');
                }

                if ($mode === 'option') {
                    if (trim((string) data_get($component, 'option_field')) === '') {
                        $validator->errors()->add("form.components.{$index}.option_field", 'Nhập field option để map hệ số.');
                    }

                    if (trim((string) data_get($component, 'coefficient_options_text')) === '') {
                        $validator->errors()->add("form.components.{$index}.coefficient_options_text", 'Nhập danh sách hệ số theo option.');
                    }
                }
            }
        })->validate();

        $payload = $validated['form'];
        $payload['field_entries'] = collect($payload['field_entries'] ?? [])
            ->map(function (array $field) {
                $field['guide_title'] = trim((string) data_get($field, 'guide_title'));
                $field['guide_content'] = $this->richEditorContent(data_get($field, 'guide_content'));

                return $field;
            })
            ->values()
            ->all();

        $service->saveForm($payload);
        $this->form = $service->loadForm();
        $fieldCount = count($this->form['field_entries'] ?? []);

        if ($fieldCount === 0) {
            $this->activeGuideEditorIndex = null;
        } elseif ($this->activeGuideEditorIndex === null || $this->activeGuideEditorIndex >= $fieldCount) {
            $this->activeGuideEditorIndex = 0;
        }

        session()->flash('status', 'Đã lưu cấu hình công thức, hệ số và quy đổi dự toán.');
    }

    public function openGuideEditor(int $index): void
    {
        $fieldCount = count($this->form['field_entries'] ?? []);

        if ($index < 0 || $index >= $fieldCount) {
            return;
        }

        $this->activeGuideEditorIndex = $index;
    }

    public function addDerivedRow(): void
    {
        $this->form['derived_rows'][] = [
            'key' => '',
            'expression' => '',
        ];
    }

    public function removeDerivedRow(int $index): void
    {
        $rows = $this->form['derived_rows'] ?? [];

        if (count($rows) <= 1) {
            return;
        }

        unset($rows[$index]);
        $this->form['derived_rows'] = array_values($rows);
    }

    public function addComponent(): void
    {
        $this->form['components'][] = $this->emptyComponent();
    }

    public function removeComponent(int $index): void
    {
        $rows = $this->form['components'] ?? [];

        if (count($rows) <= 1) {
            return;
        }

        unset($rows[$index]);
        $this->form['components'] = array_values($rows);
    }

    public function addPackage(): void
    {
        $this->form['packages'][] = [
            'key' => '',
            'label' => '',
            'unit_price' => '',
            'currency' => 'VND',
        ];
    }

    public function removePackage(int $index): void
    {
        $rows = $this->form['packages'] ?? [];

        if (count($rows) <= 1) {
            return;
        }

        unset($rows[$index]);
        $this->form['packages'] = array_values($rows);
    }

    protected function validateUniqueKeys(object $validator, array $rows, string $column, string $label): void
    {
        $duplicates = collect($rows)
            ->map(fn (array $row) => trim((string) data_get($row, $column)))
            ->filter()
            ->duplicates();

        if ($duplicates->isNotEmpty()) {
            $validator->errors()->add('form', $label.' đang có key bị trùng: '.$duplicates->unique()->implode(', ').'.');
        }
    }

    protected function emptyComponent(): array
    {
        return [
            'key' => '',
            'section' => '',
            'label' => '',
            'condition' => '',
            'area' => '',
            'coefficient_mode' => 'fixed',
            'coefficient' => '',
            'coefficient_expression' => '',
            'option_field' => '',
            'coefficient_options_text' => '',
        ];
    }
}
