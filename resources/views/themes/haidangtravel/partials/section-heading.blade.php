@php
    $align = $align ?? 'left';
    $width = $width ?? 'max-w-3xl';
    $alignClasses = $align === 'center' ? 'mx-auto text-center items-center' : 'items-start text-left';
    $displayEyebrow = trim((string) ($eyebrow ?? ''));
    $displayDescription = trim((string) ($description ?? ''));
    $displayTitle = trim((string) ($title ?? ''));
    $showEyebrow = (bool) ($showEyebrow ?? false);
    $displayIcon = trim((string) ($icon ?? ''));
    $displayCtaLabel = trim((string) ($ctaLabel ?? ''));
    $displayCtaUrl = trim((string) ($ctaUrl ?? ''));
    $titleClasses = 'frontsite-text-reveal frontsite-h2';

    if ($align === 'center') {
        $titleClasses .= ' mx-auto text-center';
    }
@endphp

<div class="flex w-full flex-col gap-4 {{ $displayCtaUrl !== '' ? 'sm:flex-row sm:items-start sm:justify-between' : '' }}">
    <div class="flex flex-col gap-4 {{ $width }} {{ $alignClasses }}">
        @if ($showEyebrow && $displayEyebrow !== '')
            <p class="frontsite-text-reveal text-sm font-semibold uppercase text-primary" data-reveal="eyebrow">
                {{ $displayEyebrow }}
            </p>
        @endif

        @if ($displayTitle !== '')
            <h2 class="{{ $titleClasses }}" data-reveal="title">
                @if ($displayIcon !== '')
                    <i class="{{ $displayIcon }} mr-2 text-primary" aria-hidden="true"></i>
                @endif
                {{ $displayTitle }}
            </h2>
        @endif

        @if ($displayDescription !== '')
            <p class="frontsite-text-reveal max-w-3xl text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">
                {{ $displayDescription }}
            </p>
        @endif
    </div>

    @include('themes.haidangtravel.partials.block-view-more-link', [
        'label' => $displayCtaLabel !== '' ? $displayCtaLabel : 'Xem thêm',
        'url' => $displayCtaUrl,
    ])
</div>
