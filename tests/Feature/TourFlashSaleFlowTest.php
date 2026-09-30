<?php

namespace Tests\Feature;

use App\Support\LandingPageBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSale;
use Tests\TestCase;

class TourFlashSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_flash_sale_block_links_to_contextual_detail_and_overrides_only_that_price(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        [$tour, $departure, $campaign] = $this->flashSaleFixture();
        $block = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FLASH_SALE);
        $block['campaign_id'] = $campaign->getKey();

        $landing = LandingPage::query()->create([
            'title' => 'Ưu đãi tour hôm nay',
            'slug' => 'uu-dai-tour-hom-nay',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$block],
        ]);

        $detailUrl = route('tours.show', [
            'tour' => $tour,
            'flash_sale' => $campaign->slug,
            'flash_departure' => $departure->getKey(),
        ]);

        $this->get('/uu-dai-tour-hom-nay')
            ->assertOk()
            ->assertSeeText('Ưu đãi giờ chót')
            ->assertSeeText('3.990.000 đ')
            ->assertSee('linear-gradient(145deg,#E65F00_0%,#C2410C_58%,#9A3412_100%)', false)
            ->assertSee('text-lg font-extrabold leading-none text-orange-700', false)
            ->assertSee('frontsite-tour-card-cta', false)
            ->assertSeeText('Đặt ngay')
            ->assertSee($detailUrl)
            ->assertSee('"price":3990000', false)
            ->assertSee('data-tour-card-location-short>HCM</span>', false)
            ->assertDontSee('data-tour-card-slider="true"', false)
            ->assertSee('data-voucher-countdown-target=', false);

        $block['is_slider'] = true;
        $landing->update(['blocks' => [$block]]);

        $this->get('/uu-dai-tour-hom-nay')
            ->assertOk()
            ->assertSee('data-tour-card-slider="true"', false)
            ->assertSee('data-card-carousel-item', false)
            ->assertSee('3.990.000 đ');

        $this->get($detailUrl)
            ->assertOk()
            ->assertSeeText('3.990.000 đ')
            ->assertSeeText('Ưu đãi giờ chót')
            ->assertSee('data-tour-main-price-panel', false)
            ->assertSee('data-tour-main-price', false)
            ->assertSee('fa-solid fa-ticket', false)
            ->assertSee('data-tour-main-price-info-icon="fa-solid fa-star"', false)
            ->assertSeeText('Đặt tour')
            ->assertSeeText('Còn 6 vé')
            ->assertSee('data-travel-inquiry-flash-sale-slug="uu-dai-gio-chot"', false)
            ->assertSee('data-travel-inquiry-departure-id="'.$departure->getKey().'"', false)
            ->assertSee('"price":3990000', false)
            ->assertSee('"priceValidUntil":"2026-09-21"', false)
            ->assertSee('<link rel="canonical" href="'.route('tours.show', $tour).'"', false);

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSeeText('6.990.000 đ')
            ->assertDontSeeText('Ưu đãi giờ chót');
    }

    public function test_flash_sale_widget_view_more_button_can_be_overridden_or_hidden(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        [, , $campaign] = $this->flashSaleFixture();
        $block = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FLASH_SALE);
        $block['campaign_id'] = $campaign->getKey();
        unset($block['show_view_more'], $block['view_more_label'], $block['view_more_url']);

        $landing = LandingPage::query()->create([
            'title' => 'Flash Sale có nút tùy chỉnh',
            'slug' => 'flash-sale-co-nut-tuy-chinh',
            'is_active' => true,
            'template_key' => 'generic',
            'blocks' => [$block],
        ]);

        $this->get('/flash-sale-co-nut-tuy-chinh')
            ->assertOk()
            ->assertSeeText('Xem thêm');

        $block['view_more_label'] = 'Khám phá ưu đãi';
        $block['view_more_url'] = '/tim-tour?flash_sale=1';
        $landing->update(['blocks' => [$block]]);

        $this->get('/flash-sale-co-nut-tuy-chinh')
            ->assertOk()
            ->assertSeeText('Khám phá ưu đãi')
            ->assertSee('href="/tim-tour?flash_sale=1"', false);

        $block['show_view_more'] = false;
        $landing->update(['blocks' => [$block]]);

        $this->get('/flash-sale-co-nut-tuy-chinh')
            ->assertOk()
            ->assertSeeText('Ưu đãi giờ chót')
            ->assertDontSeeText('Khám phá ưu đãi')
            ->assertDontSee('href="/tim-tour?flash_sale=1"', false);

        $block['is_enabled'] = false;
        $landing->update(['blocks' => [$block]]);

        $this->get('/flash-sale-co-nut-tuy-chinh')
            ->assertOk()
            ->assertDontSeeText('Ưu đãi giờ chót');
    }

    public function test_expired_or_mismatched_flash_sale_context_never_changes_detail_price(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        [$tour, $departure, $campaign] = $this->flashSaleFixture();
        $campaign->update(['ends_at' => now()->subMinute()]);

        $this->get(route('tours.show', [
            'tour' => $tour,
            'flash_sale' => $campaign->slug,
            'flash_departure' => $departure->getKey(),
        ]))
            ->assertOk()
            ->assertSeeText('6.990.000 đ')
            ->assertDontSeeText('Ưu đãi giờ chót')
            ->assertDontSee('"price":3990000', false);
    }

    /** @return array{Tour, TourDeparture, TourFlashSale} */
    protected function flashSaleFixture(): array
    {
        $tour = Tour::query()->create([
            'title' => 'Tour Thái Lan giờ chót',
            'slug' => 'tour-thai-lan-gio-chot',
            'excerpt' => 'Khởi hành gần với giá ưu đãi theo campaign.',
            'content' => '<p>Lịch trình tour.</p>',
            'status' => 'published',
            'scope' => 'international',
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport' => 'Máy bay',
            'duration_days' => 5,
            'duration_nights' => 4,
            'standard_label' => 'Khách sạn 4 sao',
            'base_price' => 7990000,
            'sale_price' => 6990000,
            'published_at' => now()->subDay(),
        ]);
        $departure = TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(5)->toDateString(),
            'return_date' => now()->addDays(9)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'base_price' => 7990000,
            'sale_price' => 6990000,
            'available_slots' => 6,
            'status' => 'scheduled',
        ]);
        $campaign = TourFlashSale::query()->create([
            'title' => 'Ưu đãi giờ chót',
            'slug' => 'uu-dai-gio-chot',
            'description' => 'Mức giá tốt nhất trong ngày.',
            'icon_class' => 'fa-solid fa-bolt',
            'cta_label' => 'Xem thêm',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
            'is_active' => true,
        ]);
        $campaign->items()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_price' => 3990000,
            'ticket_quantity' => 6,
            'sort_order' => 0,
        ]);

        return [$tour, $departure, $campaign];
    }
}
