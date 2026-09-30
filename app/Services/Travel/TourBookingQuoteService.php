<?php

namespace App\Services\Travel;

use Src\Domains\Cms\Enums\TravelInquirySource;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSaleItem;

class TourBookingQuoteService
{
    /**
     * Resolve a server-side price snapshot and atomically reserve Flash Sale tickets.
     * This method must be called inside the same database transaction that creates the inquiry.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>|null
     */
    public function resolveAndReserve(array $validated): ?array
    {
        if (($validated['source'] ?? null) !== TravelInquirySource::Tour->value || empty($validated['tour_id'])) {
            return null;
        }

        $tour = Tour::query()->published()->find((int) $validated['tour_id']);

        if (! $tour) {
            return null;
        }

        $adultGuestCount = max(0, (int) ($validated['adult_guest_count'] ?? 0));
        $childGuestCount = max(0, (int) ($validated['party_size'] ?? 0));
        $ticketCount = $adultGuestCount + $childGuestCount;
        $departure = $this->resolveDeparture($tour, $validated['tour_departure_id'] ?? null);
        $regularPrice = $this->regularPrice($tour, $departure);
        $flashSaleSlug = trim((string) ($validated['flash_sale_slug'] ?? ''));

        $quote = [
            'price_type' => $regularPrice !== null ? 'regular' : 'contact',
            'quoted_unit_price' => $regularPrice,
            'regular_unit_price' => $regularPrice,
            'currency' => 'VND',
            'ticket_count' => $ticketCount,
            'adult_guest_count' => $adultGuestCount,
            'child_guest_count' => $childGuestCount,
            'tour_id' => (int) $tour->getKey(),
            'tour_departure_id' => $departure?->getKey(),
            'departure_date' => $departure?->departure_date?->toDateString(),
            'quoted_at' => now()->toIso8601String(),
            'flash_sale_requested' => $flashSaleSlug !== '',
            'flash_sale_status' => $flashSaleSlug !== '' ? 'unavailable' : 'not_requested',
        ];

        if ($flashSaleSlug === '' || ! $departure || $ticketCount < 1) {
            return $quote;
        }

        $item = TourFlashSaleItem::query()
            ->where('tour_id', $tour->getKey())
            ->where('tour_departure_id', $departure->getKey())
            ->whereHas('flashSale', fn ($query) => $query->active()->where('slug', $flashSaleSlug))
            ->whereHas('tour', fn ($query) => $query->published())
            ->whereHas('departure', fn ($query) => $query->upcomingPublic())
            ->with(['departure', 'flashSale'])
            ->lockForUpdate()
            ->first();

        if (! $item || ! $item->flashSale) {
            return $quote;
        }

        $regularPrice = $this->regularPrice($tour, $item->departure);
        $flashPrice = (int) $item->flash_price;
        $capacity = $item->effectiveTicketQuantity();
        $remainingBefore = $item->remainingTicketQuantity();

        $quote = [
            ...$quote,
            'quoted_unit_price' => $regularPrice,
            'regular_unit_price' => $regularPrice,
            'tour_flash_sale_item_id' => (int) $item->getKey(),
            'tour_departure_id' => (int) $item->tour_departure_id,
            'departure_date' => $item->departure?->departure_date?->toDateString(),
            'flash_sale_campaign_id' => (int) $item->flashSale->getKey(),
            'flash_sale_slug' => (string) $item->flashSale->slug,
            'flash_sale_title' => (string) $item->flashSale->title,
            'flash_unit_price' => $flashPrice,
            'flash_ticket_capacity' => $capacity,
            'flash_tickets_remaining_before' => $remainingBefore,
            'flash_tickets_remaining_after' => $remainingBefore,
        ];

        if ($flashPrice < 1 || ($regularPrice !== null && $flashPrice >= $regularPrice)) {
            $quote['flash_sale_status'] = 'invalid_price';

            return $quote;
        }

        if ($remainingBefore < $ticketCount) {
            $quote['flash_sale_status'] = $remainingBefore > 0 ? 'insufficient_tickets' : 'sold_out';

            return $quote;
        }

        $updated = TourFlashSaleItem::query()
            ->whereKey($item->getKey())
            ->where('booked_quantity', '<=', $capacity - $ticketCount)
            ->increment('booked_quantity', $ticketCount);

        if ($updated !== 1) {
            $item->refresh();
            $remaining = $item->remainingTicketQuantity();
            $quote['flash_sale_status'] = $remaining > 0 ? 'insufficient_tickets' : 'sold_out';
            $quote['flash_tickets_remaining_before'] = $remaining;
            $quote['flash_tickets_remaining_after'] = $remaining;

            return $quote;
        }

        $quote['price_type'] = 'flash_sale';
        $quote['quoted_unit_price'] = $flashPrice;
        $quote['flash_sale_status'] = 'reserved';
        $quote['flash_tickets_remaining_after'] = $remainingBefore - $ticketCount;

        return $quote;
    }

