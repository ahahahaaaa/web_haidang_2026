@php
    $offer = is_array($offer ?? null) ? $offer : [];
    $card = $offer['tour_card'] ?? [];
    $departure = $offer['departure'] ?? null;
    $imageUrl = $card['image_url'] ?? null;
    $transportLabel = trim((string) ($departure?->transport_label ?: ($card['transport_label'] ?? 'Liên hệ')));
    $departurePlace = trim((string) ($departure?->departure_location ?: ($card['departure_place'] ?? 'Liên hệ')));
    $departurePlaceShort = \App\Support\FrontsiteCardData::abbreviateDeparturePlace($departurePlace);
    $slots = $departure?->available_slots;
@endphp

<article class="frontsite-tour-card group flex h-full min-w-0 flex-col overflow-hidden rounded-[1.25rem] border-4 border-white/25 bg-white text-slate-950 shadow-[0_22px_48px_-30px_rgba(70,0,16,0.65)]">
    <a href="{{ $offer['detail_url'] }}" class="relative block aspect-[16/10] overflow-hidden bg-slate-200">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $card['image_alt'] ?? $card['title'] }}" class="frontsite-tour-card-image h-full w-full object-cover" width="640" height="400" loading="lazy" decoding="async">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/45 via-transparent to-transparent"></div>
        <div
            class="absolute right-3 top-3 rounded-lg bg-white px-2.5 py-1 font-mono text-[12px] font-extrabold text-orange-700 shadow-sm"
            data-voucher-countdown
            data-voucher-countdown-target="{{ $offer['ends_at_iso'] }}"
            data-voucher-countdown-expired="Đã kết thúc"
            data-voucher-countdown-total-hours="true"
        >
            <span class="sr-only" data-voucher-countdown-days>00</span>
            <span data-voucher-countdown-hours>00</span>:<span data-voucher-countdown-minutes>00</span>:<span data-voucher-countdown-seconds>00</span>
            <span class="sr-only" data-voucher-countdown-status>Thời gian ưu đãi còn lại</span>
        </div>
        <span class="absolute bottom-3 right-3 inline-flex items-center gap-2 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-semibold text-amber-300 backdrop-blur-sm">
            <i class="fa-regular fa-eye"></i> Xem nhanh
        </span>
    </a>

    <div class="flex flex-1 flex-col gap-3 p-3">
        <h3 class="line-clamp-2 min-h-11 text-[15px] font-bold leading-[1.4]">
            <a href="{{ $offer['detail_url'] }}" class="transition hover:text-primary">{{ $card['title'] ?? '' }}</a>
        </h3>

        <div class="space-y-2 text-[12px] text-slate-600">
            <p class="flex items-center gap-2"><i class="{{ \App\Support\TourUiIcons::DEPARTURE_DATE }} w-4 text-slate-500"></i><span>{{ $offer['departure_date_label'] }}</span><span class="ml-auto inline-flex items-center gap-1 font-semibold text-orange-700"><i class="{{ \App\Support\TourUiIcons::SLOTS }}"></i>{{ is_numeric($slots) ? 'Còn '.(int) $slots.' chỗ' : 'Liên hệ chỗ' }}</span></p>
            <p class="flex items-center gap-2" title="Điểm khởi hành: {{ $departurePlace }}"><i class="{{ \App\Support\TourUiIcons::DEPARTURE_LOCATION }} w-4 text-slate-500"></i><span data-tour-card-location-short>{{ $departurePlaceShort }}</span><span class="ml-auto inline-flex items-center gap-1"><i class="{{ \App\Support\TourUiIcons::DURATION }}"></i>{{ $card['duration_compact_label'] ?? $card['duration_label'] ?? '' }}</span></p>
            <p class="flex items-center gap-2"><i class="{{ \App\Support\TourUiIcons::transport($transportLabel) }} w-4 text-slate-500"></i><span>{{ $transportLabel }}</span></p>
        </div>

        <div class="mt-auto -mb-3 -mr-3 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
            <div class="pb-3">
                @if (filled($offer['regular_price_label'] ?? null) && ($offer['regular_price'] ?? null) !== ($offer['flash_price'] ?? null))
                    <p class="text-[11px] text-slate-400 line-through">{{ $offer['regular_price_label'] }}</p>
                @endif
                <p class="text-lg font-extrabold leading-none text-orange-700">{{ $offer['flash_price_label'] }}</p>
                <p class="mt-1 text-xs font-semibold text-orange-700">Còn {{ (int) ($offer['remaining_ticket_quantity'] ?? 0) }} vé</p>
            </div>
            <span class="frontsite-tour-card-cta-wrap inline-flex shrink-0">
                <a href="{{ $offer['detail_url'] }}" class="frontsite-tour-card-cta frontsite-tour-booking-cta inline-flex min-h-11 items-center justify-center border px-5 py-2.5 text-sm font-bold transition focus-visible:outline-none">
                    <span>Đặt ngay</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </span>
        </div>
    </div>
</article>
