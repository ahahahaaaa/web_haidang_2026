@php
    $breadcrumbVariant = $breadcrumbVariant ?? 'prominent';
    $isPlainBreadcrumb = $breadcrumbVariant === 'plain';

    $listClasses = $isPlainBreadcrumb
        ? 'inline-flex max-w-full flex-wrap items-center gap-2 text-sm font-medium text-slate-950'
        : 'inline-flex max-w-full flex-wrap items-center gap-2 rounded-sm border border-white/15 bg-slate-950/45 px-4 py-2 text-sm font-medium text-white shadow-sm backdrop-blur';
    $separatorClasses = $isPlainBreadcrumb
        ? 'fa-solid fa-angle-right text-[11px] text-slate-400'
        : 'fa-solid fa-angle-right text-[11px] text-white/60';
    $linkClasses = $isPlainBreadcrumb
        ? 'text-slate-950 transition hover:text-primary'
        : 'text-white transition hover:text-orange-200';
    $inactiveTextClasses = $isPlainBreadcrumb ? 'text-slate-950' : 'text-white';
@endphp

@if (! empty($items))
    <nav aria-label="Breadcrumb" class="frontsite-text-reveal" data-reveal="meta">
        <ol class="{{ $listClasses }}">
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    @if (! $loop->first)
                        <i class="{{ $separatorClasses }}"></i>
                    @endif

                    @if (! empty($item['url']) && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="{{ $linkClasses }}">{{ $item['label'] }}</a>
                    @else
                        <span @if ($loop->last) aria-current="page" @endif class="{{ $loop->last ? 'font-bold text-primary' : $inactiveTextClasses }}">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
