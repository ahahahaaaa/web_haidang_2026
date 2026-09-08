@php
    $align = $align ?? 'left';
    $width = $width ?? 'max-w-3xl';
    $alignClasses = $align === 'center' ? 'mx-auto text-center items-center' : 'items-start text-left';
    $displayEyebrow = trim((string) ($eyebrow ?? ''));
    $displayDescription = trim((string) ($description ?? ''));
    $displayTitle = trim((string) ($title ?? ''));
    $showEyebrow = (bool) ($showEyebrow ?? false);
    $titleClasses = 'frontsite-text-reveal frontsite-h2';

    if ($align === 'center') {
        $titleClasses .= ' mx-auto text-center';
    }
@endphp

<div class="flex flex-col gap-4 {{ $width }} {{ $alignClasses }}">
    @if ($showEyebrow && $displayEyebrow !== '')
        <p class="frontsite-text-reveal text-sm font-semibold uppercase text-primary" data-reveal="eyebrow">
            {{ $displayEyebrow }}
        </p>
    @endif

    @if ($displayTitle !== '')
        <h2 class="{{ $titleClasses }}" data-reveal="title">
            {{ $displayTitle }}
        </h2>
    @endif

    @if ($displayDescription !== '')
        <p class="frontsite-text-reveal max-w-3xl text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">
            {{ $displayDescription }}
        </p>
    @endif
</div>
