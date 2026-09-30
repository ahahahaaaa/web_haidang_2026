@php
    $blockViewMoreUrl = trim((string) ($url ?? ''));
    $blockViewMoreLabel = trim((string) ($label ?? '')) ?: 'Xem thêm';
    $blockViewMoreAttributes = new \Illuminate\View\ComponentAttributeBag($linkAttributes ?? []);

    if (\Illuminate\Support\Str::lower($blockViewMoreLabel) === 'xem danh sách tour') {
        $blockViewMoreLabel = 'Xem thêm';
    }
@endphp

@if ($blockViewMoreUrl !== '')
    <a
        href="{{ $blockViewMoreUrl }}"
        {{ $blockViewMoreAttributes->class([
            'frontsite-text-reveal' => $blockViewMoreAttributes->has('data-reveal'),
            'group inline-flex min-h-11 shrink-0 items-center justify-center gap-2 self-start rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 shadow-sm transition hover:border-orange-300 hover:bg-orange-100 hover:text-orange-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-200',
        ]) }}
    >
        <span>{{ $blockViewMoreLabel }}</span>
        <span class="inline-flex size-7 items-center justify-center text-orange-700 transition group-hover:text-orange-800" aria-hidden="true">
            <i class="fa-solid fa-arrow-right text-[11px]"></i>
        </span>
    </a>
@endif
