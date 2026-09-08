@props([
    'landing' => null,
    'hero' => [],
    'breadcrumbItems' => [],
    'metaItems' => [],
    'fallbackEyebrow' => null,
    'fallbackTitle' => null,
    'fallbackDescription' => null,
    'fallbackPrimaryLabel' => null,
    'fallbackPrimaryUrl' => null,
    'fallbackSecondaryLabel' => null,
    'fallbackSecondaryUrl' => null,
])

@php
    $normalizeActionUrl = static function (?string $url): string {
        $value = trim((string) $url);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '#')) {
            return $value;
        }

        $parts = parse_url($value);

        if (($parts['scheme'] ?? null) || ($parts['host'] ?? null)) {
            return '/'.ltrim((string) ($parts['path'] ?? ''), '/');
        }

        return '/'.ltrim($value, '/');
    };
    $isInquiryTriggerUrl = static fn (?string $url): bool => in_array($normalizeActionUrl($url), ['/lien-he', '#travel-inquiry-form'], true);
    $heroMode = (string) ($hero['mode'] ?? 'default');

    $metaItems = collect($metaItems ?? [])
        ->filter(fn ($item) => is_array($item) && trim((string) data_get($item, 'label')) !== '')
        ->values();
    $heroSlides = collect($hero['slides'] ?? [])->filter(fn (array $slide) => filled($slide['title'] ?? null)
        || filled($slide['description'] ?? null)
        || filled($slide['image_url'] ?? null)
        || filled($slide['mobile_image_url'] ?? null)
        || ((bool) ($slide['show_inner_media'] ?? true) && filled($slide['inner_image_url'] ?? null))
        || filled($slide['thumbnail_url'] ?? null)
        || filled($slide['video_url'] ?? null)
        || filled($slide['embed_url'] ?? null)
        || (filled($slide['primary_label'] ?? null) && filled($slide['primary_url'] ?? null))
        || (filled($slide['secondary_label'] ?? null) && filled($slide['secondary_url'] ?? null)))->values();
    $staticTitle = trim((string) ($fallbackTitle ?: $landing?->hero_title ?: $landing?->title));
    $staticDescription = trim((string) ($fallbackDescription ?: $landing?->hero_excerpt ?: $landing?->intro_excerpt));
    $staticPrimaryLabel = trim((string) ($fallbackPrimaryLabel ?: $landing?->cta_primary_label));
    $staticPrimaryUrl = trim((string) ($fallbackPrimaryUrl ?: $landing?->cta_primary_url));
    $staticSecondaryLabel = trim((string) ($fallbackSecondaryLabel ?: $landing?->cta_secondary_label));
    $staticSecondaryUrl = trim((string) ($fallbackSecondaryUrl ?: $landing?->cta_secondary_url));
    $staticMediaUrl = trim((string) ($hero['media_url'] ?? ''));
    $staticMediaMediumUrl = trim((string) ($hero['media_medium_url'] ?? $hero['media_small_url'] ?? $staticMediaUrl));
    $staticMediaAlt = trim((string) ($hero['media_alt'] ?? $staticTitle ?: 'Landing hero'));
@endphp

