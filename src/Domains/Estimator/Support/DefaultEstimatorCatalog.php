<?php

namespace Src\Domains\Estimator\Support;

class DefaultEstimatorCatalog
{
    public static function componentNodes(): array
    {
        return [
            ['code' => 'site_preparation', 'name' => 'Chuẩn bị & ngoài nhà', 'node_type' => 'group', 'sort_order' => 10],
            ['code' => 'site_access', 'parent_code' => 'site_preparation', 'name' => 'Chuẩn bị mặt bằng', 'node_type' => 'subcategory', 'sort_order' => 11],
            ['code' => 'yard_landscape', 'parent_code' => 'site_preparation', 'name' => 'Sân vườn & cảnh quan', 'node_type' => 'subcategory', 'sort_order' => 12],

            ['code' => 'foundation_basement', 'name' => 'Móng - hầm - nền', 'node_type' => 'group', 'sort_order' => 20],
            ['code' => 'pile_foundation', 'parent_code' => 'foundation_basement', 'name' => 'Móng cọc & nền móng', 'node_type' => 'subcategory', 'sort_order' => 21],
            ['code' => 'basement_retaining', 'parent_code' => 'foundation_basement', 'name' => 'Hầm & tường vây', 'node_type' => 'subcategory', 'sort_order' => 22],

            ['code' => 'superstructure', 'name' => 'Kết cấu thân', 'node_type' => 'group', 'sort_order' => 30],
            ['code' => 'superstructure_frame', 'parent_code' => 'superstructure', 'name' => 'Khung cột - dầm - sàn', 'node_type' => 'subcategory', 'sort_order' => 31],
            ['code' => 'roof_system', 'parent_code' => 'superstructure', 'name' => 'Mái', 'node_type' => 'subcategory', 'sort_order' => 32],

            ['code' => 'envelope_finishing', 'name' => 'Bao che - hoàn thiện', 'node_type' => 'group', 'sort_order' => 40],
            ['code' => 'facade_finish', 'parent_code' => 'envelope_finishing', 'name' => 'Hoàn thiện mặt ngoài', 'node_type' => 'subcategory', 'sort_order' => 41],
            ['code' => 'interior_finish', 'parent_code' => 'envelope_finishing', 'name' => 'Hoàn thiện bên trong', 'node_type' => 'subcategory', 'sort_order' => 42],

            ['code' => 'mep', 'name' => 'MEP', 'node_type' => 'group', 'sort_order' => 50],
            ['code' => 'electrical_system', 'parent_code' => 'mep', 'name' => 'Điện', 'node_type' => 'subcategory', 'sort_order' => 51],
            ['code' => 'plumbing_system', 'parent_code' => 'mep', 'name' => 'Cấp thoát nước', 'node_type' => 'subcategory', 'sort_order' => 52],
            ['code' => 'hvac_system', 'parent_code' => 'mep', 'name' => 'Điều hòa & thông gió', 'node_type' => 'subcategory', 'sort_order' => 53],

            ['code' => 'room_interiors', 'name' => 'Nội thất theo phòng', 'node_type' => 'group', 'sort_order' => 60],
            ['code' => 'living_room', 'parent_code' => 'room_interiors', 'name' => 'Phòng khách', 'node_type' => 'room', 'sort_order' => 61, 'building_types' => ['townhouse', 'villa', 'shophouse']],
            ['code' => 'kitchen_dining', 'parent_code' => 'room_interiors', 'name' => 'Bếp & ăn', 'node_type' => 'room', 'sort_order' => 62, 'building_types' => ['townhouse', 'villa', 'shophouse']],
            ['code' => 'bedroom', 'parent_code' => 'room_interiors', 'name' => 'Phòng ngủ', 'node_type' => 'room', 'sort_order' => 63, 'building_types' => ['townhouse', 'villa', 'shophouse']],
            ['code' => 'master_bedroom', 'parent_code' => 'room_interiors', 'name' => 'Phòng ngủ master', 'node_type' => 'room', 'sort_order' => 64, 'building_types' => ['townhouse', 'villa']],
            ['code' => 'wc_room', 'parent_code' => 'room_interiors', 'name' => 'WC / phòng tắm', 'node_type' => 'room', 'sort_order' => 65, 'building_types' => ['townhouse', 'villa', 'office', 'shophouse']],
            ['code' => 'worship_room', 'parent_code' => 'room_interiors', 'name' => 'Phòng thờ', 'node_type' => 'room', 'sort_order' => 66, 'building_types' => ['townhouse', 'villa']],
            ['code' => 'laundry_room', 'parent_code' => 'room_interiors', 'name' => 'Giặt phơi', 'node_type' => 'room', 'sort_order' => 67, 'building_types' => ['townhouse', 'villa', 'shophouse']],
            ['code' => 'office_reception', 'parent_code' => 'room_interiors', 'name' => 'Quầy lễ tân', 'node_type' => 'room', 'sort_order' => 68, 'building_types' => ['office']],
            ['code' => 'office_workspace', 'parent_code' => 'room_interiors', 'name' => 'Không gian làm việc', 'node_type' => 'room', 'sort_order' => 69, 'building_types' => ['office']],
            ['code' => 'meeting_room', 'parent_code' => 'room_interiors', 'name' => 'Phòng họp', 'node_type' => 'room', 'sort_order' => 70, 'building_types' => ['office', 'shophouse']],
            ['code' => 'director_room', 'parent_code' => 'room_interiors', 'name' => 'Phòng giám đốc', 'node_type' => 'room', 'sort_order' => 71, 'building_types' => ['office']],
            ['code' => 'showroom_space', 'parent_code' => 'room_interiors', 'name' => 'Khu trưng bày / showroom', 'node_type' => 'room', 'sort_order' => 72, 'building_types' => ['shophouse']],

            ['code' => 'special_items', 'name' => 'Hạng mục đặc biệt', 'node_type' => 'group', 'sort_order' => 80],
            ['code' => 'elevator_system', 'parent_code' => 'special_items', 'name' => 'Thang máy', 'node_type' => 'subcategory', 'sort_order' => 81],
            ['code' => 'pool_system', 'parent_code' => 'special_items', 'name' => 'Hồ bơi', 'node_type' => 'subcategory', 'sort_order' => 82],
            ['code' => 'special_addons', 'parent_code' => 'special_items', 'name' => 'Add-on kỹ thuật', 'node_type' => 'subcategory', 'sort_order' => 83],

            ['code' => 'design_services', 'name' => 'Thiết kế', 'node_type' => 'group', 'sort_order' => 90],
            ['code' => 'architecture_design', 'parent_code' => 'design_services', 'name' => 'Thiết kế kiến trúc', 'node_type' => 'subcategory', 'sort_order' => 91],
            ['code' => 'interior_design', 'parent_code' => 'design_services', 'name' => 'Thiết kế nội thất', 'node_type' => 'subcategory', 'sort_order' => 92],
            ['code' => 'mep_design', 'parent_code' => 'design_services', 'name' => 'Thiết kế MEP', 'node_type' => 'subcategory', 'sort_order' => 93],
        ];
    }

