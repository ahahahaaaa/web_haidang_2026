<?php

namespace Tests\Feature;

use App\Support\LandingPageBlocks;
use App\Support\TravelHomePageConfig;
use Database\Seeders\HomeFlashSaleDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSale;
use Tests\TestCase;

class HomeFlashSaleDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_demo_flash_sale_is_seeded_once_immediately_after_home_vouchers(): void
    {
        Carbon::setTestNow('2026-09-22 10:00:00');

        $voucherBlock = [
            ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_RAIL),
            'uuid' => 'home-test-vouchers',
        ];
        $homePage = LandingPage::query()->create([
            'page_key' => 'home',
            'template_key' => 'home',
            'title' => 'Trang chủ',
            'slug' => 'home',
            'is_active' => true,
            'blocks' => [$voucherBlock],
            'home_config' => TravelHomePageConfig::prepare([], [$voucherBlock]),
        ]);

        foreach ([2000000, 3000000] as $index => $price) {
            $tour = Tour::query()->create([
                'title' => 'Tour demo '.($index + 1),
                'slug' => 'tour-demo-'.$index,
                'excerpt' => 'Tour dùng để kiểm tra Flash Sale trên trang chủ.',
                'content' => '<p>Lịch trình tour.</p>',
                'status' => 'published',
                'scope' => 'domestic',
                'base_price' => $price,
                'published_at' => now()->subDay(),
            ]);
            TourDeparture::query()->create([
                'tour_id' => $tour->getKey(),
                'departure_date' => now()->addDays($index + 3)->toDateString(),
                'base_price' => $price,
                'status' => 'scheduled',
            ]);
        }

        $this->seed(HomeFlashSaleDemoSeeder::class);
        $this->seed(HomeFlashSaleDemoSeeder::class);

        $campaign = TourFlashSale::query()->where('slug', 'demo-uu-dai-gio-chot')->firstOrFail();
        $this->assertTrue($campaign->isCurrentlyActive());
        $this->assertSame(2, $campaign->items()->count());
        $campaign->load('items.departure');
        $this->assertSame([12, 8], $campaign->items->pluck('ticket_quantity')->all());
        $this->assertSame([0, 0], $campaign->items->pluck('booked_quantity')->all());
        $this->assertTrue($campaign->items->every(function ($item): bool {
            $regularPrice = $item->departure->sale_price ?: $item->departure->base_price;

            return $item->flash_price > 0 && $item->flash_price < $regularPrice;
        }));

        $homePage->refresh();
        $blocks = LandingPageBlocks::normalize($homePage->blocks);
        $this->assertSame(1, collect($blocks)->where('uuid', 'home-flash-sale-demo')->count());
        $this->assertSame($campaign->getKey(), collect($blocks)->firstWhere('uuid', 'home-flash-sale-demo')['campaign_id']);

        $layoutOrder = TravelHomePageConfig::homeLayoutOrder($homePage->home_config, $blocks);
        $voucherIndex = array_search('block:home-test-vouchers', $layoutOrder, true);
        $this->assertSame('block:home-flash-sale-demo', $layoutOrder[$voucherIndex + 1]);

        $response = $this->get(route('home'));
        $response->assertOk()->assertSeeText('Ưu đãi giờ chót')->assertSee('data-tour-card-slider="true"', false);

        $firstItem = $campaign->items->firstOrFail();
        $campaign->forceFill([
            'title' => 'Campaign demo đã chỉnh trong CMS',
            'is_active' => false,
        ])->save();
        $firstItem->forceFill([
            'flash_price' => 1500000,
            'ticket_quantity' => 30,
            'booked_quantity' => 4,
            'sort_order' => 99,
        ])->save();

        $blocks = LandingPageBlocks::normalize($homePage->fresh()->blocks);
        $blocks = collect($blocks)->map(function (array $block): array {
            if (($block['uuid'] ?? null) === 'home-flash-sale-demo') {
                $block['title'] = 'Block Flash Sale đã chỉnh trong CMS';
                $block['is_enabled'] = false;
            }

            return $block;
        })->all();
        $homePage->forceFill(['blocks' => $blocks])->save();

        $this->seed(HomeFlashSaleDemoSeeder::class);

        $campaign->refresh();
        $firstItem->refresh();
        $homePage->refresh();
        $savedDemoBlock = collect(LandingPageBlocks::normalize($homePage->blocks))
            ->firstWhere('uuid', 'home-flash-sale-demo');

        $this->assertSame('Campaign demo đã chỉnh trong CMS', $campaign->title);
        $this->assertFalse($campaign->is_active);
        $this->assertSame(2, $campaign->items()->count());
        $this->assertSame(1500000, $firstItem->flash_price);
        $this->assertSame(30, $firstItem->ticket_quantity);
        $this->assertSame(4, $firstItem->booked_quantity);
        $this->assertSame(99, $firstItem->sort_order);
        $this->assertSame('Block Flash Sale đã chỉnh trong CMS', $savedDemoBlock['title']);
        $this->assertFalse($savedDemoBlock['is_enabled']);
    }
}
