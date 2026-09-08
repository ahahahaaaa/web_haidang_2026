@php
    $contentHtml = trim((string) ($contentHtml ?? ''));
    $contentClass = trim((string) ($contentClass ?? '')) ?: 'theme-copy text-base leading-8 text-slate-600';
    $containerClass = trim((string) ($containerClass ?? '')) ?: 'space-y-4 px-6 py-6 sm:px-8 sm:py-8';
    $expandableId = trim((string) ($expandableId ?? '')) ?: 'expandable-rich-text-panel';
    $introClass = trim((string) ($introClass ?? '')) ?: 'text-base leading-8 text-slate-600';
    $introText = trim((string) ($introText ?? ''));
    $mobileCollapsedHeight = max(1, (int) ($mobileCollapsedHeight ?? 400));
    $desktopCollapsedHeight = max($mobileCollapsedHeight, (int) ($desktopCollapsedHeight ?? 800));
    $panelClass = trim((string) ($panelClass ?? '')) ?: 'space-y-6 overflow-hidden transition-[max-height] duration-300 ease-out';
    $toggleClass = trim((string) ($toggleClass ?? '')) ?: 'inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white px-4 py-2 text-sm font-semibold text-primary transition hover:bg-orange-50';
@endphp

@if ($introText !== '' || $contentHtml !== '')
    <div
        class="{{ $containerClass }}"
        data-tour-details-expandable-block
        data-expanded="false"
        data-collapsed-height-mobile="{{ $mobileCollapsedHeight }}"
        data-collapsed-height-desktop="{{ $desktopCollapsedHeight }}"
    >
        <div class="relative">
            <div
                id="{{ $expandableId }}"
                class="{{ $panelClass }}"
                data-tour-details-expandable
            >
                @if ($introText !== '')
                    <p class="{{ $introClass }}">{{ $introText }}</p>
                @endif

                @if ($contentHtml !== '')
                    <div class="{{ $contentClass }}">
                        {!! $contentHtml !!}
                    </div>
                @endif
            </div>

            <div class="pointer-events-none absolute inset-x-0 bottom-0 hidden h-24 bg-gradient-to-t from-white via-white/96 to-transparent" data-tour-details-fade></div>
        </div>

        <button
            type="button"
            class="{{ $toggleClass }}"
            data-tour-details-toggle
            aria-controls="{{ $expandableId }}"
            aria-expanded="false"
            hidden
        >
            <span data-tour-details-toggle-label>Xem thêm</span>
            <i class="fa-solid fa-chevron-down text-xs" data-tour-details-toggle-icon></i>
        </button>
    </div>
@endif
