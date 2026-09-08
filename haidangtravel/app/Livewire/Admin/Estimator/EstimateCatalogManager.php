<?php

namespace App\Livewire\Admin\Estimator;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Domains\Estimator\Models\EstimateCatalogItem;
use Src\Domains\Estimator\Models\EstimateComponentNode;
use Src\Domains\Estimator\Models\EstimatePriceBook;

#[Layout('layouts.app')]
#[Title('Catalog dự toán')]
class EstimateCatalogManager extends Component
{
    public array $nodes = [];
    public array $items = [];

    public function mount(): void
    {
        $this->loadState();
    }

    public function render()
    {
        return view('livewire.admin.estimator.estimate-catalog-manager', [
            'priceBookCodes' => EstimatePriceBook::query()->orderBy('code')->pluck('code')->all(),
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'nodes' => ['required', 'array', 'min:1'],
            'nodes.*.code' => ['required', 'string', 'max:100'],
            'nodes.*.name' => ['required', 'string', 'max:255'],
            'nodes.*.node_type' => ['required', 'string', 'max:50'],
            'nodes.*.parent_code' => ['nullable', 'string', 'max:100'],
            'nodes.*.building_types_text' => ['nullable', 'string'],
            'nodes.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'nodes.*.is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.component_node_code' => ['required', 'string', 'max:100'],
            'items.*.item_type' => ['required', 'string', 'max:50'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.pricing_mode' => ['required', 'string', 'max:50'],
            'items.*.default_quantity_formula' => ['nullable', 'string'],
            'items.*.level_support_text' => ['nullable', 'string'],
            'items.*.building_types_text' => ['nullable', 'string'],
            'items.*.price_book_code' => ['nullable', 'string', 'max:100'],
            'items.*.is_active' => ['boolean'],
        ]);

        foreach ($this->nodes as $index => $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            EstimateComponentNode::query()->updateOrCreate(
                ['code' => $code],
                [
                    'parent_id' => null,
                    'name' => $name,
                    'node_type' => trim((string) ($row['node_type'] ?? 'group')) ?: 'group',
                    'building_types' => $this->parseList($row['building_types_text'] ?? ''),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ],
            );
        }

        $nodeIdsByCode = EstimateComponentNode::query()->pluck('id', 'code')->all();

        foreach ($this->nodes as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $parentCode = trim((string) ($row['parent_code'] ?? ''));

            if ($code === '') {
                continue;
            }

            EstimateComponentNode::query()
                ->where('code', $code)
                ->update([
                    'parent_id' => $parentCode !== '' ? ($nodeIdsByCode[$parentCode] ?? null) : null,
                ]);
        }

        foreach ($this->items as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $nodeCode = trim((string) ($row['component_node_code'] ?? ''));

            if ($code === '' || $name === '' || $nodeCode === '' || ! isset($nodeIdsByCode[$nodeCode])) {
                continue;
            }

            EstimateCatalogItem::query()->updateOrCreate(
                ['code' => $code],
                [
                    'component_node_id' => $nodeIdsByCode[$nodeCode],
                    'name' => $name,
                    'item_type' => trim((string) ($row['item_type'] ?? 'addon')) ?: 'addon',
                    'unit' => trim((string) ($row['unit'] ?? 'item')) ?: 'item',
                    'pricing_mode' => trim((string) ($row['pricing_mode'] ?? 'fixed')) ?: 'fixed',
                    'default_quantity_formula' => trim((string) ($row['default_quantity_formula'] ?? '')) ?: null,
                    'level_support' => $this->parseList($row['level_support_text'] ?? ''),
                    'building_types' => $this->parseList($row['building_types_text'] ?? ''),
                    'price_book_code' => trim((string) ($row['price_book_code'] ?? '')) ?: null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ],
            );
        }

        $this->loadState();
        session()->flash('status', 'Đã lưu catalog thành phần và item báo giá.');
    }

    public function addNode(): void
    {
        $this->nodes[] = [
            'code' => '',
            'name' => '',
            'node_type' => 'subcategory',
            'parent_code' => '',
            'building_types_text' => '',
            'sort_order' => count($this->nodes) + 1,
            'is_active' => true,
        ];
    }

    public function addItem(): void
    {
        $this->items[] = [
            'code' => '',
            'name' => '',
            'component_node_code' => '',
            'item_type' => 'addon',
            'unit' => 'item',
            'pricing_mode' => 'fixed',
            'default_quantity_formula' => '',
            'level_support_text' => '',
            'building_types_text' => '',
            'price_book_code' => '',
            'is_active' => true,
        ];
    }

    protected function loadState(): void
    {
        $this->nodes = EstimateComponentNode::query()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (EstimateComponentNode $node) => [
                'code' => $node->code,
                'name' => $node->name,
                'node_type' => $node->node_type,
                'parent_code' => $node->parent?->code,
                'building_types_text' => implode(', ', $node->building_types ?? []),
                'sort_order' => $node->sort_order,
                'is_active' => $node->is_active,
            ])
            ->all();

        $this->items = EstimateCatalogItem::query()
            ->with('componentNode')
            ->orderBy('name')
            ->get()
            ->map(fn (EstimateCatalogItem $item) => [
                'code' => $item->code,
                'name' => $item->name,
                'component_node_code' => $item->componentNode?->code,
                'item_type' => $item->item_type,
                'unit' => $item->unit,
                'pricing_mode' => $item->pricing_mode,
                'default_quantity_formula' => $item->default_quantity_formula,
                'level_support_text' => implode(', ', $item->level_support ?? []),
                'building_types_text' => implode(', ', $item->building_types ?? []),
                'price_book_code' => $item->price_book_code,
                'is_active' => $item->is_active,
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
}
