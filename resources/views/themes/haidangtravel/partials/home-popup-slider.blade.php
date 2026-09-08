@props([
    'popup' => null,
])

@php
    $popupSlides = collect(data_get($popup, 'slides', []))
        ->filter(fn (array $slide): bool => filled($slide['title'] ?? null)
            || filled($slide['subtitle'] ?? null)
            || filled($slide['description'] ?? null)
            || filled($slide['image_url'] ?? null)
            || filled($slide['full_image_url'] ?? null)
            || filled($slide['mobile_image_url'] ?? null)
            || filled($slide['full_mobile_image_url'] ?? null)
            || filled($slide['thumbnail_url'] ?? null)
            || filled($slide['embed_url'] ?? null)
            || filled($slide['video_url'] ?? null)
            || (filled($slide['primary_label'] ?? null) && filled($slide['primary_url'] ?? null))
            || (filled($slide['secondary_label'] ?? null) && filled($slide['secondary_url'] ?? null)))
        ->values();
    $popupVersion = trim((string) data_get($popup, 'version', 'home-popup'));
    $popupInterval = (int) data_get($popup, 'autoplay_delay', 0);
    $popupHeadingId = 'home-popup-title-'.substr(md5($popupVersion), 0, 10);
    $firstPopupTitle = trim((string) data_get($popupSlides->first(), 'title', 'Popup Trang chủ'));
@endphp

