@props([
    'tour',
    'variant' => 'default',
    'revealDelay' => null,
    'showRating' => false,
])

@php
    $card = \App\Support\FrontsiteCardData::tour($tour);
    $isCompact = $variant === 'compact';
    $isHorizontal = $variant === 'horizontal';
    $imageUrl = $card['image_url'] ?? null;
    $nextDepartureChip = $card['next_departure_label'] !== 'Liên hệ' ? $card['next_departure_label'] : 'Tư vấn lịch';
    $topicChip = $card['primary_topic_label'] ?? null;
    $hasTopicChip = filled($topicChip);
    $horizontalHighlightChip = $topicChip;
    $transportText = \Illuminate\Support\Str::lower($card['transport_label']);
    $transportIcon = \Illuminate\Support\Str::contains($transportText, ['bay', 'plane', 'air'])
        ? 'fa-solid fa-plane-departure'
        : (\Illuminate\Support\Str::contains($transportText, ['xe', 'bus', 'car']) ? 'fa-solid fa-bus-simple' : 'fa-solid fa-route');
    $horizontalGalleryItems = $isHorizontal ? \App\Support\FrontsiteCardData::tourGallerySlides($tour) : [];
    $activeHorizontalGalleryItem = $horizontalGalleryItems[0] ?? null;
    $horizontalGalleryCount = count($horizontalGalleryItems);
    $hasHorizontalGallery = $isHorizontal && $activeHorizontalGalleryItem !== null;
    $hasHorizontalGalleryNavigation = $horizontalGalleryCount > 1;
    $horizontalSliderHeightClasses = 'lg:h-[24rem] lg:min-h-[24rem] lg:max-h-[24rem]';
    $defaultImageClasses = 'frontsite-media-panel relative block aspect-[16/9] min-h-[12.75rem] overflow-hidden bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/35';
    $articleClasses = $isHorizontal
        ? 'theme-panel frontsite-spotlight-card group frontsite-text-reveal overflow-hidden lg:grid lg:grid-cols-2 lg:items-stretch'
        : 'theme-panel frontsite-spotlight-card group frontsite-text-reveal overflow-hidden';
    $horizontalImageClasses = 'frontsite-media-panel relative overflow-hidden bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] hidden lg:block lg:aspect-auto '.$horizontalSliderHeightClasses;
    $imageClasses = $isHorizontal
        ? $defaultImageClasses.' lg:h-full lg:min-h-full lg:aspect-auto'
        : $defaultImageClasses;
    $contentPadding = $isCompact ? 'p-4' : ($isHorizontal ? 'p-4 lg:px-6 lg:py-5' : 'p-4');
    $titleClasses = $isCompact ? 'text-lg' : ($isHorizontal ? 'text-[14px] lg:text-[1.35rem]' : 'text-[14px]');
    $priceClasses = $isCompact ? 'text-[1.25rem]' : ($isHorizontal ? 'text-[1.15rem] lg:text-[1.4rem]' : 'text-[1.15rem]');
    $metaGridClasses = $isCompact
        ? 'grid gap-2.5 text-[15px] text-slate-700'
        : ($isHorizontal ? 'grid gap-2 text-[14px] text-slate-700 lg:grid-cols-3 lg:gap-3' : 'grid gap-2 text-[14px] text-slate-700');
    $metaRowClasses = 'flex items-center gap-2.5';
    $metaIconClasses = $isCompact
        ? 'inline-flex size-9 shrink-0 items-center justify-center rounded-[0.9rem] bg-slate-50 text-secondary'
        : 'inline-flex size-8 shrink-0 items-center justify-center rounded-[0.8rem] bg-slate-50 text-secondary';
    $metaLabelClasses = $isCompact
        ? 'text-[12px] font-semibold uppercase text-slate-500'
        : 'text-[11px] font-semibold uppercase text-slate-500';
    $metaValueClasses = $isCompact ? 'font-medium leading-5 text-slate-950' : 'font-medium leading-5 text-slate-950';
    $ctaLabel = trim((string) ($card['cta_label'] ?? ''));
    $ctaLabel = $ctaLabel === 'Nhận tư vấn tour' ? 'Tư vấn' : $ctaLabel;
    $basePriceLabel = null;

    if (($card['base_price_value'] ?? null) !== null && $card['price_label'] !== 'Liên hệ') {
        $resolvedBasePriceLabel = number_format((int) $card['base_price_value'], 0, ',', '.') . ' đ';
        $basePriceLabel = $resolvedBasePriceLabel !== $card['price_label'] ? $resolvedBasePriceLabel : null;
    }
