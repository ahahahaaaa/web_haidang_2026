<?php

namespace Database\Seeders;

use App\Support\EstimatePageContent;
use Illuminate\Database\Seeder;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;

class CmsEstimateBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CmsBootstrapSeeder::class,
            EstimateCatalogSeeder::class,
        ]);

        $header = Menu::query()->firstOrCreate(['location' => 'header'], ['name' => 'Header Menu']);
        $footer = Menu::query()->firstOrCreate(['location' => 'footer'], ['name' => 'Footer Menu']);

        foreach ([
            [$header, 'Dự toán', '/du-toan', 5],
            [$footer, 'Dự toán', '/du-toan', 4],
        ] as [$menu, $label, $url, $order]) {
            MenuItem::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'label' => $label],
                ['url' => $url, 'order' => $order, 'is_active' => true, 'target' => '_self'],
            );
        }

        LandingPage::query()->updateOrCreate(
            ['page_key' => 'estimate'],
            [
                'title' => 'Landing Dự toán',
                'slug' => 'du-toan',
                'hero_badge' => 'ESTIMATE',
                'hero_title' => 'Landing Dự toán',
                'hero_excerpt' => 'Nội dung giới thiệu linh hoạt cho Landing Dự toán',
                'robots_directive' => 'index,follow',
                'estimate_config' => EstimatePageContent::defaultConfig(),
                'faq_items' => EstimatePageContent::defaultFaqItems(),
            ],
        );
    }
}