    public static function catalogItems(): array
    {
        return [
            ['code' => 'site_setup_standard', 'node_code' => 'site_access', 'name' => 'Chuẩn bị mặt bằng cơ bản', 'item_type' => 'component', 'unit' => 'gói', 'pricing_mode' => 'fixed', 'default_quantity_formula' => '1', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'landscape_basic', 'node_code' => 'yard_landscape', 'name' => 'Cảnh quan & sân ngoài trời', 'item_type' => 'component', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => '(garden_area ?? 0) + (front_yard_area ?? 0) + (back_yard_area ?? 0)', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'pile_package_standard', 'node_code' => 'pile_foundation', 'name' => 'Gói cọc tiêu chuẩn', 'item_type' => 'component', 'unit' => 'cọc', 'pricing_mode' => 'per_item', 'default_quantity_formula' => 'pile_count ?? 0', 'level_support' => ['level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'internal-2026-v1'],
            ['code' => 'retaining_wall_package', 'node_code' => 'basement_retaining', 'name' => 'Tường vây / retaining wall', 'item_type' => 'component', 'unit' => 'gói', 'pricing_mode' => 'fixed', 'default_quantity_formula' => 'retaining_wall == true ? 1 : 0', 'level_support' => ['level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'internal-2026-v1'],
            ['code' => 'facade_lighting_upgrade', 'node_code' => 'facade_finish', 'name' => 'Lighting mặt đứng nâng cao', 'item_type' => 'addon', 'unit' => 'gói', 'pricing_mode' => 'fixed', 'default_quantity_formula' => '1', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'smart_home_basic', 'node_code' => 'special_addons', 'name' => 'Smart home cơ bản', 'item_type' => 'addon', 'unit' => 'gói', 'pricing_mode' => 'fixed', 'default_quantity_formula' => '1', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'solar_roof_basic', 'node_code' => 'special_addons', 'name' => 'Điện mặt trời áp mái', 'item_type' => 'addon', 'unit' => 'gói', 'pricing_mode' => 'fixed', 'default_quantity_formula' => '1', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'elevator_standard', 'node_code' => 'elevator_system', 'name' => 'Thang máy tải khách tiêu chuẩn', 'item_type' => 'special', 'unit' => 'tầng phục vụ', 'pricing_mode' => 'per_floor', 'default_quantity_formula' => 'elevator_service_floors ?? floors ?? 0', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'pool_standard', 'node_code' => 'pool_system', 'name' => 'Hồ bơi skimmer tiêu chuẩn', 'item_type' => 'special', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => 'pool_area ?? 0', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'pool_overflow', 'node_code' => 'pool_system', 'name' => 'Hồ bơi overflow', 'item_type' => 'special', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => 'pool_area ?? 0', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'pool_jacuzzi', 'node_code' => 'pool_system', 'name' => 'Jacuzzi / plunge pool', 'item_type' => 'special', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => 'pool_area ?? 0', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'design_architecture_basic', 'node_code' => 'architecture_design', 'name' => 'Thiết kế kiến trúc cơ bản', 'item_type' => 'design', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => 'base_area * Math.max(floors ?? 1, 1)', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'design_interior_package', 'node_code' => 'interior_design', 'name' => 'Thiết kế nội thất đồng bộ', 'item_type' => 'design', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => 'base_area * Math.max(floors ?? 1, 1)', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'design_mep_package', 'node_code' => 'mep_design', 'name' => 'Thiết kế MEP chi tiết', 'item_type' => 'design', 'unit' => 'm2', 'pricing_mode' => 'per_m2', 'default_quantity_formula' => 'base_area * Math.max(floors ?? 1, 1)', 'level_support' => ['level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'internal-2026-v1'],
            ['code' => 'living_room_standard', 'node_code' => 'living_room', 'name' => 'Nội thất phòng khách tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'kitchen_standard', 'node_code' => 'kitchen_dining', 'name' => 'Nội thất bếp & ăn tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'bedroom_standard', 'node_code' => 'bedroom', 'name' => 'Nội thất phòng ngủ tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'master_bedroom_premium', 'node_code' => 'master_bedroom', 'name' => 'Nội thất phòng ngủ master', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'wc_standard', 'node_code' => 'wc_room', 'name' => 'Nội thất WC tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'worship_room_standard', 'node_code' => 'worship_room', 'name' => 'Nội thất phòng thờ', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'laundry_standard', 'node_code' => 'laundry_room', 'name' => 'Khu giặt phơi hoàn thiện', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'office_reception_standard', 'node_code' => 'office_reception', 'name' => 'Quầy lễ tân tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['office'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'workspace_standard', 'node_code' => 'office_workspace', 'name' => 'Không gian làm việc tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['office'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'meeting_room_standard', 'node_code' => 'meeting_room', 'name' => 'Phòng họp tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'director_room_standard', 'node_code' => 'director_room', 'name' => 'Phòng giám đốc tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['office'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'showroom_display_standard', 'node_code' => 'showroom_space', 'name' => 'Khu trưng bày / showroom tiêu chuẩn', 'item_type' => 'interior', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['shophouse'], 'price_book_code' => 'advanced-2026-v1'],
            ['code' => 'loose_furniture_upgrade', 'node_code' => 'interior_finish', 'name' => 'Bổ sung loose furniture', 'item_type' => 'addon', 'unit' => 'phòng', 'pricing_mode' => 'per_room', 'default_quantity_formula' => 'room_count', 'level_support' => ['level_2', 'level_3'], 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'price_book_code' => 'advanced-2026-v1'],
        ];
    }

    public static function roomTemplates(): array
    {
        return [
            ['code' => 'res_living_standard', 'name' => 'Mẫu phòng khách nhà ở', 'room_type' => 'living_room', 'building_types' => ['townhouse', 'villa', 'shophouse'], 'description' => 'Bộ nội thất chuẩn cho phòng khách nhà ở.', 'sort_order' => 10],
            ['code' => 'res_kitchen_standard', 'name' => 'Mẫu bếp & ăn nhà ở', 'room_type' => 'kitchen_dining', 'building_types' => ['townhouse', 'villa', 'shophouse'], 'description' => 'Bộ nội thất bếp & ăn tiêu chuẩn.', 'sort_order' => 20],
            ['code' => 'res_bedroom_standard', 'name' => 'Mẫu phòng ngủ tiêu chuẩn', 'room_type' => 'bedroom', 'building_types' => ['townhouse', 'villa', 'shophouse'], 'description' => 'Bộ nội thất phòng ngủ cơ bản.', 'sort_order' => 30],
            ['code' => 'res_master_bedroom', 'name' => 'Mẫu phòng ngủ master', 'room_type' => 'master_bedroom', 'building_types' => ['townhouse', 'villa'], 'description' => 'Bộ nội thất phòng ngủ master.', 'sort_order' => 40],
            ['code' => 'res_wc_standard', 'name' => 'Mẫu WC tiêu chuẩn', 'room_type' => 'wc_room', 'building_types' => ['townhouse', 'villa', 'office', 'shophouse'], 'description' => 'Bộ thiết bị & hoàn thiện WC.', 'sort_order' => 50],
            ['code' => 'res_worship_standard', 'name' => 'Mẫu phòng thờ', 'room_type' => 'worship_room', 'building_types' => ['townhouse', 'villa'], 'description' => 'Bộ nội thất phòng thờ cơ bản.', 'sort_order' => 60],
            ['code' => 'res_laundry_standard', 'name' => 'Mẫu giặt phơi', 'room_type' => 'laundry_room', 'building_types' => ['townhouse', 'villa', 'shophouse'], 'description' => 'Bộ hoàn thiện khu giặt phơi.', 'sort_order' => 70],
            ['code' => 'office_reception_standard', 'name' => 'Mẫu quầy lễ tân', 'room_type' => 'office_reception', 'building_types' => ['office'], 'description' => 'Bộ tiêu chuẩn cho khu lễ tân.', 'sort_order' => 80],
            ['code' => 'office_workspace_standard', 'name' => 'Mẫu không gian làm việc', 'room_type' => 'office_workspace', 'building_types' => ['office'], 'description' => 'Bộ tiêu chuẩn cho khu làm việc.', 'sort_order' => 90],
            ['code' => 'office_meeting_standard', 'name' => 'Mẫu phòng họp', 'room_type' => 'meeting_room', 'building_types' => ['office', 'shophouse'], 'description' => 'Bộ tiêu chuẩn cho phòng họp.', 'sort_order' => 100],
            ['code' => 'office_director_standard', 'name' => 'Mẫu phòng giám đốc', 'room_type' => 'director_room', 'building_types' => ['office'], 'description' => 'Bộ tiêu chuẩn cho phòng giám đốc.', 'sort_order' => 110],
            ['code' => 'shophouse_showroom_standard', 'name' => 'Mẫu showroom chuẩn', 'room_type' => 'showroom_space', 'building_types' => ['shophouse'], 'description' => 'Bộ tiêu chuẩn cho không gian trưng bày.', 'sort_order' => 120],
        ];
    }

    public static function roomTemplateItems(): array
    {
        return [
            ['template_code' => 'res_living_standard', 'catalog_item_code' => 'living_room_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'res_kitchen_standard', 'catalog_item_code' => 'kitchen_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'res_bedroom_standard', 'catalog_item_code' => 'bedroom_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'res_master_bedroom', 'catalog_item_code' => 'master_bedroom_premium', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'res_wc_standard', 'catalog_item_code' => 'wc_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'res_worship_standard', 'catalog_item_code' => 'worship_room_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'res_laundry_standard', 'catalog_item_code' => 'laundry_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'office_reception_standard', 'catalog_item_code' => 'office_reception_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'office_workspace_standard', 'catalog_item_code' => 'workspace_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'office_meeting_standard', 'catalog_item_code' => 'meeting_room_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'office_director_standard', 'catalog_item_code' => 'director_room_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
            ['template_code' => 'shophouse_showroom_standard', 'catalog_item_code' => 'showroom_display_standard', 'default_quantity_formula' => 'room_count', 'sort_order' => 10],
        ];
    }

    public static function priceBooks(): array
    {
        return [
            [
                'code' => 'advanced-2026-v1',
                'name' => 'Bảng giá nâng cao 2026',
                'version' => '2026.v1',
                'status' => 'published',
                'level_support' => ['level_2'],
                'building_types' => ['townhouse', 'villa', 'office', 'shophouse'],
                'description' => 'Bảng giá tham chiếu cho dự toán nâng cao.',
                'published_at' => now(),
                'is_active' => true,
                'item_prices' => [
                    'site_setup_standard' => 35000000,
                    'landscape_basic' => 950000,
                    'facade_lighting_upgrade' => 28000000,
                    'smart_home_basic' => 45000000,
                    'solar_roof_basic' => 120000000,
                    'elevator_standard' => 38000000,
                    'pool_standard' => 14500000,
                    'pool_overflow' => 18500000,
                    'pool_jacuzzi' => 21000000,
                    'design_architecture_basic' => 180000,
                    'design_interior_package' => 150000,
                    'living_room_standard' => 68000000,
                    'kitchen_standard' => 85000000,
                    'bedroom_standard' => 42000000,
                    'master_bedroom_premium' => 98000000,
                    'wc_standard' => 32000000,
                    'worship_room_standard' => 35000000,
                    'laundry_standard' => 22000000,
                    'office_reception_standard' => 55000000,
                    'workspace_standard' => 75000000,
                    'meeting_room_standard' => 90000000,
                    'director_room_standard' => 125000000,
                    'showroom_display_standard' => 150000000,
                    'loose_furniture_upgrade' => 30000000,
                ],
            ],
            [
                'code' => 'internal-2026-v1',
                'name' => 'Bảng giá nội bộ 2026',
                'version' => '2026.v1',
                'status' => 'published',
                'level_support' => ['level_3'],
                'building_types' => ['townhouse', 'villa', 'office', 'shophouse'],
                'description' => 'Bảng giá nội bộ cho dự toán kỹ thuật và thương mại.',
                'published_at' => now(),
                'is_active' => true,
                'item_prices' => [
                    'site_setup_standard' => 32000000,
                    'landscape_basic' => 890000,
                    'pile_package_standard' => 4200000,
                    'retaining_wall_package' => 125000000,
                    'facade_lighting_upgrade' => 25000000,
                    'smart_home_basic' => 39000000,
                    'solar_roof_basic' => 110000000,
                    'elevator_standard' => 35000000,
                    'pool_standard' => 13800000,
                    'pool_overflow' => 17600000,
                    'pool_jacuzzi' => 19800000,
                    'design_architecture_basic' => 165000,
                    'design_interior_package' => 135000,
                    'design_mep_package' => 65000,
                    'living_room_standard' => 64000000,
                    'kitchen_standard' => 80000000,
                    'bedroom_standard' => 39000000,
                    'master_bedroom_premium' => 92000000,
                    'wc_standard' => 30000000,
                    'worship_room_standard' => 32000000,
                    'laundry_standard' => 20000000,
                    'office_reception_standard' => 52000000,
                    'workspace_standard' => 71000000,
                    'meeting_room_standard' => 86000000,
                    'director_room_standard' => 118000000,
                    'showroom_display_standard' => 142000000,
                    'loose_furniture_upgrade' => 26000000,
                ],
            ],
        ];
    }
}
