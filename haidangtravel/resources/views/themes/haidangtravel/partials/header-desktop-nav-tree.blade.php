@php
    $level = $level ?? 2;
@endphp

<div class="grid gap-2">
    @foreach ($items as $item)
        @php
            $hasChildren = collect($item['children'] ?? [])->isNotEmpty();
            $itemClasses = $level === 2
                ? 'rounded-2xl border border-slate-100 bg-slate-50/80 p-3'
                : 'rounded-2xl border border-slate-100 bg-white p-3';
            $linkClasses = $level === 2
                ? 'flex items-center justify-between gap-3 rounded-2xl px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-white hover:text-primary'
                : 'flex items-center justify-between gap-3 rounded-2xl px-3 py-2 text-[13px] font-medium text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary';
            $leafClasses = $level === 2
                ? 'flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary'
                : 'flex items-center justify-between rounded-2xl px-3 py-2.5 text-[13px] font-medium text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary';
        @endphp

        @if ($hasChildren)
            <div class="{{ $itemClasses }}">
                <a
                    href="{{ $item['url'] }}"
                    @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
                    class="{{ $linkClasses }}"
                >
                    <span class="flex min-w-0 items-center gap-3 whitespace-nowrap">
                        @if (! empty($item['icon']))
                            <i class="{{ $item['icon'] }} text-base text-primary"></i>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-right shrink-0 text-[11px] text-slate-400"></i>
                </a>

                <div class="mt-2 grid gap-2 border-l border-slate-200 pl-4">
                    @include('themes.haidangtravel.partials.header-desktop-nav-tree', ['items' => $item['children'], 'level' => $level + 1])
                </div>
            </div>
        @else
            <a
                href="{{ $item['url'] }}"
                @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
                class="{{ $leafClasses }}"
            >
                <span class="flex min-w-0 items-center gap-3 whitespace-nowrap">
                    @if (! empty($item['icon']))
                        <i class="{{ $item['icon'] }} text-base text-primary"></i>
                    @endif
                    <span>{{ $item['label'] }}</span>
                </span>
                <i class="fa-solid fa-arrow-right shrink-0 text-xs text-slate-400"></i>
            </a>
        @endif
    @endforeach
</div>
