<?php

namespace App\Services\Frontsite;

use App\Support\FrontsiteCardData;
use Illuminate\Support\Collection;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourFlashSale;
use Src\Domains\Cms\Models\TourFlashSaleItem;

class TourFlashSaleService
{
    /**
     * @return array<string, mixed>|null
     */
    public function activeCampaignForBlock(mixed $campaignId): ?array
    {
        if (! is_numeric($campaignId) || (int) $campaignId <= 0) {
            return null;
        }

        $campaign = TourFlashSale::query()
            ->active()
            ->with(['items' => fn ($query) => $query
                ->whereHas('tour', fn ($tourQuery) => $tourQuery->published())
                ->whereHas('departure', fn ($departureQuery) => $departureQuery->upcomingPublic())
                ->with([
                    'departure',
                    'tour.departures' => fn ($departureQuery) => $departureQuery->upcomingPublic()->orderBy('departure_date')->orderBy('sort_order'),
                    'tour.destination.media',
                    'tour.media',
                    'tour.primaryCategory.media',
                    'tour.region.media',
                ])])
            ->find((int) $campaignId);

        if (! $campaign) {
            return null;
        }

        $offers = $campaign->items
            ->filter(fn (TourFlashSaleItem $item): bool => $this->validItem($item))
            ->map(fn (TourFlashSaleItem $item): array => $this->presentItem($campaign, $item))
            ->values();

        if ($offers->isEmpty()) {
            return null;
        }

        return $this->presentCampaign($campaign, $offers);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeOfferForTour(Tour $tour, ?string $campaignSlug, mixed $departureId = null): ?array
    {
        $slug = trim((string) $campaignSlug);

        if ($slug === '') {
            return null;
        }

        if (! is_numeric($departureId) || (int) $departureId <= 0) {
            return null;
        }

        $campaign = TourFlashSale::query()
            ->active()
            ->where('slug', $slug)
            ->with(['items' => fn ($query) => $query
                ->where('tour_id', $tour->getKey())
                ->where('tour_departure_id', (int) $departureId)
                ->whereHas('departure', fn ($departureQuery) => $departureQuery->upcomingPublic())
                ->with(['departure', 'tour'])])
            ->first();

        /** @var TourFlashSaleItem|null $item */
        $item = $campaign?->items->first(fn (TourFlashSaleItem $candidate): bool => $this->validItem($candidate));

        if (! $campaign || ! $item) {
            return null;
        }

        $item->setRelation('tour', $tour);

        return $this->presentItem($campaign, $item);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $offers
     * @return array<string, mixed>
     */
    protected function presentCampaign(TourFlashSale $campaign, Collection $offers): array
    {
        return [
            'id' => (int) $campaign->getKey(),
            'title' => trim((string) $campaign->title),
            'slug' => trim((string) $campaign->slug),
            'description' => trim((string) $campaign->description),
            'icon_class' => trim((string) $campaign->icon_class) ?: 'fa-solid fa-bolt',
            'cta_label' => trim((string) $campaign->cta_label) ?: 'Xem thêm',
            'cta_url' => trim((string) $campaign->cta_url) ?: route('tours.search'),
            'starts_at' => $campaign->starts_at,
            'starts_at_iso' => $campaign->starts_at?->toIso8601String(),
            'ends_at' => $campaign->ends_at,
            'ends_at_iso' => $campaign->ends_at?->toIso8601String(),
            'offers' => $offers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentItem(TourFlashSale $campaign, TourFlashSaleItem $item): array
    {
        $tour = $item->tour;
        $departure = $item->departure;
        $regularPrice = $departure?->sale_price ?: $departure?->base_price;
        $card = FrontsiteCardData::tour($tour);

        return [
            'campaign_id' => (int) $campaign->getKey(),
            'campaign_slug' => (string) $campaign->slug,
            'campaign_title' => (string) $campaign->title,
            'ends_at' => $campaign->ends_at,
            'ends_at_iso' => $campaign->ends_at?->toIso8601String(),
            'flash_price' => (int) $item->flash_price,
            'flash_price_label' => number_format((int) $item->flash_price, 0, ',', '.').' đ',
            'ticket_quantity' => $item->effectiveTicketQuantity(),
            'booked_quantity' => (int) $item->booked_quantity,
            'remaining_ticket_quantity' => $item->remainingTicketQuantity(),
            'item_id' => (int) $item->getKey(),
            'regular_price' => is_numeric($regularPrice) ? (int) $regularPrice : null,
            'regular_price_label' => is_numeric($regularPrice) ? number_format((int) $regularPrice, 0, ',', '.').' đ' : null,
            'departure' => $departure,
            'departure_id' => (int) $departure->getKey(),
            'departure_date_label' => $departure->departure_date?->format('d/m/Y') ?: 'Liên hệ',
            'detail_url' => route('tours.show', [
                'tour' => $tour,
                'flash_sale' => $campaign->slug,
                'flash_departure' => $departure->getKey(),
            ]),
            'tour' => $tour,
            'tour_card' => $card,
        ];
    }

    protected function validItem(TourFlashSaleItem $item): bool
    {
        $regularPrice = $item->departure?->sale_price ?: $item->departure?->base_price;

        return $item->tour instanceof Tour
            && $item->departure
            && (int) $item->departure->tour_id === (int) $item->tour_id
            && (int) $item->flash_price > 0
            && $item->remainingTicketQuantity() > 0
            && (! is_numeric($regularPrice) || (int) $regularPrice <= 0 || (int) $item->flash_price < (int) $regularPrice);
    }
}