@endphp

<article
    class="{{ $articleClasses }}"
    data-reveal="card"
    @if ($revealDelay !== null) data-reveal-delay="{{ $revealDelay }}" @endif
>
    @if ($hasHorizontalGallery)
        <a
            href="{{ $card['detail_url'] }}"
            class="{{ $defaultImageClasses }} lg:hidden"
        >
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $card['image_alt'] }}"
                    class="frontsite-media-asset h-full w-full object-cover"
                    width="960"
                    height="600"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="theme-grid-pattern flex h-full w-full items-end bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] p-4">
                    <div class="space-y-1 text-white">
                        <p class="text-[10px] font-semibold uppercase text-white/72">{{ $card['scope_label'] }}</p>
                        <p class="max-w-[16rem] text-lg font-semibold leading-tight">{{ $card['destination_label'] }}</p>
                    </div>
                </div>
            @endif

            <div class="frontsite-media-content absolute inset-x-3 top-3 flex items-start justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-white/14 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-solid fa-compass text-orange-200"></i>
                    {{ $card['scope_label'] }}
                </span>
                @if ($hasTopicChip)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-slate-950/24 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                        <i class="fa-solid fa-tag text-orange-200"></i>
                        {{ $horizontalHighlightChip }}
                    </span>
                @endif
            </div>

            <div class="frontsite-media-content absolute inset-x-3 bottom-3 flex items-end justify-between gap-2 text-white">
                <div class="min-w-0 space-y-1">
                    <p class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-white/12 bg-white/10 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white/88 backdrop-blur-sm">
                        <i class="fa-solid fa-location-dot text-orange-200"></i>
                        <span class="truncate">{{ $card['destination_label'] }}</span>
                    </p>
                    <p class="line-clamp-1 text-[12px] font-semibold leading-4">{{ $card['departure_place'] }}</p>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-slate-950/24 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-solid fa-users text-[9px] text-orange-200"></i>
                    {{ $card['slot_label'] }}
                </span>
            </div>
        </a>

        <div
            class="{{ $horizontalImageClasses }}"
            data-tour-gallery
            data-tour-gallery-autoplay="{{ $hasHorizontalGalleryNavigation ? 'true' : 'false' }}"
            data-tour-gallery-autoplay-delay="4600"
        >
            <div class="theme-grid-pattern absolute inset-0 opacity-15"></div>

            <div class="absolute inset-x-3 top-3 z-10 flex items-start justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-white/14 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-solid fa-compass text-orange-200"></i>
                    {{ $card['scope_label'] }}
                </span>

                <div class="flex flex-col items-end gap-2">
                    @if ($hasTopicChip)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-slate-950/24 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                            <i class="fa-solid fa-tag text-orange-200"></i>
                            {{ $horizontalHighlightChip }}
                        </span>
                    @endif

                    <span data-tour-gallery-counter class="inline-flex min-w-[4.6rem] items-center justify-center rounded-full border border-white/12 bg-slate-950/35 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-white backdrop-blur-sm">
                        1 / {{ $horizontalGalleryCount }}
                    </span>
                </div>
            </div>

            @if ($hasHorizontalGalleryNavigation)
                <div class="pointer-events-none absolute inset-y-0 left-3 right-3 z-10 hidden items-center justify-between lg:flex">
                    <button
                        type="button"
                        class="pointer-events-auto inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/14 bg-slate-950/28 text-white backdrop-blur-sm transition hover:bg-slate-950/42 disabled:cursor-not-allowed disabled:opacity-45"
                        data-tour-gallery-prev
                        aria-label="Ảnh trước"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <button
                        type="button"
                        class="pointer-events-auto inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/14 bg-slate-950/28 text-white backdrop-blur-sm transition hover:bg-slate-950/42 disabled:cursor-not-allowed disabled:opacity-45"
                        data-tour-gallery-next
                        aria-label="Ảnh tiếp theo"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            @endif

            <div class="absolute inset-0 overflow-hidden" data-tour-gallery-stage>
                <img
                    src="{{ $activeHorizontalGalleryItem['src'] }}"
                    alt="{{ $activeHorizontalGalleryItem['alt'] }}"
                    class="frontsite-media-asset absolute inset-0 h-full w-full object-cover"
                    width="960"
                    height="600"
                    loading="lazy"
                    decoding="async"
                    data-tour-gallery-image
                >
                <video
                    class="hidden absolute inset-0 h-full w-full bg-black object-contain"
                    data-tour-gallery-video
                    playsinline
                    muted
                    preload="metadata"
                ></video>
                <iframe
                    class="hidden absolute inset-0 h-full w-full bg-black"
                    data-tour-gallery-iframe
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen
                ></iframe>
            </div>

            <div class="pointer-events-none absolute inset-x-3 bottom-3 z-10">
                <div class="pointer-events-auto">
                    <div class="frontsite-soft-scrollbar frontsite-soft-scrollbar--inverse overflow-x-auto pb-1" data-tour-gallery-thumb-rail>
                        <div class="flex w-max gap-2">
                            @foreach ($horizontalGalleryItems as $item)
                                <button
                                    type="button"
                                    class="group shrink-0 overflow-hidden rounded-[1rem] border text-left transition {{ $loop->first ? 'border-primary ring-2 ring-primary/15 shadow-[0_24px_50px_-36px_rgba(255,106,0,0.55)]' : 'border-slate-200 hover:border-orange-200' }}"
                                    data-tour-gallery-thumb
                                    data-tour-gallery-kind="{{ $item['kind'] }}"
                                    data-tour-gallery-src="{{ $item['src'] }}"
                                    data-tour-gallery-embed=""
                                    data-tour-gallery-poster=""
                                    data-tour-gallery-title="{{ $item['title'] }}"
                                    data-tour-gallery-description="{{ $item['description'] }}"
                                    data-tour-gallery-alt="{{ $item['alt'] }}"
                                    data-tour-gallery-label="{{ $item['label'] }}"
                                    data-tour-gallery-icon="{{ $item['icon'] }}"
                                    aria-label="Chọn ảnh {{ $loop->iteration }} của tour"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                >
                                    <span class="block h-12 w-16 overflow-hidden rounded-[0.9rem]">
                                        <img
                                            src="{{ $item['thumbnail_url'] }}"
                                            alt="{{ $item['alt'] }}"
                                            class="block h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            decoding="async"
                                        >
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <a
            href="{{ $card['detail_url'] }}"
            class="{{ $imageClasses }}"
        >
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $card['image_alt'] }}"
                    class="frontsite-media-asset h-full w-full object-cover"
                    width="960"
                    height="600"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="theme-grid-pattern flex h-full w-full items-end bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] p-4">
                    <div class="space-y-1 text-white">
                        <p class="text-[10px] font-semibold uppercase text-white/72">{{ $card['scope_label'] }}</p>
                        <p class="max-w-[16rem] text-lg font-semibold leading-tight">{{ $card['destination_label'] }}</p>
                    </div>
                </div>
            @endif

            <div class="frontsite-media-content absolute inset-x-3 top-3 flex items-start justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-white/14 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-solid fa-compass text-orange-200"></i>
                    {{ $card['scope_label'] }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-slate-950/24 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-regular fa-calendar-days text-orange-200"></i>
                    {{ $nextDepartureChip }}
                </span>
            </div>

            <div class="frontsite-media-content absolute inset-x-3 bottom-3 flex items-end justify-between gap-2 text-white">
                <div class="min-w-0 space-y-1">
                    <p class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-white/12 bg-white/10 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white/88 backdrop-blur-sm">
                        <i class="fa-solid fa-location-dot text-orange-200"></i>
                        <span class="truncate">{{ $card['destination_label'] }}</span>
                    </p>
                    <p class="line-clamp-1 text-[12px] font-semibold leading-4">{{ $card['departure_place'] }}</p>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-slate-950/24 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-solid fa-users text-[9px] text-orange-200"></i>
                    {{ $card['slot_label'] }}
                </span>
            </div>
        </a>
    @endif

    <div class="flex flex-col gap-3 {{ $contentPadding }} {{ $isHorizontal ? 'lg:h-full lg:gap-4' : '' }}">
        <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-semibold uppercase text-slate-500">
            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5">{{ $card['standard_label'] }}</span>
            @if ($showRating && filled($card['rating_label']) && filled($card['rating_count']))
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[10px] font-semibold normal-case text-amber-700">
                    <i class="fa-solid fa-star text-[9px] text-amber-500"></i>
                    {{ $card['rating_label'] }}/5
                    <span class="text-amber-700/80">• {{ number_format((int) $card['rating_count'], 0, ',', '.') }} đánh giá</span>
                </span>
            @endif
            @if (! $isHorizontal && $hasTopicChip)
                <span class="rounded-full bg-[color:var(--color-primary-soft)] px-2.5 py-0.5 text-primary">{{ $topicChip }}</span>
            @endif
        </div>

        <h3 class="font-heading {{ $titleClasses }} font-bold leading-[1.3] text-slate-950">
            <a href="{{ $card['detail_url'] }}" class="line-clamp-2 transition hover:text-primary focus-visible:outline-none focus-visible:text-primary">
                {{ $card['title'] }}
            </a>
        </h3>

        @if ($isHorizontal)
            <p class="hidden text-sm leading-6 text-slate-600 lg:block">
                {{ $card['summary'] }}
            </p>
        @endif

        <div class="{{ $metaGridClasses }}">
            <div class="{{ $metaRowClasses }}">
                <span class="{{ $metaIconClasses }}">
                    <i class="fa-regular fa-calendar-days"></i>
                </span>
                <div class="min-w-0 flex flex-wrap items-center gap-1.5">
                    <span class="{{ $metaLabelClasses }}">Khởi Hành:</span>
                    <span class="{{ $metaValueClasses }}">{{ $card['next_departure_label'] }}</span>
                </div>
            </div>

            <div class="{{ $metaRowClasses }}">
                <span class="{{ $metaIconClasses }}">
                    <i class="fa-regular fa-clock"></i>
                </span>
                <div class="min-w-0 flex flex-wrap items-center gap-1.5">
                    <span class="{{ $metaValueClasses }}">{{ $card['duration_label'] }}</span>
                </div>
            </div>

            <div class="{{ $metaRowClasses }}">
                <span class="{{ $metaIconClasses }}">
                    <i class="{{ $transportIcon }}"></i>
                </span>
                <div class="min-w-0 flex flex-wrap items-center gap-1.5">
                    <span class="{{ $metaValueClasses }}">{{ $card['transport_label'] }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-end justify-between gap-2.5 border-t border-slate-200 pt-2.5 {{ $isHorizontal ? 'lg:mt-auto' : '' }}">
            <div class="space-y-1">
                @if ($isHorizontal)
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Giá từ</p>
                    <div class="flex flex-nowrap items-baseline gap-2">
                        <p class="font-heading {{ $priceClasses }} whitespace-nowrap font-bold leading-none text-[color:var(--color-price)]">{{ $card['price_label'] }}</p>
                        @if ($basePriceLabel)
                            <p class="whitespace-nowrap text-[12px] text-slate-400 line-through">{{ $basePriceLabel }}</p>
                        @endif
                    </div>
                @else
                    <div class="inline-flex flex-wrap items-center gap-y-1">
                        <p class="text-[10px] font-semibold uppercase leading-none text-slate-500">Giá từ</p>
                        @if ($basePriceLabel)
                            <p class="pl-2 whitespace-nowrap text-[12px] leading-none text-slate-400 line-through">{{ $basePriceLabel }}</p>
                        @endif
                    </div>
                    <p class="font-heading {{ $priceClasses }} whitespace-nowrap font-bold leading-none text-[color:var(--color-price)]">{{ $card['price_label'] }}</p>
                @endif
            </div>

            <div class="ml-auto flex shrink-0 items-center justify-end">
                <a href="{{ $card['detail_url'] }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-[0.9rem] border border-orange-200 px-4 py-2.5 text-[13px] font-semibold text-primary transition hover:bg-orange-50">
                    {{ $ctaLabel }}
                    <i class="fa-solid fa-arrow-right 123"></i>
                </a>
            </div>
        </div>
    </div>
</article>
