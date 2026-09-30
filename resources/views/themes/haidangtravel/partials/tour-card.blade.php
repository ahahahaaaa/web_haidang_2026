@props([
    'tour',
    'variant' => 'default',
    'revealDelay' => null,
    'showRating' => true,
    'ctaVariant' => null,
])

@php
    $card = \App\Support\FrontsiteCardData::tour($tour);
    $isCompact = $variant === 'compact';
    $isHorizontal = $variant === 'horizontal';
    $imageUrl = $card['image_url'] ?? null;
    $imageFullUrl = $card['image_full_url'] ?? null;
    $imageSrcset = filled($imageFullUrl) && filled($imageUrl) && $imageFullUrl !== $imageUrl
        ? $imageUrl.' 1x, '.$imageFullUrl.' 2x'
        : null;
    $topicChip = $card['primary_topic_label'] ?? null;
    $hasTopicChip = filled($topicChip);
    $transportIcon = \App\Support\TourUiIcons::transport($card['transport_label']);
    $departureDateLabels = collect($card['departure_date_labels'] ?? []);
    $horizontalGalleryItems = $isHorizontal ? \App\Support\FrontsiteCardData::tourGallerySlides($tour) : [];
    $activeHorizontalGalleryItem = $horizontalGalleryItems[0] ?? null;
    $horizontalGalleryCount = count($horizontalGalleryItems);
    $hasHorizontalGallery = $isHorizontal && $activeHorizontalGalleryItem !== null;
    $hasHorizontalGalleryNavigation = $horizontalGalleryCount > 1;
    $horizontalSliderHeightClasses = 'lg:h-[24rem] lg:min-h-[24rem] lg:max-h-[24rem]';
    $defaultImageClasses = 'frontsite-media-panel relative block aspect-[4/3] overflow-hidden bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/35';
    $articleClasses = $isHorizontal
        ? 'theme-panel frontsite-spotlight-card frontsite-tour-card group frontsite-text-reveal flex h-full flex-col overflow-hidden lg:grid lg:grid-cols-2 lg:items-stretch'
        : 'theme-panel frontsite-spotlight-card frontsite-tour-card group frontsite-text-reveal flex h-full flex-col overflow-hidden';
    $horizontalImageClasses = 'frontsite-media-panel relative overflow-hidden bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] hidden lg:block lg:aspect-auto '.$horizontalSliderHeightClasses;
    $imageClasses = $isHorizontal
        ? $defaultImageClasses.' lg:h-full lg:min-h-full lg:aspect-auto'
        : $defaultImageClasses;
    $contentWrapperClasses = $isHorizontal
        ? 'relative z-10 mx-1.5 -mt-[22.5%] mb-1.5 flex flex-1 flex-col lg:m-0 lg:h-full'
        : 'relative z-10 mx-1.5 -mt-[22.5%] mb-1.5 flex flex-1 flex-col';
    $contentClasses = $isHorizontal
        ? 'flex flex-1 flex-col gap-3 rounded-[1.15rem] border border-slate-200/90 bg-white p-4 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.4)] lg:gap-4 lg:border-0 lg:bg-transparent lg:px-6 lg:py-5 lg:shadow-none'
        : 'flex flex-1 flex-col gap-3 rounded-[1.15rem] border border-slate-200/90 bg-white p-4 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.4)]';
    $titleClasses = $isCompact ? 'text-lg' : ($isHorizontal ? 'text-[14px] lg:text-[1.35rem]' : 'text-[14px]');
    $priceClasses = $isCompact
        ? 'text-[clamp(1rem,4.8vw,1.25rem)]'
        : ($isHorizontal
            ? 'text-[clamp(1rem,4.8vw,1.15rem)] lg:text-[1.4rem]'
            : 'text-[clamp(1rem,4.8vw,1.15rem)]');
    $ctaLabel = trim((string) ($card['cta_label'] ?? ''));
    $ctaLabel = $ctaLabel === 'Xem' ? 'Đặt ngay' : ($ctaLabel === 'Nhận tư vấn tour' ? 'Tư vấn' : $ctaLabel);
    $resolvedCtaVariant = \App\Support\TourCardStyle::normalizeCtaVariant($ctaVariant);
    $ctaVariantClasses = \App\Support\TourCardStyle::ctaClasses($resolvedCtaVariant);
    $standardStarCount = is_numeric($card['standard_star_count'] ?? null)
        ? max(1, min(5, (int) $card['standard_star_count']))
        : 0;
    $showRatingBadge = $showRating && filled($card['rating_label']) && filled($card['rating_count']);
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
                    class="frontsite-media-asset frontsite-tour-card-image h-full w-full object-cover"
                    @if ($imageSrcset) srcset="{{ $imageSrcset }}" @endif
                    width="1200"
                    height="900"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="theme-grid-pattern flex h-full w-full items-end bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] p-4">
                    <p class="max-w-[16rem] text-lg font-semibold leading-tight text-white">{{ $card['destination_label'] }}</p>
                </div>
            @endif

            @if ($hasTopicChip)
                <div class="absolute inset-x-3 top-3 z-20 flex items-start justify-between gap-2">
                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-white/30 bg-slate-950/80 px-3 py-1.5 text-[10px] font-bold uppercase text-white shadow-md backdrop-blur-sm" data-tour-card-category-overlay>
                        <i class="fa-solid fa-tag text-orange-200" aria-hidden="true"></i>
                        <span class="truncate">{{ $topicChip }}</span>
                    </span>
                </div>
            @endif

        </a>

        <div
            class="{{ $horizontalImageClasses }}"
            data-tour-gallery
            data-tour-gallery-autoplay="{{ $hasHorizontalGalleryNavigation ? 'true' : 'false' }}"
            data-tour-gallery-autoplay-delay="4600"
        >
            <div class="theme-grid-pattern absolute inset-0 opacity-15"></div>

            <div class="absolute inset-x-3 top-3 z-20 flex items-start justify-between gap-2">
                <div class="flex min-w-0 flex-col items-start gap-2">
                    @if ($hasTopicChip)
                        <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-white/30 bg-slate-950/80 px-3 py-1.5 text-[10px] font-bold uppercase text-white shadow-md backdrop-blur-sm" data-tour-card-category-overlay>
                            <i class="fa-solid fa-tag text-orange-200" aria-hidden="true"></i>
                            <span class="truncate">{{ $topicChip }}</span>
                        </span>
                    @endif
                </div>

                <div class="flex flex-col items-end gap-2">
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
                    class="frontsite-media-asset frontsite-tour-card-image absolute inset-0 h-full w-full object-cover"
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
                    class="frontsite-media-asset frontsite-tour-card-image h-full w-full object-cover"
                    @if ($imageSrcset) srcset="{{ $imageSrcset }}" @endif
                    width="1200"
                    height="900"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="theme-grid-pattern flex h-full w-full items-end bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] p-4">
                    <p class="max-w-[16rem] text-lg font-semibold leading-tight text-white">{{ $card['destination_label'] }}</p>
                </div>
            @endif

            @if ($hasTopicChip)
                <div class="absolute inset-x-3 top-3 z-20 flex items-start justify-between gap-2">
                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-white/30 bg-slate-950/80 px-3 py-1.5 text-[10px] font-bold uppercase text-white shadow-md backdrop-blur-sm" data-tour-card-category-overlay>
                        <i class="fa-solid fa-tag text-orange-200" aria-hidden="true"></i>
                        <span class="truncate">{{ $topicChip }}</span>
                    </span>
                </div>
            @endif

        </a>
    @endif

    <div class="{{ $contentWrapperClasses }}">
        @if ($showRatingBadge)
            <span
                class="absolute bottom-full right-2 mb-2 inline-flex max-w-[calc(100%_-_1rem)] items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50/95 px-2.5 py-1 text-[10px] font-semibold normal-case text-amber-700 shadow-sm backdrop-blur-sm {{ $isHorizontal ? 'lg:static lg:mb-0 lg:self-start' : '' }}"
                data-tour-card-rating-overlay
                aria-label="Đánh giá {{ $card['rating_label'] }} trên 5 từ {{ number_format((int) $card['rating_count'], 0, ',', '.') }} lượt đánh giá"
            >
                <i class="fa-solid fa-star text-[9px] text-amber-500" aria-hidden="true"></i>
                {{ $card['rating_label'] }}/5
                <span class="truncate text-amber-700/80">• {{ number_format((int) $card['rating_count'], 0, ',', '.') }} đánh giá</span>
            </span>
        @endif
        <div class="{{ $contentClasses }}" data-tour-card-info-panel>
        <h3 class="font-heading {{ $titleClasses }} font-bold leading-[1.3] text-slate-950">
            <a href="{{ $card['detail_url'] }}" class="line-clamp-2 transition hover:text-primary focus-visible:outline-none focus-visible:text-primary">
                {{ $card['title'] }}
            </a>
        </h3>

        @if ($standardStarCount > 0)
            <div
                class="inline-flex w-fit items-center gap-1 text-[15px] text-amber-400"
                role="img"
                aria-label="Tiêu chuẩn {{ $standardStarCount }} sao"
                data-tour-card-standard-stars="{{ $standardStarCount }}"
            >
                @for ($starIndex = 0; $starIndex < $standardStarCount; $starIndex++)
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                @endfor
            </div>
        @endif

        @if ($isHorizontal)
            <p class="hidden text-sm leading-6 text-slate-600 lg:block">
                {{ \Illuminate\Support\Str::limit(strip_tags((string) ($tour->description ?: $tour->excerpt)), 300) }}
            </p>
        @endif

        <div class="space-y-2 text-[13px] leading-5 text-slate-600">
            <ul class="grid grid-cols-3 gap-1.5" data-tour-card-meta-list aria-label="Thông tin nhanh của tour">
                <li class="flex min-w-0 items-center gap-1.5 rounded-lg bg-slate-50 px-2 py-1.5" title="Điểm khởi hành: {{ $card['departure_place'] }}">
                    <i class="{{ \App\Support\TourUiIcons::DEPARTURE_LOCATION }} shrink-0 text-slate-500" aria-hidden="true"></i>
                    <span class="truncate text-[11px] font-medium text-slate-700" data-tour-card-location-short>{{ $card['departure_place_short'] }}</span>
                </li>
                <li class="flex min-w-0 items-center gap-1.5 rounded-lg bg-slate-50 px-2 py-1.5" title="Phương tiện: {{ $card['transport_label'] }}">
                    <i class="{{ $transportIcon }} shrink-0 text-slate-500" aria-hidden="true"></i>
                    <span class="truncate text-[11px] font-medium text-slate-700">{{ $card['transport_label'] }}</span>
                </li>
                <li class="flex min-w-0 items-center gap-1.5 rounded-lg bg-slate-50 px-2 py-1.5" title="Thời gian: {{ $card['duration_compact_label'] }}">
                    <i class="{{ \App\Support\TourUiIcons::DURATION }} shrink-0 text-slate-500" aria-hidden="true"></i>
                    <strong class="truncate text-[11px] font-semibold text-slate-800">{{ $card['duration_compact_label'] }}</strong>
                </li>
            </ul>
            @if ($departureDateLabels->isNotEmpty())
                <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-1.5" data-tour-card-departure-rail aria-label="Các ngày khởi hành gần nhất">
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-full border border-orange-200 bg-white text-primary transition hover:border-primary hover:bg-orange-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-35"
                        data-tour-card-departure-prev
                        aria-label="Xem ngày khởi hành phía trước"
                    >
                        <i class="fa-solid fa-play rotate-180 text-[0.6rem]" aria-hidden="true"></i>
                    </button>
                    <div class="min-w-0 overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-tour-card-departure-track>
                        <div class="flex min-w-max items-center gap-1.5">
                            @foreach ($departureDateLabels as $departureDateLabel)
                                <span class="rounded-md border border-rose-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-600" data-tour-card-departure-date>{{ $departureDateLabel }}</span>
                            @endforeach
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-full border border-orange-200 bg-white text-primary transition hover:border-primary hover:bg-orange-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-35"
                        data-tour-card-departure-next
                        aria-label="Xem ngày khởi hành tiếp theo"
                    >
                        <i class="fa-solid fa-play text-[0.6rem]" aria-hidden="true"></i>
                    </button>
                </div>
            @endif
            <p class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-rose-600">
                <i class="{{ \App\Support\TourUiIcons::SLOTS }}" aria-hidden="true"></i>
                {{ $card['slot_label'] }}
            </p>
        </div>

        <div class="mt-auto -mb-[1.375rem] -mr-[1.375rem] grid h-[58px] grid-cols-[minmax(0,1fr)_auto] items-start justify-between gap-2 border-t border-slate-200 pt-2.5 sm:h-auto sm:items-end sm:justify-normal {{ $isHorizontal ? 'lg:-mb-5 lg:-mr-6' : '' }}" data-tour-card-price-action>
            <div class="min-w-0 space-y-1 pb-3">
                @if ($isHorizontal)
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Giá từ</p>
                    <div class="flex min-w-0 flex-nowrap items-baseline gap-1.5 overflow-hidden">
                        <p class="min-w-0 whitespace-nowrap font-heading {{ $priceClasses }} font-bold leading-none tracking-[-0.015em] text-[color:var(--color-price)]">{{ $card['price_label'] }}</p>
                        @if ($basePriceLabel)
                            <p class="min-w-0 truncate whitespace-nowrap text-[10px] text-slate-400 line-through sm:text-[12px]">{{ $basePriceLabel }}</p>
                        @endif
                    </div>
                @else
                    <div class="flex min-w-0 max-w-full flex-nowrap items-center gap-1.5 overflow-hidden">
                        <p class="shrink-0 text-[10px] font-semibold uppercase leading-none text-slate-500">Giá từ</p>
                        @if ($basePriceLabel)
                            <p class="min-w-0 truncate whitespace-nowrap text-[10px] leading-none text-slate-400 line-through sm:text-[12px]">{{ $basePriceLabel }}</p>
                        @endif
                    </div>
                    <p class="max-w-full whitespace-nowrap font-heading {{ $priceClasses }} font-bold leading-none tracking-[-0.015em] text-[color:var(--color-price)]">{{ $card['price_label'] }}</p>
                @endif
            </div>

            <div class="frontsite-tour-card-cta-wrap relative -top-3.5 flex shrink-0 items-end justify-end self-end sm:relative sm:-left-1.5 sm:-top-1.5">
                <a
                    href="{{ $card['detail_url'] }}"
                    class="frontsite-tour-card-cta inline-flex min-h-11 items-center justify-center gap-1.5 border py-2.5 pl-5 pr-4 text-[13px] font-bold shadow-sm transition focus-visible:outline-none sm:pr-5 {{ $ctaVariantClasses }}"
                    data-tour-card-cta-variant="{{ $resolvedCtaVariant }}"
                >
                    <span>{{ $ctaLabel }}</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        </div>
    </div>
</article>
