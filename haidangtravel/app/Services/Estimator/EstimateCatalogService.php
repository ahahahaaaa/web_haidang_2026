<?php

namespace App\Services\Estimator;

use Illuminate\Support\Collection;
use Src\Domains\Estimator\Models\EstimateCatalogItem;
use Src\Domains\Estimator\Models\EstimateComponentNode;
use Src\Domains\Estimator\Models\EstimatePriceBook;
use Src\Domains\Estimator\Models\EstimateRoomTemplate;

class EstimateCatalogService
{
    public function uiPayload(): array
    {
        $nodes = EstimateComponentNode::query()
            ->with('parent')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $items = EstimateCatalogItem::query()
            ->with('componentNode')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $roomTemplates = EstimateRoomTemplate::query()
            ->where('is_active', true)
            ->with(['templateItems.catalogItem.componentNode'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $priceBooks = EstimatePriceBook::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->orderBy('name')
            ->get();

        return [
            'catalog_version' => $this->versionFromTimestamps($nodes->pluck('updated_at')->merge($items->pluck('updated_at'))),
            'room_template_version' => $this->versionFromTimestamps($roomTemplates->pluck('updated_at')),
            'nodes' => $this->serializeNodes($nodes),
            'tree' => $this->buildTree($nodes),
            'items' => $this->serializeItems($items),
            'room_templates' => $this->serializeRoomTemplates($roomTemplates),
            'room_types' => $this->roomTypes($roomTemplates),
            'price_books' => $this->serializePriceBooks($priceBooks),
        ];
    }

    public function itemLabel(?string $code): string
    {
        if (! filled($code)) {
            return '';
        }

        return (string) EstimateCatalogItem::query()->where('code', $code)->value('name');
    }

    public function roomTemplateLabel(?string $code): string
    {
        if (! filled($code)) {
            return '';
        }

        return (string) EstimateRoomTemplate::query()->where('code', $code)->value('name');
    }

    public function roomTypeLabel(?string $code): string
    {
        if (! filled($code)) {
            return '';
        }

        return (string) EstimateComponentNode::query()->where('code', $code)->value('name');
    }

    protected function serializeNodes(Collection $nodes): array
    {
        return $nodes
            ->map(fn (EstimateComponentNode $node) => [
                'code' => $node->code,
                'parent_code' => $node->parent?->code,
                'name' => $node->name,
                'node_type' => $node->node_type,
                'building_types' => $node->building_types ?? [],
                'sort_order' => $node->sort_order,
            ])
            ->values()
            ->all();
    }

    protected function serializeItems(Collection $items): array
    {
        return $items
            ->map(fn (EstimateCatalogItem $item) => [
                'code' => $item->code,
                'name' => $item->name,
                'component_node_code' => $item->componentNode?->code,
                'item_type' => $item->item_type,
                'unit' => $item->unit,
                'pricing_mode' => $item->pricing_mode,
                'default_quantity_formula' => $item->default_quantity_formula,
                'level_support' => $item->level_support ?? [],
                'building_types' => $item->building_types ?? [],
                'price_book_code' => $item->price_book_code,
            ])
            ->values()
            ->all();
    }

    protected function serializeRoomTemplates(Collection $roomTemplates): array
    {
        return $roomTemplates
            ->map(fn (EstimateRoomTemplate $template) => [
                'code' => $template->code,
                'name' => $template->name,
                'room_type' => $template->room_type,
                'room_type_label' => $template->templateItems->first()?->catalogItem?->componentNode?->name
                    ?: $this->roomTypeLabel($template->room_type),
                'building_types' => $template->building_types ?? [],
                'description' => $template->description,
                'sort_order' => $template->sort_order,
                'template_items' => $template->templateItems
                    ->map(fn ($item) => [
                        'catalog_item_code' => $item->catalogItem?->code,
                        'catalog_item_label' => $item->catalogItem?->name,
                        'default_quantity_formula' => $item->default_quantity_formula,
                        'sort_order' => $item->sort_order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    protected function roomTypes(Collection $roomTemplates): array
    {
        return $roomTemplates
            ->groupBy('room_type')
            ->map(function (Collection $items, string $roomType) {
                /** @var EstimateRoomTemplate|null $first */
                $first = $items->first();

                return [
                    'code' => $roomType,
                    'label' => $this->roomTypeLabel($roomType) ?: ($first?->name ?? $roomType),
                    'building_types' => $items
                        ->flatMap(fn (EstimateRoomTemplate $template) => $template->building_types ?? [])
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function serializePriceBooks(Collection $priceBooks): array
    {
        return $priceBooks
            ->map(fn (EstimatePriceBook $priceBook) => [
                'code' => $priceBook->code,
                'name' => $priceBook->name,
                'version' => $priceBook->version,
                'status' => $priceBook->status,
                'level_support' => $priceBook->level_support ?? [],
                'building_types' => $priceBook->building_types ?? [],
                'description' => $priceBook->description,
                'item_prices' => $priceBook->item_prices ?? [],
            ])
            ->values()
            ->all();
    }

    protected function buildTree(Collection $nodes): array
    {
        $grouped = $nodes->groupBy('parent_id');

        $build = function (?int $parentId) use (&$build, $grouped): array {
            return ($grouped->get($parentId) ?? collect())
                ->map(fn (EstimateComponentNode $node) => [
                    'code' => $node->code,
                    'name' => $node->name,
                    'node_type' => $node->node_type,
                    'building_types' => $node->building_types ?? [],
                    'children' => $build($node->getKey()),
                ])
                ->values()
                ->all();
        };

        return $build(null);
    }

    protected function versionFromTimestamps(Collection $timestamps): string
    {
        $latest = $timestamps->filter()->sort()->last();

        return $latest ? $latest->format('YmdHis') : 'seed-v1';
    }
}
