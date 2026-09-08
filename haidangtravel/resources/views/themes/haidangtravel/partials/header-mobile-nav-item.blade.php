@php
    $level = $level ?? 1;
    $hasChildren = collect($item['children'] ?? [])->isNotEmpty();
    $indentClass = $level > 1 ? 'ml-3' : '';
    $containerClasses = $level === 1
        ? 'rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3'
        : 'rounded-2xl border border-slate-200 bg-white px-4 py-3';
    $leafClasses = $level === 1
        ? 'flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)] hover:text-primary'
        : 'flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)] hover:text-primary';
@endphp

@if ($hasChildren)
    <details class="{{ $indentClass }} {{ $containerClasses }}">
        <summary class="flex list-none items-center justify-between gap-3 text-sm font-semibold text-slate-800">
            <span>{{ $item['label'] }}</span>
            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
        </summary>

        <div class="mt-3 grid gap-2 border-t border-slate-200 pt-3">
            <a
                href="{{ $item['url'] }}"
                @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
                class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary"
            >
                <span class="flex items-center gap-3">
                    @if (! empty($item['icon']))
                        <i class="{{ $item['icon'] }} text-base text-primary"></i>
                    @endif
                    <span>Xem {{ $item['label'] }}</span>
                </span>
                <i class="fa-solid fa-arrow-right text-xs text-slate-400"></i>
            </a>

            @foreach ($item['children'] as $child)
                @include('themes.haidangtravel.partials.header-mobile-nav-item', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    </details>
@else
    <a
        href="{{ $item['url'] }}"
        @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
        class="{{ $indentClass }} {{ $leafClasses }}"
    >
        <span class="flex items-center gap-3">
            @if (! empty($item['icon']))
                <i class="{{ $item['icon'] }} text-base text-primary"></i>
            @endif
            <span>{{ $item['label'] }}</span>
        </span>
        <i class="fa-solid fa-arrow-right text-xs text-slate-400"></i>
    </a>
@endif
