<?php

namespace App\Livewire\Admin\Estimator;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Domains\Estimator\Models\EstimateCatalogItem;
use Src\Domains\Estimator\Models\EstimateRoomTemplate;
use Src\Domains\Estimator\Models\EstimateRoomTemplateItem;

#[Layout('layouts.app')]
#[Title('Template phòng dự toán')]
class EstimateRoomTemplatesManager extends Component
{
    public array $templates = [];

    public function mount(): void
    {
        $this->loadState();
    }

    public function render()
    {
        return view('livewire.admin.estimator.estimate-room-templates-manager');
    }

    public function save(): void
    {
        $this->validate([
            'templates' => ['required', 'array', 'min:1'],
            'templates.*.code' => ['required', 'string', 'max:100'],
            'templates.*.name' => ['required', 'string', 'max:255'],
            'templates.*.room_type' => ['required', 'string', 'max:100'],
            'templates.*.building_types_text' => ['nullable', 'string'],
            'templates.*.description' => ['nullable', 'string'],
            'templates.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'templates.*.is_active' => ['boolean'],
            'templates.*.item_lines' => ['nullable', 'string'],
        ]);

        $itemIds = EstimateCatalogItem::query()->pluck('id', 'code')->all();

        foreach ($this->templates as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $template = EstimateRoomTemplate::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'room_type' => trim((string) ($row['room_type'] ?? '')),
                    'building_types' => $this->parseList($row['building_types_text'] ?? ''),
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ],
            );

            EstimateRoomTemplateItem::query()->where('room_template_id', $template->getKey())->delete();

            foreach ($this->parseItemLines($row['item_lines'] ?? '') as $index => $itemLine) {
                if (! isset($itemIds[$itemLine['code']])) {
                    continue;
                }

                EstimateRoomTemplateItem::query()->create([
                    'room_template_id' => $template->getKey(),
                    'catalog_item_id' => $itemIds[$itemLine['code']],
                    'default_quantity_formula' => $itemLine['formula'],
                    'sort_order' => $index + 1,
                ]);
            }
        }

        $this->loadState();
        session()->flash('status', 'Đã lưu template phòng và preset nội thất.');
    }

    public function addTemplate(): void
    {
        $this->templates[] = [
            'code' => '',
            'name' => '',
            'room_type' => '',
            'building_types_text' => '',
            'description' => '',
            'sort_order' => count($this->templates) + 1,
            'is_active' => true,
            'item_lines' => '',
        ];
    }

    protected function loadState(): void
    {
        $this->templates = EstimateRoomTemplate::query()
            ->with(['templateItems.catalogItem'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (EstimateRoomTemplate $template) => [
                'code' => $template->code,
                'name' => $template->name,
                'room_type' => $template->room_type,
                'building_types_text' => implode(', ', $template->building_types ?? []),
                'description' => $template->description,
                'sort_order' => $template->sort_order,
                'is_active' => $template->is_active,
                'item_lines' => $template->templateItems
                    ->map(fn (EstimateRoomTemplateItem $item) => ($item->catalogItem?->code ?? '').'='.(trim((string) $item->default_quantity_formula) ?: '1'))
                    ->implode("\n"),
            ])
            ->all();
    }

    protected function parseList(mixed $value): array
    {
        return collect(preg_split('/[\r\n,]+/', trim((string) $value)) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function parseItemLines(mixed $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', trim((string) $value)) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                $segments = preg_split('/=|:/', $line, 2) ?: [];

                return [
                    'code' => trim((string) ($segments[0] ?? '')),
                    'formula' => trim((string) ($segments[1] ?? '1')) ?: '1',
                ];
            })
            ->filter(fn (array $line) => $line['code'] !== '')
            ->values()
            ->all();
    }
}