@if ($popupSlides->isNotEmpty())
    <div
        data-home-popup-slider
        data-home-popup-key="{{ $popupVersion }}"
        data-home-popup-delay="2000"
        data-home-popup-dismiss-hours="24"
        data-home-popup-interval="{{ $popupInterval > 0 ? $popupInterval : 0 }}"
        class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm"
        aria-hidden="true"
    >
        <button type="button" class="absolute inset-0 cursor-default" data-home-popup-close aria-label="Đóng popup trang chủ"></button>

        <section
            data-home-popup-dialog
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $popupHeadingId }}"
            tabindex="-1"
            class="relative z-10 flex w-[80vw] max-w-5xl flex-col overflow-hidden rounded-[1.35rem] bg-white shadow-[0_42px_120px_-42px_rgba(15,23,42,0.82)] outline-none ring-1 ring-white/20"
        >
            <button
                type="button"
                data-home-popup-close
                class="absolute right-3 top-3 z-30 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-slate-950/42 text-white shadow-lg backdrop-blur transition hover:bg-slate-950/62 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                aria-label="Đóng popup trang chủ"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>

            <div class="relative bg-slate-950" style="height: min(80vh, 44rem);">
                @foreach ($popupSlides as $slide)
                    @php
                        $slideTitle = trim((string) ($slide['title'] ?? ''));
                        $slideSubtitle = trim((string) ($slide['subtitle'] ?? ''));
                        $slideDescription = trim((string) ($slide['description'] ?? ''));
                        $slidePrimaryLabel = trim((string) ($slide['primary_label'] ?? ''));
                        $slidePrimaryUrl = trim((string) ($slide['primary_url'] ?? ''));
                        $slideSecondaryLabel = trim((string) ($slide['secondary_label'] ?? ''));
                        $slideSecondaryUrl = trim((string) ($slide['secondary_url'] ?? ''));
                        $slideDesktopImage = trim((string) ($slide['full_image_url'] ?? $slide['image_url'] ?? $slide['thumbnail_url'] ?? ''));
                        $slideMobileImage = trim((string) ($slide['full_mobile_image_url'] ?? $slide['mobile_image_url'] ?? $slideDesktopImage));
                        $slideImageAlt = trim((string) ($slide['image_alt'] ?? $slideTitle ?: $firstPopupTitle ?: 'Popup Hải Đăng Travel'));
                        $slideEmbedUrl = trim((string) ($slide['embed_url'] ?? ''));
                        $slideVideoUrl = trim((string) ($slide['video_url'] ?? ''));
                        $slideVideoKind = trim((string) ($slide['video_kind'] ?? ''));
                        $slideHasText = $slideTitle !== ''
                            || $slideSubtitle !== ''
                            || $slideDescription !== ''
                            || ($slidePrimaryLabel !== '' && $slidePrimaryUrl !== '')
                            || ($slideSecondaryLabel !== '' && $slideSecondaryUrl !== '');
                        $slideHasMedia = $slideDesktopImage !== '' || $slideMobileImage !== '' || $slideEmbedUrl !== '' || $slideVideoUrl !== '';
                    @endphp

                    <article
                        data-home-popup-slide
                        class="{{ $loop->first ? '' : 'hidden' }} absolute inset-0"
                        aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                        @if (! $loop->first) inert @endif
                    >
                        @if ($slideEmbedUrl !== '')
                            <iframe
                                src="{{ $slideEmbedUrl }}"
                                title="{{ $slideTitle ?: $firstPopupTitle ?: 'Popup Trang chủ' }}"
                                class="h-full w-full bg-black"
                                loading="lazy"
                                allow="autoplay; encrypted-media; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        @elseif ($slideVideoUrl !== '' && $slideVideoKind === 'mp4')
                            <video src="{{ $slideVideoUrl }}" class="h-full w-full bg-black object-contain" autoplay muted loop playsinline controls></video>
                        @elseif ($slideHasMedia)
                            <picture class="flex h-full w-full items-center justify-center bg-slate-950">
                                @if ($slideMobileImage !== '')
                                    <source media="(max-width: 767px)" srcset="{{ $slideMobileImage }}">
                                @endif
                                <img
                                    src="{{ $slideDesktopImage ?: $slideMobileImage }}"
                                    alt="{{ $slideImageAlt }}"
                                    class="h-full w-full object-contain"
                                    width="1280"
                                    height="720"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </picture>
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-[linear-gradient(135deg,#004A99_0%,#0f3f79_54%,#FF6A00_140%)]"></div>
                        @endif

                        @if ($slideHasText)
                            <div class="absolute inset-x-0 bottom-0 bg-[linear-gradient(180deg,transparent_0%,rgba(3,18,43,0.82)_34%,rgba(3,18,43,0.94)_100%)] px-5 pb-5 pt-16 text-white sm:px-7 sm:pb-7">
                                <div class="max-w-3xl space-y-3">
                                    @if ($slideSubtitle !== '')
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-100">{{ $slideSubtitle }}</p>
                                    @endif

                                    @if ($slideTitle !== '')
                                        <h2 @if ($loop->first) id="{{ $popupHeadingId }}" @endif class="font-heading text-2xl font-extrabold leading-tight sm:text-3xl">
                                            {{ $slideTitle }}
                                        </h2>
                                    @elseif ($loop->first)
                                        <span id="{{ $popupHeadingId }}" class="sr-only">{{ $firstPopupTitle ?: 'Popup Trang chủ' }}</span>
                                    @endif

                                    @if ($slideDescription !== '')
                                        <p class="max-w-2xl text-sm leading-6 text-slate-100 sm:text-base">
                                            {!! nl2br(e($slideDescription)) !!}
                                        </p>
                                    @endif

                                    @if (($slidePrimaryLabel !== '' && $slidePrimaryUrl !== '') || ($slideSecondaryLabel !== '' && $slideSecondaryUrl !== ''))
                                        <div class="flex flex-wrap gap-2.5 pt-1">
                                            @if ($slidePrimaryLabel !== '' && $slidePrimaryUrl !== '')
                                                <a href="{{ $slidePrimaryUrl }}" class="inline-flex min-h-10 items-center gap-2 rounded-sm bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                                                    {{ $slidePrimaryLabel }}
                                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                </a>
                                            @endif

                                            @if ($slideSecondaryLabel !== '' && $slideSecondaryUrl !== '')
                                                <a href="{{ $slideSecondaryUrl }}" class="inline-flex min-h-10 items-center rounded-sm border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/16">
                                                    {{ $slideSecondaryLabel }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif ($loop->first)
                            <span id="{{ $popupHeadingId }}" class="sr-only">{{ $firstPopupTitle ?: 'Popup Trang chủ' }}</span>
                        @endif
                    </article>
                @endforeach

                @if ($popupSlides->count() > 1)
                    <div class="absolute bottom-3 right-3 z-20 flex items-center gap-2 rounded-full border border-white/15 bg-slate-950/46 px-2.5 py-2 text-white backdrop-blur">
                        <button type="button" data-home-popup-prev class="inline-flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/12 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80" aria-label="Xem popup trước">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        </button>
                        <span class="min-w-12 text-center text-xs font-semibold" data-home-popup-counter>1 / {{ $popupSlides->count() }}</span>
                        <button type="button" data-home-popup-next class="inline-flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/12 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80" aria-label="Xem popup tiếp theo">
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endif
