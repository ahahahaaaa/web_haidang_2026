<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Domains\Estimator\Models\EstimateCatalogItem;
use Src\Domains\Estimator\Models\EstimateComponentNode;
use Src\Domains\Estimator\Models\EstimatePriceBook;
use Src\Domains\Estimator\Models\EstimateRoomTemplate;
use Src\Domains\Estimator\Models\EstimateRoomTemplateItem;
use Src\Domains\Estimator\Support\DefaultEstimatorCatalog;

class EstimateCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $nodeMap = [];

        foreach (DefaultEstimatorCatalog::componentNodes() as $definition) {
            $node = EstimateComponentNode::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'parent_id' => isset($definition['parent_code']) ? ($nodeMap[$definition['parent_code']] ?? null) : null,
                    'name' => $definition['name'],
                    'node_type' => $definition['node_type'] ?? 'group',
                    'building_types' => $definition['building_types'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_active' => $definition['is_active'] ?? true,
                ],
            );

            $nodeMap[$definition['code']] = $node->getKey();
        }

        $itemMap = [];

        foreach (DefaultEstimatorCatalog::catalogItems() as $definition) {
            $item = EstimateCatalogItem::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'component_node_id' => $nodeMap[$definition['node_code']],
                    'name' => $definition['name'],
                    'item_type' => $definition['item_type'],
                    'unit' => $definition['unit'],
                    'pricing_mode' => $definition['pricing_mode'],
                    'default_quantity_formula' => $definition['default_quantity_formula'] ?? null,
                    'level_support' => $definition['level_support'] ?? null,
                    'building_types' => $definition['building_types'] ?? null,
                    'price_book_code' => $definition['price_book_code'] ?? null,
                    'is_active' => $definition['is_active'] ?? true,
                ],
            );

            $itemMap[$definition['code']] = $item->getKey();
        }

        $templateMap = [];

        foreach (DefaultEstimatorCatalog::roomTemplates() as $definition) {
            $template = EstimateRoomTemplate::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'room_type' => $definition['room_type'],
                    'building_types' => $definition['building_types'] ?? null,
                    'description' => $definition['description'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_active' => $definition['is_active'] ?? true,
                ],
            );

            $templateMap[$definition['code']] = $template->getKey();
        }

        foreach (DefaultEstimatorCatalog::roomTemplateItems() as $definition) {
            EstimateRoomTemplateItem::query()->updateOrCreate(
                [
                    'room_template_id' => $templateMap[$definition['template_code']],
                    'catalog_item_id' => $itemMap[$definition['catalog_item_code']],
                ],
                [
                    'default_quantity_formula' => $definition['default_quantity_formula'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                ],
            );
        }

        foreach (DefaultEstimatorCatalog::priceBooks() as $definition) {
            EstimatePriceBook::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'version' => $definition['version'] ?? 'v1',
                    'status' => $definition['status'] ?? 'draft',
                    'level_support' => $definition['level_support'] ?? null,
                    'building_types' => $definition['building_types'] ?? null,
                    'item_prices' => $definition['item_prices'] ?? [],
                    'description' => $definition['description'] ?? null,
                    'published_at' => $definition['published_at'] ?? null,
                    'is_active' => $definition['is_active'] ?? true,
                ],
            );
        }
    }
}