    /** @param array<string, mixed>|null $quote */
    public function confirmationMessage(?array $quote): string
    {
        if (! $quote) {
            return 'Yêu cầu của bạn đã được ghi nhận. Hải Đăng Travel sẽ liên hệ sớm nhất.';
        }

        $ticketCount = (int) ($quote['ticket_count'] ?? 0);
        $adultGuestCount = (int) ($quote['adult_guest_count'] ?? 0);
        $childGuestCount = (int) ($quote['child_guest_count'] ?? 0);
        $guestSummary = $ticketCount.' vé ('.$adultGuestCount.' người lớn + '.$childGuestCount.' trẻ em)';
        $quotedPrice = $this->formatPrice($quote['quoted_unit_price'] ?? null);
        $hasQuotedPrice = is_numeric($quote['quoted_unit_price'] ?? null) && (int) $quote['quoted_unit_price'] > 0;

        if (($quote['price_type'] ?? null) === 'flash_sale') {
            $remaining = (int) ($quote['flash_tickets_remaining_after'] ?? 0);

            return 'Đã ghi nhận giá Flash Sale '.$quotedPrice.'/khách cho '.$guestSummary.'. Còn '.$remaining.' vé Flash Sale. Hải Đăng Travel sẽ liên hệ xác nhận.';
        }

        if ((bool) ($quote['flash_sale_requested'] ?? false)) {
            $status = $quote['flash_sale_status'] ?? 'unavailable';

            if (in_array($status, ['insufficient_tickets', 'sold_out'], true)) {
                $remaining = (int) ($quote['flash_tickets_remaining_before'] ?? 0);

                if ($status === 'sold_out') {
                    return $hasQuotedPrice
                        ? 'Flash Sale đã hết vé. Yêu cầu đã được ghi nhận theo giá thường '.$quotedPrice.'/khách; Hải Đăng Travel sẽ liên hệ xác nhận.'
                        : 'Flash Sale đã hết vé. Yêu cầu đã được ghi nhận và Hải Đăng Travel sẽ liên hệ xác nhận giá thường.';
                }

                return $hasQuotedPrice
                    ? 'Flash Sale chỉ còn '.$remaining.' vé, không đủ cho '.$ticketCount.' khách. Yêu cầu đã được ghi nhận theo giá thường '.$quotedPrice.'/khách; Hải Đăng Travel sẽ liên hệ xác nhận.'
                    : 'Flash Sale chỉ còn '.$remaining.' vé, không đủ cho '.$ticketCount.' khách. Yêu cầu đã được ghi nhận và Hải Đăng Travel sẽ liên hệ xác nhận giá thường.';
            }

            return $hasQuotedPrice
                ? 'Giá Flash Sale không còn hiệu lực tại thời điểm gửi. Yêu cầu đã được ghi nhận theo giá thường '.$quotedPrice.'/khách; Hải Đăng Travel sẽ liên hệ xác nhận.'
                : 'Giá Flash Sale không còn hiệu lực tại thời điểm gửi. Yêu cầu đã được ghi nhận và Hải Đăng Travel sẽ liên hệ xác nhận giá thường.';
        }

        if (($quote['price_type'] ?? null) === 'regular') {
            return 'Đã ghi nhận giá hiện tại '.$quotedPrice.'/khách cho '.$guestSummary.'. Hải Đăng Travel sẽ liên hệ xác nhận.';
        }

        return 'Yêu cầu của bạn đã được ghi nhận. Hải Đăng Travel sẽ liên hệ để xác nhận giá và tình trạng chỗ.';
    }

    protected function resolveDeparture(Tour $tour, mixed $departureId): ?TourDeparture
    {
        if (! is_numeric($departureId) || (int) $departureId < 1) {
            return null;
        }

        return TourDeparture::query()
            ->upcomingPublic()
            ->where('tour_id', $tour->getKey())
            ->find((int) $departureId);
    }

    protected function regularPrice(Tour $tour, ?TourDeparture $departure): ?int
    {
        $price = $departure
            ? ($departure->sale_price ?: $departure->base_price)
            : ($tour->sale_price ?: $tour->base_price);

        return is_numeric($price) && (int) $price > 0 ? (int) $price : null;
    }

    protected function formatPrice(mixed $price): string
    {
        if (! is_numeric($price) || (int) $price < 1) {
            return 'liên hệ';
        }

        return number_format((int) $price, 0, ',', '.').' đ';
    }
}
