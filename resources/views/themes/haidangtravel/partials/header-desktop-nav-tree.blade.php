@php
    $level = $level ?? 2;
@endphp

<div class="flex flex-col gap-1 min-w-[220px]">
    @foreach ($items as $item)
        @php
            $hasChildren = collect($item['children'] ?? [])->isNotEmpty();
        @endphp

        @if ($hasChildren)
            <div class="group/sub relative">
                <a
                    href="{{ $item['url'] }}"
                    @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
                    class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary"
                >
                    <span class="flex items-center gap-2.5 min-w-0">
                        @if (! empty($item['icon']))
                            <i class="{{ $item['icon'] }} text-sm text-primary shrink-0"></i>
                        @endif
                        <span class="truncate">{{ $item['label'] }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-right shrink-0 text-[10px] text-slate-400 transition-transform group-hover/sub:translate-x-0.5"></i>
                </a>

                {{-- Horizontal sub-div flyout panel when hovering parent item --}}
                <div class="invisible pointer-events-none absolute left-full top-0 z-30 -mt-2 pl-2 opacity-0 transition duration-200 group-hover/sub:visible group-hover/sub:pointer-events-auto group-hover/sub:opacity-100 group-focus-within/sub:visible group-focus-within/sub:pointer-events-auto group-focus-within/sub:opacity-100">
                    <div class="translate-x-1 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-[0_20px_50px_-20px_rgba(15,23,42,0.3)] transition duration-200 group-hover/sub:translate-x-0">
                        @include('themes.haidangtravel.partials.header-desktop-nav-tree', ['items' => $item['children'], 'level' => $level + 1])
                    </div>
                </div>
            </div>
        @else
            <a
                href="{{ $item['url'] }}"
                @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary"
            >
                <span class="flex items-center gap-2.5 min-w-0">
                    @if (! empty($item['icon']))
                        <i class="{{ $item['icon'] }} text-sm text-primary shrink-0"></i>
                    @endif
                    <span class="truncate">{{ $item['label'] }}</span>
                </span>
            </a>
        @endif
    @endforeach
</div>


