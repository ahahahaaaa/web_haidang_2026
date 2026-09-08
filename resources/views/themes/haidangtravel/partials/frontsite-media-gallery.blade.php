@props([
    'gallery' => [],
    'sectionId' => 'frontsite-gallery',
    'collectionKey' => null,
    'title' => '',
    'desktopVerticalThumbs' => false,
    'primaryMediaDescription' => '',
])

@php
    $galleryItems = collect($gallery ?? [])
        ->filter(fn ($item) => is_array($item))
        ->values();
    $activeGalleryItem = $galleryItems->first();
    $hasGalleryNavigation = $galleryItems->count() > 1;
    $resolvedSectionId = trim((string) $sectionId) !== '' ? trim((string) $sectionId) : 'frontsite-gallery';
    $resolvedCollectionKey = trim((string) ($collectionKey ?: $resolvedSectionId));
    $resolvedTitle = trim((string) $title);
    $useDesktopVerticalThumbs = (bool) $desktopVerticalThumbs;
    $galleryShellClasses = $useDesktopVerticalThumbs
        ? 'space-y-4 lg:grid lg:items-start lg:gap-5 lg:space-y-0'
        : 'space-y-4';
    $galleryShellStyle = $useDesktopVerticalThumbs
        ? 'grid-template-columns:minmax(0,8fr) minmax(0,2fr);'
        : null;
    $stageClasses = $useDesktopVerticalThumbs
        ? 'lg:col-start-1 lg:row-start-1'
        : '';
    $thumbColumnClasses = $useDesktopVerticalThumbs
        ? 'lg:col-start-2 lg:row-start-1 lg:self-stretch'
        : '';
    $thumbRailClasses = $useDesktopVerticalThumbs
        ? 'frontsite-soft-scrollbar frontsite-soft-scrollbar--large frontsite-soft-scrollbar--inverse overflow-x-auto pb-2 lg:h-full lg:max-h-[38rem] lg:overflow-y-auto lg:overflow-x-hidden lg:pr-1 lg:pb-0'
        : 'frontsite-soft-scrollbar frontsite-soft-scrollbar--large frontsite-soft-scrollbar--inverse overflow-x-auto pb-2';
    $thumbListClasses = $useDesktopVerticalThumbs
        ? 'flex w-max snap-x snap-mandatory gap-3 lg:grid lg:w-auto lg:grid-cols-1 lg:gap-3'
        : 'flex w-max snap-x snap-mandatory gap-3';
@endphp

