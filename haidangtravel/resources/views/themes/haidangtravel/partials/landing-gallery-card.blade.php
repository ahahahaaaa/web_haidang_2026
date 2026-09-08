@props([
    'item' => [],
    'cardClass' => '',
    'delay' => null,
    'intrinsicHeight' => false,
    'tagLabel' => '',
    'title' => '',
])

@php
    $imageUrl = trim((string) ($item['image_url'] ?? ''));
    $mobileImageUrl = trim((string) ($item['mobile_image_url'] ?? ''));
    $innerImageUrl = trim((string) ($item['inner_image_url'] ?? ''));
    $thumbnailUrl = trim((string) ($item['thumbnail_url'] ?? ''));
    $fallbackImageUrl = $imageUrl !== ''
        ? $imageUrl
        : ($mobileImageUrl !== '' ? $mobileImageUrl : ($innerImageUrl !== '' ? $innerImageUrl : $thumbnailUrl));
    $itemTitle = trim((string) $title) !== ''
        ? trim((string) $title)
        : trim((string) ($item['title'] ?? ''));
    $itemTagLabel = trim((string) $tagLabel);
    $itemUrl = trim((string) ($item['url'] ?? ''));
    $usesIntrinsicHeight = (bool) $intrinsicHeight;
    $mediaFrameClass = $usesIntrinsicHeight ? 'relative' : 'absolute inset-0';
    $pictureClass = $usesIntrinsicHeight ? 'block' : '';
    $imageClass = $usesIntrinsicHeight
        ? 'h-auto w-full transition duration-500 group-hover:scale-[1.04]'
        : 'h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]';
    $videoClass = $usesIntrinsicHeight ? 'aspect-video w-full object-cover' : 'h-full w-full object-cover';
    $iframeClass = $usesIntrinsicHeight ? 'aspect-video w-full' : 'h-full w-full';
    $hoverOverlayClasses = 'pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,_rgba(15,23,42,0.08)_0%,_rgba(15,23,42,0.2)_44%,_rgba(15,23,42,0.84)_100%)] opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-focus-visible:opacity-100';
    $hoverTitleClasses = 'absolute inset-x-0 bottom-0 translate-y-3 p-4 opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100 group-focus-visible:translate-y-0 group-focus-visible:opacity-100 sm:p-5';
@endphp

@if ($itemUrl !== '')
    <a
        href="{{ $itemUrl }}"
        class="{{ $cardClass }}"
        data-reveal="card"
        @if ($delay !== null) data-reveal-delay="{{ $delay }}" @endif
    >
        <div class="{{ $mediaFrameClass }}">
            @if (filled($item['embed_url'] ?? null))
                <iframe src="{{ $item['embed_url'] }}" title="{{ $itemTitle ?: 'Gallery video' }}" class="{{ $iframeClass }}" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            @elseif (filled($item['video_url'] ?? null) && ($item['kind'] ?? null) === 'mp4')
                <video src="{{ $item['video_url'] }}" class="{{ $videoClass }}" controls playsinline></video>
            @else
                <picture class="{{ $pictureClass }}">
                    @if ($mobileImageUrl !== '')
                        <source media="(max-width: 767px)" srcset="{{ $mobileImageUrl }}">
                    @endif
                    <img src="{{ $fallbackImageUrl }}" alt="{{ $item['image_alt'] ?: $itemTitle ?: 'Gallery image' }}" class="{{ $imageClass }}">
                </picture>
            @endif
        </div>

        <div class="{{ $hoverOverlayClasses }}"></div>

        @if ($itemTagLabel !== '')
            <div class="absolute left-4 top-4 rounded-full bg-white/16 px-3 py-1 text-[0.68rem] font-semibold uppercase text-white/92 backdrop-blur">
                {{ $itemTagLabel }}
            </div>
        @endif

        @if ($itemTitle !== '')
            <div class="{{ $hoverTitleClasses }}">
                <h3 class="font-heading text-lg font-extrabold leading-tight text-white drop-shadow-[0_14px_34px_rgba(15,23,42,0.48)] sm:text-xl">
                    {{ $itemTitle }}
                </h3>
            </div>
        @endif
    </a>
@else
    <div
        class="{{ $cardClass }}"
        data-reveal="card"
        @if ($delay !== null) data-reveal-delay="{{ $delay }}" @endif
    >
        <div class="{{ $mediaFrameClass }}">
            @if (filled($item['embed_url'] ?? null))
                <iframe src="{{ $item['embed_url'] }}" title="{{ $itemTitle ?: 'Gallery video' }}" class="{{ $iframeClass }}" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            @elseif (filled($item['video_url'] ?? null) && ($item['kind'] ?? null) === 'mp4')
                <video src="{{ $item['video_url'] }}" class="{{ $videoClass }}" controls playsinline></video>
            @else
                <picture class="{{ $pictureClass }}">
                    @if ($mobileImageUrl !== '')
                        <source media="(max-width: 767px)" srcset="{{ $mobileImageUrl }}">
                    @endif
                    <img src="{{ $fallbackImageUrl }}" alt="{{ $item['image_alt'] ?: $itemTitle ?: 'Gallery image' }}" class="{{ $imageClass }}">
                </picture>
            @endif
        </div>

        <div class="{{ $hoverOverlayClasses }}"></div>

        @if ($itemTagLabel !== '')
            <div class="absolute left-4 top-4 rounded-full bg-white/16 px-3 py-1 text-[0.68rem] font-semibold uppercase text-white/92 backdrop-blur">
                {{ $itemTagLabel }}
            </div>
        @endif

        @if ($itemTitle !== '')
            <div class="{{ $hoverTitleClasses }}">
                <h3 class="font-heading text-lg font-extrabold leading-tight text-white drop-shadow-[0_14px_34px_rgba(15,23,42,0.48)] sm:text-xl">
                    {{ $itemTitle }}
                </h3>
            </div>
        @endif
    </div>
@endif