@if ($heroMode === 'slider' && $heroSlides->isNotEmpty())
    <section class="relative overflow-hidden bg-secondary">
        <div data-hero-slider class="relative min-h-[34rem] lg:min-h-[38rem]" data-interval="{{ ((int) ($hero['autoplay_delay'] ?? 0)) > 0 ? (int) $hero['autoplay_delay'] : 5500 }}">
            @foreach ($heroSlides as $slide)
                @php
                    $slideTitle = trim((string) ($slide['title'] ?? ''));
                    $slideDescription = trim((string) ($slide['description'] ?? ''));
                    $slidePrimaryLabel = trim((string) ($slide['primary_label'] ?? ''));
                    $slidePrimaryUrl = trim((string) ($slide['primary_url'] ?? ''));
                    $slideSecondaryLabel = trim((string) ($slide['secondary_label'] ?? ''));
                    $slideSecondaryUrl = trim((string) ($slide['secondary_url'] ?? ''));
                    $slideDesktopImageUrl = trim((string) ($slide['image_url'] ?? ''));
                    $slideDesktopFullImageUrl = trim((string) ($slide['full_image_url'] ?? $slideDesktopImageUrl));
                    $slideMobileImageUrl = trim((string) ($slide['mobile_image_url'] ?? $slideDesktopImageUrl));
                    $slideMobileFullImageUrl = trim((string) ($slide['full_mobile_image_url'] ?? $slideMobileImageUrl));
                    $slideInnerImageUrl = trim((string) ($slide['inner_image_url'] ?? ''));
                    $slideInnerFullImageUrl = trim((string) ($slide['full_inner_image_url'] ?? $slideInnerImageUrl));
                    $slideImageAlt = trim((string) ($slide['image_alt'] ?? $slideTitle ?: $staticTitle ?: 'Slide image'));
                    $slideThumbnailUrl = trim((string) ($slide['thumbnail_url'] ?? ''));
                    $slideEmbedUrl = trim((string) ($slide['embed_url'] ?? ''));
                    $slideVideoUrl = trim((string) ($slide['video_url'] ?? ''));
                    $slideEffect = trim((string) ($slide['effect'] ?? 'animate__fadeInUp'));
                    $slideShowOverlay = (bool) ($slide['show_overlay'] ?? true);
                    $slideShowInnerMedia = (bool) ($slide['show_inner_media'] ?? true);
                    $slideBackgroundDesktopUrl = $slideDesktopFullImageUrl !== ''
                        ? $slideDesktopFullImageUrl
                        : ($slideDesktopImageUrl !== '' ? $slideDesktopImageUrl : $slideThumbnailUrl);
                    $slideBackgroundMobileUrl = $slideMobileImageUrl !== ''
                        ? $slideMobileImageUrl
                        : ($slideDesktopImageUrl !== '' ? $slideDesktopImageUrl : $slideThumbnailUrl);
                    $slideBackgroundFallbackUrl = $slideBackgroundDesktopUrl !== ''
                        ? $slideBackgroundDesktopUrl
                        : ($slideBackgroundMobileUrl !== '' ? $slideBackgroundMobileUrl : $slideThumbnailUrl);
                    $slidePanelImageUrl = $slideInnerImageUrl !== ''
                        ? $slideInnerImageUrl
                        : ($slideInnerFullImageUrl !== '' ? $slideInnerFullImageUrl : ($slideDesktopImageUrl !== '' ? $slideDesktopImageUrl : ($slideThumbnailUrl !== '' ? $slideThumbnailUrl : $slideBackgroundFallbackUrl)));
                    $slideHasPanelMedia = $slideShowInnerMedia && (
                        $slidePanelImageUrl !== ''
                        || $slideEmbedUrl !== ''
                        || ($slideVideoUrl !== '' && ($slide['video_kind'] ?? null) === 'mp4')
                    );
                    $slideGridClasses = $slideHasPanelMedia
                        ? 'lg:grid-cols-[minmax(0,1.05fr)_24rem] lg:items-center'
                        : 'lg:grid-cols-1';
                    $slideCopyWidth = $slideHasPanelMedia ? 'max-w-3xl' : 'max-w-4xl';
                @endphp

                <article
                    data-hero-slide-item
                    class="service-hero-slide {{ $loop->first ? 'is-active' : '' }}"
                    aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                    @if (! $loop->first) inert @endif
                >
                    @if ($slideBackgroundFallbackUrl !== '')
                        <picture class="absolute inset-0 block h-full w-full">
                            @if ($slideBackgroundMobileUrl !== '')
                                <source
                                    media="(max-width: 767px)"
                                    @if ($loop->first)
                                        srcset="{{ $slideBackgroundMobileUrl }}"
                                    @else
                                        data-hero-source-srcset="{{ $slideBackgroundMobileUrl }}"
                                    @endif
                                >
                            @endif
                            @if ($slideBackgroundDesktopUrl !== '')
                                <source
                                    media="(min-width: 768px)"
                                    @if ($loop->first)
                                        srcset="{{ $slideBackgroundDesktopUrl }}"
                                    @else
                                        data-hero-source-srcset="{{ $slideBackgroundDesktopUrl }}"
                                    @endif
                                >
                            @endif
                            <img
                                @if ($loop->first)
                                    src="{{ $slideBackgroundFallbackUrl }}"
                                @else
                                    data-hero-image-src="{{ $slideBackgroundFallbackUrl }}"
                                @endif
                                data-hero-image
                                alt="{{ $slideImageAlt }}"
                                class="h-full w-full object-cover"
                                width="1600"
                                height="900"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                fetchpriority="{{ $loop->first ? 'high' : 'low' }}"
                                decoding="async"
                            >
                        </picture>
                    @elseif ($slideThumbnailUrl !== '')
                        <img
                            @if ($loop->first)
                                src="{{ $slideThumbnailUrl }}"
                            @else
                                data-hero-image-src="{{ $slideThumbnailUrl }}"
                            @endif
                            data-hero-image
                            alt="{{ $slideImageAlt }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            width="1600"
                            height="900"
                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $loop->first ? 'high' : 'low' }}"
                            decoding="async"
                        >
                    @elseif ($slideVideoUrl !== '' && ($slide['video_kind'] ?? null) === 'mp4')
                        <video class="absolute inset-0 h-full w-full object-cover" autoplay muted loop playsinline>
                            <source src="{{ $slideVideoUrl }}" type="video/mp4">
                        </video>
                    @else
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,106,0,0.22),_transparent_24%),linear-gradient(135deg,_#004A99_0%,_#0c3569_50%,_#002d5f_100%)]"></div>
                    @endif

                    @if ($slideShowOverlay)
                        <div data-hero-overlay class="absolute inset-0 bg-[linear-gradient(110deg,_rgba(3,18,43,0.92)_0%,_rgba(3,18,43,0.82)_42%,_rgba(255,106,0,0.2)_100%)]"></div>
                    @endif

                    <div class="relative mx-auto grid min-h-[34rem] max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:min-h-[38rem] {{ $slideGridClasses }} lg:px-8 lg:py-10">
                        <div class="{{ $slideCopyWidth }} space-y-6">
                            @if ($breadcrumbItems !== [])
                                @include('themes.haidangtravel.partials.breadcrumbs', ['items' => $breadcrumbItems])
                            @endif

                            @if ($slideTitle !== '')
                                <h1 data-hero-text="title" data-hero-effect="{{ $slideEffect }}" class="font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                                    {{ $slideTitle }}
                                </h1>
                            @endif

                            @if ($slideDescription !== '')
                                <p data-hero-text="body" data-hero-effect="{{ $slideEffect }}" class="max-w-3xl text-lg leading-8 text-slate-200">
                                    {!! nl2br(e($slideDescription)) !!}
                                </p>
                            @endif

                            @if ($metaItems->isNotEmpty())
                                <div data-hero-text="meta" data-hero-effect="{{ $slideEffect }}" class="flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-100/85">
                                    @foreach ($metaItems as $metaItem)
                                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2">
                                            @if (trim((string) data_get($metaItem, 'icon')) !== '')
                                                <i class="{{ data_get($metaItem, 'icon') }} text-orange-200"></i>
                                            @endif
                                            {{ data_get($metaItem, 'label') }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if (($slidePrimaryLabel !== '' && $slidePrimaryUrl !== '') || ($slideSecondaryLabel !== '' && $slideSecondaryUrl !== ''))
                                <div data-hero-text="cta" data-hero-effect="{{ $slideEffect }}" class="flex flex-wrap gap-3">
                                    @if ($slidePrimaryLabel !== '' && $slidePrimaryUrl !== '')
                                        @if ($isInquiryTriggerUrl($slidePrimaryUrl))
                                            <button
                                                type="button"
                                                class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover"
                                                data-travel-inquiry-open
                                                data-travel-inquiry-source="general"
                                                data-travel-inquiry-context="{{ $slideTitle ?: $staticTitle ?: 'Liên hệ chung' }}"
                                                data-travel-inquiry-subject="{{ $slideTitle ?: $slidePrimaryLabel }}"
                                                data-travel-inquiry-modal-title="Thông tin đặt tour"
                                                data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                                            >
                                                {{ $slidePrimaryLabel }}
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </button>
                                        @else
                                            <a href="{{ $slidePrimaryUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover">
                                                {{ $slidePrimaryLabel }}
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        @endif
                                    @endif

                                    @if ($slideSecondaryLabel !== '' && $slideSecondaryUrl !== '')
                                        <a href="{{ $slideSecondaryUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-sm border border-white/18 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/14">
                                            {{ $slideSecondaryLabel }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($slideHasPanelMedia)
                            <div class="hidden lg:block">
                                <div data-hero-text="panel" data-hero-effect="{{ $slideEffect }}" data-hero-panel class="overflow-hidden rounded-[1.8rem] border border-white/12 bg-white/10 p-4 shadow-[0_24px_70px_-40px_rgba(15,23,42,0.58)] backdrop-blur">
                                    @if ($slideEmbedUrl !== '')
                                        <div class="aspect-[4/5] overflow-hidden rounded-[1.35rem] bg-slate-950">
                                            <iframe src="{{ $slideEmbedUrl }}" title="{{ $slideTitle ?: 'Landing hero video' }}" class="h-full w-full" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                                        </div>
                                    @elseif ($slideVideoUrl !== '' && ($slide['video_kind'] ?? null) === 'mp4')
                                        <video src="{{ $slideVideoUrl }}" class="aspect-[4/5] h-full w-full rounded-[1.35rem] object-cover" controls playsinline></video>
                                    @elseif ($slidePanelImageUrl !== '')
                                        <img
                                            @if ($loop->first)
                                                src="{{ $slidePanelImageUrl }}"
                                            @else
                                                data-hero-image-src="{{ $slidePanelImageUrl }}"
                                            @endif
                                            data-hero-image
                                            alt="{{ $slideImageAlt }}"
                                            class="aspect-[4/5] h-full w-full rounded-[1.35rem] object-cover"
                                            width="640"
                                            height="800"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach

            @if ($heroSlides->count() > 1)
                <div class="frontsite-slider-nav">
                    <button type="button" data-hero-prev class="frontsite-hero-nav-button inline-flex h-11 w-11 items-center justify-center rounded-sm border border-white/15 bg-white/10 text-white transition hover:bg-white/16" aria-label="Xem slide trước">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <button type="button" data-hero-next class="frontsite-hero-nav-button inline-flex h-11 w-11 items-center justify-center rounded-sm border border-white/15 bg-white/10 text-white transition hover:bg-white/16" aria-label="Xem slide tiếp theo">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <div class="pointer-events-none absolute inset-x-0 bottom-6 z-10 flex justify-center">
                    <div class="pointer-events-auto flex items-center gap-2 rounded-full border border-white/10 bg-slate-950/25 px-3 py-2 backdrop-blur">
                        @foreach ($heroSlides as $slide)
                            <button type="button" data-hero-dot class="service-hero-dot {{ $loop->first ? 'is-active' : '' }}" aria-label="Đi tới slide {{ $loop->iteration }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@else
    <section class="relative overflow-hidden bg-secondary">
        @if ($staticMediaUrl !== '' || $staticMediaMediumUrl !== '')
            <picture class="absolute inset-0 block h-full w-full">
                @if ($staticMediaMediumUrl !== '')
                    <source media="(max-width: 767px)" srcset="{{ $staticMediaMediumUrl }}">
                @endif
                <img
                    src="{{ $staticMediaUrl !== '' ? $staticMediaUrl : $staticMediaMediumUrl }}"
                    alt="{{ $staticMediaAlt }}"
                    class="absolute inset-0 h-full w-full object-cover"
                    width="1600"
                    height="900"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                >
            </picture>
            <div class="absolute inset-0 bg-[linear-gradient(110deg,_rgba(3,18,43,0.94)_0%,_rgba(3,18,43,0.84)_46%,_rgba(255,106,0,0.22)_100%)]"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,106,0,0.22),_transparent_24%),linear-gradient(135deg,_#004A99_0%,_#0c3569_50%,_#002d5f_100%)]"></div>
        @endif

        <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1.02fr)_22rem] lg:items-center lg:px-8 lg:py-10">
            <div class="max-w-3xl space-y-6">
                @if ($breadcrumbItems !== [])
                    @include('themes.haidangtravel.partials.breadcrumbs', ['items' => $breadcrumbItems])
                @endif

                @if ($staticTitle !== '')
                    <h1 class="font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ $staticTitle }}
                    </h1>
                @endif

                @if ($staticDescription !== '')
                    <p class="max-w-3xl text-lg leading-8 text-slate-200">
                        {!! nl2br(e($staticDescription)) !!}
                    </p>
                @endif

                @if ($metaItems->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-100/85">
                        @foreach ($metaItems as $metaItem)
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2">
                                @if (trim((string) data_get($metaItem, 'icon')) !== '')
                                    <i class="{{ data_get($metaItem, 'icon') }} text-orange-200"></i>
                                @endif
                                {{ data_get($metaItem, 'label') }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if (($staticPrimaryLabel !== '' && $staticPrimaryUrl !== '') || ($staticSecondaryLabel !== '' && $staticSecondaryUrl !== ''))
                    <div class="flex flex-wrap gap-3">
                        @if ($staticPrimaryLabel !== '' && $staticPrimaryUrl !== '')
                            @if ($isInquiryTriggerUrl($staticPrimaryUrl))
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover"
                                    data-travel-inquiry-open
                                    data-travel-inquiry-source="general"
                                    data-travel-inquiry-context="{{ $staticTitle ?: 'Liên hệ chung' }}"
                                    data-travel-inquiry-subject="{{ $staticTitle ?: $staticPrimaryLabel }}"
                                    data-travel-inquiry-modal-title="Thông tin đặt tour"
                                    data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                                >
                                    {{ $staticPrimaryLabel }}
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            @else
                                <a href="{{ $staticPrimaryUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover">
                                    {{ $staticPrimaryLabel }}
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            @endif
                        @endif

                        @if ($staticSecondaryLabel !== '' && $staticSecondaryUrl !== '')
                            <a href="{{ $staticSecondaryUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-sm border border-white/18 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/14">
                                {{ $staticSecondaryLabel }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if ($staticMediaUrl !== '' || $staticMediaMediumUrl !== '')
                <div class="hidden lg:block">
                    <div class="overflow-hidden rounded-[1.8rem] border border-white/12 bg-white/10 p-4 shadow-[0_24px_70px_-40px_rgba(15,23,42,0.58)] backdrop-blur">
                        <img src="{{ $staticMediaMediumUrl !== '' ? $staticMediaMediumUrl : $staticMediaUrl }}" alt="{{ $staticMediaAlt }}" class="aspect-[4/5] h-full w-full rounded-[1.35rem] object-cover" width="640" height="800" loading="lazy" decoding="async">
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