@if ($activeGalleryItem)
    <section id="{{ $resolvedSectionId }}" class="space-y-5">
        <div data-tour-gallery data-gallery-count="{{ $galleryItems->count() }}">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_34px_90px_-54px_rgba(15,23,42,0.28)]">
                <div class="space-y-4 px-4 py-4 sm:space-y-5 sm:p-6">
                    <div class="{{ $galleryShellClasses }}" @if ($galleryShellStyle) style="{{ $galleryShellStyle }}" @endif>
                        <div class="relative -mx-4 overflow-hidden rounded-none bg-[radial-gradient(circle_at_top_right,_rgba(255,140,0,0.18),_transparent_28%),linear-gradient(135deg,_#071120_0%,_#0c2f5e_48%,_#071120_100%)] sm:mx-0 sm:rounded-[1.8rem] {{ $stageClasses }}" data-tour-gallery-stage>
                            <div class="theme-grid-pattern absolute inset-0 opacity-20"></div>

                            @if ($hasGalleryNavigation)
                                <div class="frontsite-slider-nav">
                                    <button
                                        type="button"
                                        class="frontsite-hero-nav-button hidden h-11 w-11 items-center justify-center rounded-sm border border-white/15 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/16 sm:inline-flex"
                                        data-tour-gallery-prev
                                        aria-label="Media trước"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="frontsite-hero-nav-button hidden h-11 w-11 items-center justify-center rounded-sm border border-white/15 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/16 sm:inline-flex"
                                        data-tour-gallery-next
                                        aria-label="Media tiếp theo"
                                    >
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            @endif

                            <div class="absolute inset-x-4 top-4 z-10 flex flex-wrap items-start justify-between gap-3 sm:inset-x-6 sm:top-6">
                                <span class="inline-flex max-w-full items-center gap-2 rounded-full border border-white/14 bg-slate-950/45 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm" data-tour-gallery-badge>
                                    <i class="{{ $activeGalleryItem['media_icon'] }}"></i>
                                    {{ $activeGalleryItem['media_label'] }}
                                </span>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-white/14 bg-white/12 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm transition hover:bg-white/18"
                                    data-tour-gallery-open-active
                                >
                                    <i class="fa-solid fa-magnifying-glass-plus text-orange-200"></i>
                                    Phóng to
                                </button>
                            </div>

                            <div class="pointer-events-none absolute inset-x-0 bottom-4 z-10 flex justify-center sm:bottom-6">
                                <span class="pointer-events-auto inline-flex min-w-[5.25rem] items-center justify-center rounded-full border border-white/10 bg-slate-950/25 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur" data-tour-gallery-counter>
                                    1 / {{ $galleryItems->count() }}
                                </span>
                            </div>

                            <div class="relative aspect-[4/3] min-h-[18rem] w-full sm:aspect-[16/10] sm:min-h-[24rem]">
                                <img
                                    src="{{ $activeGalleryItem['lightbox_kind'] === 'image' ? $activeGalleryItem['stage_src'] : '' }}"
                                    alt="{{ $activeGalleryItem['resolved_alt'] }}"
                                    class="{{ $activeGalleryItem['lightbox_kind'] === 'image' ? '' : 'hidden ' }}h-full w-full object-cover"
                                    data-tour-gallery-image
                                    loading="lazy"
                                    decoding="async"
                                >
                                <video
                                    controls
                                    playsinline
                                    preload="metadata"
                                    class="{{ $activeGalleryItem['lightbox_kind'] === 'mp4' ? '' : 'hidden ' }}h-full w-full bg-black object-contain"
                                    data-tour-gallery-video
                                    @if ($activeGalleryItem['lightbox_kind'] === 'mp4' && $activeGalleryItem['lightbox_src'])
                                        src="{{ $activeGalleryItem['lightbox_src'] }}"
                                    @endif
                                    @if ($activeGalleryItem['stage_poster'])
                                        poster="{{ $activeGalleryItem['stage_poster'] }}"
                                    @endif
                                ></video>
                                <iframe
                                    class="{{ $activeGalleryItem['lightbox_kind'] === 'youtube' ? '' : 'hidden ' }}h-full w-full bg-black"
                                    data-tour-gallery-iframe
                                    src="{{ $activeGalleryItem['lightbox_kind'] === 'youtube' ? ($activeGalleryItem['embed_url'] ?? '') : '' }}"
                                    loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        </div>

                        @if ($hasGalleryNavigation)
                            <div class="{{ $thumbColumnClasses }}">
                                <div
                                    class="{{ $thumbRailClasses }}"
                                    data-tour-gallery-thumb-rail
                                    data-tour-gallery-thumb-layout="{{ $useDesktopVerticalThumbs ? 'vertical-desktop' : 'horizontal' }}"
                                >
                                    <div class="{{ $thumbListClasses }}">
                                    @foreach ($galleryItems as $item)
                                            @php
                                                $itemDescription = $loop->first && filled($primaryMediaDescription)
                                                    ? $primaryMediaDescription
                                                    : ($item['resolved_description'] ?? '');
                                            @endphp
                                        <button
                                            type="button"
                                            class="group w-24 shrink-0 snap-start overflow-hidden rounded-[1.4rem] border bg-white text-left transition sm:w-28 lg:w-full {{ $loop->first ? 'border-primary ring-2 ring-primary/15 shadow-[0_24px_50px_-36px_rgba(255,106,0,0.55)]' : 'border-slate-200 hover:border-orange-200' }}"
                                            data-tour-gallery-thumb
                                            data-tour-gallery-kind="{{ $item['lightbox_kind'] }}"
                                            data-tour-gallery-src="{{ $item['stage_src'] }}"
                                            data-tour-gallery-embed="{{ $item['embed_url'] ?? '' }}"
                                            data-tour-gallery-poster="{{ $item['stage_poster'] ?? '' }}"
                                            data-tour-gallery-title="{{ $item['resolved_title'] ?: $resolvedTitle }}"
                                            data-tour-gallery-description="{{ $itemDescription  }}"
                                            data-tour-gallery-alt="{{ $item['resolved_alt'] }}"
                                            data-tour-gallery-label="{{ $item['media_label'] }}"
                                            data-tour-gallery-icon="{{ $item['media_icon'] }}"
                                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                        >
                                            <span
                                                class="relative block aspect-[4/3] overflow-hidden bg-[linear-gradient(135deg,#071120_0%,#0c2f5e_52%,#f97316_100%)]"
                                                @if ($item['thumbnail_image'])
                                                    style="background-image:url('{{ $item['thumbnail_image'] }}');background-position:center;background-size:cover;"
                                                @endif
                                            >
                                                @if ($item['thumbnail_image'])
                                                    <img
                                                        src="{{ $item['thumbnail_image'] }}"
                                                        alt="{{ $item['resolved_alt'] }}"
                                                        class="absolute inset-0 block h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                                        @if ($loop->first)
                                                            fetchpriority="high"
                                                        @endif
                                                        decoding="async"
                                                    >
                                                @else
                                                    <span class="theme-grid-pattern block h-full w-full opacity-30"></span>
                                                    <span class="absolute inset-0 flex items-center justify-center text-white/90">
                                                        <i class="{{ $item['media_icon'] }} text-2xl"></i>
                                                    </span>
                                                @endif
                                            </span>
                                        </button>
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="hidden" aria-hidden="true" data-gallery-collection="{{ $resolvedCollectionKey }}">
                    @foreach ($galleryItems as $item)
                        @php
                            $itemDescription = $loop->first && filled($primaryMediaDescription)
                                ? $primaryMediaDescription
                                : ($item['resolved_description'] ?? '');
                        @endphp
                        <button
                            type="button"
                            class="hidden"
                            data-tour-gallery-lightbox-trigger
                            data-tour-gallery-index="{{ $loop->index }}"
                            data-gallery-trigger
                            data-gallery-kind="{{ $item['lightbox_kind'] }}"
                            data-gallery-src="{{ $item['lightbox_src'] }}"
                            data-gallery-embed="{{ $item['embed_url'] ?? '' }}"
                            data-gallery-poster="{{ $item['stage_poster'] ?? '' }}"
                            data-gallery-title="{{ $item['resolved_title'] ?: $resolvedTitle }}"
                            data-gallery-description="{{ $itemDescription  }}"
                            data-gallery-alt="{{ $item['resolved_alt'] }}"
                        >
                            {{ $item['resolved_title'] ?: $resolvedTitle }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
