@php
    $hasChildren = collect($item['children'] ?? [])->isNotEmpty();
@endphp

@if ($hasChildren)
    <div class="group relative">
        <a
            href="{{ $item['url'] }}"
            @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
        >
            <span>{{ $item['label'] }}</span>
            <i class="fa-solid fa-chevron-down text-[11px] transition group-hover:rotate-180"></i>
        </a>

        <div class="invisible pointer-events-none absolute left-0 top-full z-30 pt-2 opacity-0 transition duration-200 group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
            <div class="translate-y-1 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-[0_20px_50px_-20px_rgba(15,23,42,0.3)] transition duration-200 group-hover:translate-y-0 group-focus-within:translate-y-0">
                @include('themes.haidangtravel.partials.header-desktop-nav-tree', ['items' => $item['children'], 'level' => 2])
            </div>
        </div>
    </div>
@else
    <a
        href="{{ $item['url'] }}"
        @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
        class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary"
    >
        {{ $item['label'] }}
    </a>
@endif
