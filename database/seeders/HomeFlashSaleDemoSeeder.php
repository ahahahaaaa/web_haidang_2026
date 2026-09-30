<?php

namespace Database\Seeders;

use App\Support\LandingPageBlocks;
use App\Support\TravelHomePageConfig;
use Illuminate\Database\Seeder;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSale;

class HomeFlashSaleDemoSeeder extends Seeder
{
    private const CAMPAIGN_SLUG = 'demo-uu-dai-gio-chot';

    private const BLOCK_UUID = 'home-flash-sale-demo';

    /** @var list<int> */
    private const DEMO_TICKET_QUANTITIES = [12, 8, 15, 10, 6, 20];

    public function run(): void
    {
        $homePage = LandingPage::query()->where('page_key', 'home')->first();

        if (! $homePage) {
            return;
        }

        $blocks = LandingPageBlocks::normalize($homePage->blocks ?? []);
        $voucherBlock = collect($blocks)->first(
            fn (array $block): bool => ($block['type'] ?? null) === LandingPageBlocks::TYPE_VOUCHER_RAIL
                && (bool) ($block['is_enabled'] ?? true),
        );

        if (! $voucherBlock) {
            return;
        }

        $departures = TourDeparture::query()
            ->upcomingPublic()
            ->whereDate('departure_date', '>', now()->toDateString())
            ->whereHas('tour', fn ($query) => $query->published())
            ->where(fn ($query) => $query->where('base_price', '>', 10000)->orWhere('sale_price', '>', 10000))
            ->where(fn ($query) => $query->whereNull('available_slots')->orWhere('available_slots', '>', 0))
            ->orderBy('departure_date')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->unique('tour_id')
            ->take(6);

        if ($departures->isEmpty()) {
            return;
        }

        $campaign = TourFlashSale::query()->firstOrCreate(
            ['slug' => self::CAMPAIGN_SLUG],
            [
                'title' => 'Ưu đãi giờ chót',
                'description' => 'Khám phá tour sắp khởi hành với giá ưu đãi trong thời gian giới hạn.',
                'icon_class' => 'fa-solid fa-bolt',
                'cta_label' => 'Xem thêm',
                'cta_url' => '/tim-tour',
                'starts_at' => now()->subMinute(),
                'ends_at' => now()->addDays(2),
                'is_active' => true,
            ],
        );

        if ($campaign->wasRecentlyCreated || ! $campaign->items()->exists()) {
            foreach ($departures as $index => $departure) {
                $regularPrice = (int) ($departure->sale_price ?: $departure->base_price);
                $flashPrice = max(1, $regularPrice - max(10000, (int) floor($regularPrice * 0.1 / 10000) * 10000));
                $ticketQuantity = $this->demoTicketQuantity($index, $departure->available_slots);

                $campaign->items()->firstOrCreate(
                    ['tour_departure_id' => $departure->getKey()],
                    [
                        'tour_id' => $departure->tour_id,
                        'flash_price' => $flashPrice,
                        'ticket_quantity' => $ticketQuantity,
                        'booked_quantity' => 0,
                        'sort_order' => $index,
                    ],
                );
            }
        }

        $campaign->items()
            ->whereNull('ticket_quantity')
            ->with('departure:id,available_slots')
            ->get()
            ->each(function ($item, int $index): void {
                $ticketQuantity = $this->demoTicketQuantity($index, $item->departure?->available_slots);

                $item->forceFill([
                    'ticket_quantity' => max($ticketQuantity, (int) $item->booked_quantity),
                ])->save();
            });

        $demoBlock = collect($blocks)->first(
            fn (array $block): bool => ($block['uuid'] ?? null) === self::BLOCK_UUID,
        );

        if ($demoBlock) {
            return;
        }

        $demoBlock = [
            ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FLASH_SALE),
            'uuid' => self::BLOCK_UUID,
            'is_slider' => true,
            'home_position' => LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS,
        ];
        $demoBlock['campaign_id'] = $campaign->getKey();

        $blocks = collect($blocks)
            ->push($demoBlock)
            ->values()
            ->all();

        $homeConfig = is_array($homePage->home_config) ? $homePage->home_config : [];
        $voucherToken = TravelHomePageConfig::homeLayoutTokenForBlock((string) $voucherBlock['uuid']);
        $demoToken = TravelHomePageConfig::homeLayoutTokenForBlock(self::BLOCK_UUID);
        $layoutOrder = collect(TravelHomePageConfig::homeLayoutOrder($homeConfig, $blocks))
            ->reject(fn (string $token): bool => $token === $demoToken)
            ->values()
            ->all();
        $voucherIndex = array_search($voucherToken, $layoutOrder, true);

        if ($voucherIndex === false) {
            return;
        }

        array_splice($layoutOrder, $voucherIndex + 1, 0, [$demoToken]);

        $homePage->forceFill([
            'blocks' => $blocks,
            'home_config' => TravelHomePageConfig::prepare([
                ...$homeConfig,
                'layout_order' => $layoutOrder,
            ], $blocks),
        ])->save();
    }

    private function demoTicketQuantity(int $index, mixed $availableSlots): int
    {
        $demoQuantity = self::DEMO_TICKET_QUANTITIES[$index % count(self::DEMO_TICKET_QUANTITIES)];

        if (is_numeric($availableSlots) && (int) $availableSlots > 0) {
            return min($demoQuantity, (int) $availableSlots);
        }

        return $demoQuantity;
    }
}
