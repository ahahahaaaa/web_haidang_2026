@php
    $filter = is_array($filter ?? null) ? $filter : [];
    $shortcuts = collect(data_get($filter, 'mobile_shortcuts', []));
    $popularLinks = collect(data_get($filter, 'popular_links', []));
@endphp

@if ($shortcuts->isNotEmpty() || $popularLinks->isNotEmpty())
    <section class="border-t border-slate-100 bg-white px-4 pb-4 pt-3 lg:hidden" data-mobile-tour-discovery aria-label="Khám phá dịch vụ du lịch">
        <div class="mx-auto max-w-7xl">
            @if ($shortcuts->isNotEmpty())
                <nav class="grid grid-cols-3 gap-x-3 gap-y-4" aria-label="Đi nhanh">
                    @foreach ($shortcuts as $shortcut)
                        <a href="{{ data_get($shortcut, 'url') }}" class="group flex min-w-0 flex-col items-center gap-2 text-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <span class="inline-flex size-14 items-center justify-center rounded-[1rem] bg-orange-50 text-primary ring-1 ring-orange-100 transition group-hover:bg-primary group-hover:text-white">
                                <i class="{{ data_get($shortcut, 'icon', 'fa-solid fa-route') }} text-xl" aria-hidden="true"></i>
                            </span>
                            <span class="line-clamp-2 text-xs font-semibold leading-5 text-slate-800">{{ data_get($shortcut, 'label') }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            @if ($popularLinks->isNotEmpty())
                <div class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-2 border-t border-slate-100 pt-3">
                    <span class="text-sm font-semibold text-slate-900">Nổi bật:</span>
                    <div class="min-w-0 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <div class="flex min-w-max items-center gap-2">
                            @foreach ($popularLinks as $link)
                                <a href="{{ data_get($link, 'url') }}" class="inline-flex min-h-9 items-center gap-2 rounded-full border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:border-orange-200 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <i class="fa-regular fa-star text-primary" aria-hidden="true"></i>
                                    {{ data_get($link, 'label') }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
