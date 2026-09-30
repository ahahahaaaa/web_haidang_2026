@php
    $filter = is_array($filter ?? null) ? $filter : [];
    $selected = is_array(data_get($filter, 'selected')) ? data_get($filter, 'selected') : [];
    $scopeOptions = collect(data_get($filter, 'scope_options', []));
    $departureLocations = collect(data_get($filter, 'departure_locations', []));
    $destinations = collect(data_get($filter, 'destinations', []));
    $departureDates = collect(data_get($filter, 'departure_dates', []))->filter()->values()->all();
    $popularLinks = collect(data_get($filter, 'popular_links', []));
    $productTabs = collect(data_get($filter, 'product_tabs', []));
    $action = data_get($filter, 'action', route('tours.search'));
    $buttonLabel = trim((string) ($buttonLabel ?? 'Tìm kiếm')) ?: 'Tìm kiếm';
    $destinationPlaceholder = trim((string) ($destinationPlaceholder ?? 'Bạn muốn đi đâu?')) ?: 'Bạn muốn đi đâu?';
    $sectionClasses = $sectionClasses ?? 'px-4 py-8 sm:px-6 lg:px-8';
    $containerClasses = $containerClasses ?? 'max-w-7xl';
    $minimumDepartureDate = now(config('app.timezone'))->addDay()->toDateString();
    $hiddenFilterKeys = ['q', 'category', 'transport', 'budget'];
    $hasActiveFilters = collect([
        data_get($selected, 'q'),
        data_get($selected, 'scope'),
        data_get($selected, 'departure_location'),
        data_get($selected, 'destination'),
        data_get($selected, 'departure_date'),
        data_get($selected, 'category'),
        data_get($selected, 'transport'),
        data_get($selected, 'budget'),
    ])->contains(fn ($value) => filled($value));
@endphp

<section class="{{ $sectionClasses }}" data-tour-search-panel aria-label="Bộ lọc tìm tour">
    <div class="mx-auto {{ $containerClasses }}">
        <div class="frontsite-text-reveal" data-reveal="panel">
            @if ($productTabs->isNotEmpty())
                <nav class="overflow-x-auto bg-transparent [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Nhóm sản phẩm du lịch">
                    <div class="flex min-w-max items-center gap-1 px-3 pt-3 sm:px-4">
                        @foreach ($productTabs as $tab)
                            <a
                                href="{{ data_get($tab, 'url') }}"
                                data-tour-search-product-tab="{{ data_get($tab, 'key') }}" data-tour-search-product-tab-active="{{ data_get($tab, 'is_active') ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex min-h-11 items-center gap-2 rounded-t-[1rem] px-4 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30',
                                    'bg-white text-primary shadow-[0_-8px_24px_-20px_rgba(255,106,0,0.45)]' => data_get($tab, 'is_active'),
                                    'bg-[color:var(--color-primary-soft)] text-primary hover:bg-orange-100' => ! data_get($tab, 'is_active'),
                                ])
                                @if (data_get($tab, 'is_active')) aria-current="page" @endif
                            >
                                <i class="{{ data_get($tab, 'icon', 'fa-solid fa-circle') }}" aria-hidden="true"></i>
                                <span>{{ data_get($tab, 'label') }}</span>
                            </a>
                        @endforeach
                    </div>
                </nav>
            @endif

            <div class="overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-[0_28px_90px_-48px_rgba(15,23,42,0.35)]">
            <form action="{{ $action }}" method="GET" class="grid gap-3 p-3 sm:p-4 lg:grid-cols-[minmax(13rem,0.85fr)_minmax(13rem,1fr)_minmax(15rem,1.2fr)_minmax(13rem,1fr)_minmax(9rem,0.65fr)] lg:items-stretch">
                @foreach ($hiddenFilterKeys as $hiddenFilterKey)
                    @if (filled(data_get($selected, $hiddenFilterKey)))
                        <input type="hidden" name="{{ $hiddenFilterKey }}" value="{{ data_get($selected, $hiddenFilterKey) }}">
                    @endif
                @endforeach

                <label class="flex min-h-16 items-center gap-3 rounded-[1.35rem] bg-slate-50 px-4 text-slate-900">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-primary shadow-[0_12px_28px_-18px_rgba(255,106,0,0.5)]">
                        <i class="fa-solid fa-route" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-500">Loại tour</span>
                        <select
                            name="scope"
                            class="w-full min-w-0 bg-transparent text-sm font-semibold text-slate-900 outline-none"
                            data-frontsite-select
                            data-frontsite-select-hide-placeholder="true"
                            data-frontsite-select-max-width="20rem"
                            data-frontsite-select-search="false"
                            data-frontsite-select-variant="ghost"
                            data-tour-search-scope
                        >
                            @foreach ($scopeOptions as $scopeOption)
                                @php
                                    $scopeValue = (string) data_get($scopeOption, 'value', '');
                                @endphp
                                <option value="{{ $scopeValue }}" @selected((string) data_get($selected, 'scope', '') === $scopeValue)>
                                    {{ data_get($scopeOption, 'label') }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>

                <label class="flex min-h-16 items-center gap-3 rounded-[1.35rem] bg-slate-50 px-4 text-slate-900">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-primary shadow-[0_12px_28px_-18px_rgba(255,106,0,0.5)]">
                        <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-500">Điểm khởi hành</span>
                        <select
                            name="departure_location"
                            class="w-full min-w-0 bg-transparent text-sm font-semibold text-slate-900 outline-none"
                            data-frontsite-select
                            data-frontsite-select-hide-placeholder="true"
                            data-frontsite-select-max-width="20rem"
                            data-frontsite-select-search="true"
                            data-frontsite-select-single-line="true"
                            data-frontsite-select-variant="ghost"
                        >
                            <option value="">Tất cả</option>
                            @foreach ($departureLocations as $location)
                                <option value="{{ data_get($location, 'value') }}" @selected((string) data_get($selected, 'departure_location') === (string) data_get($location, 'value'))>
                                    {{ data_get($location, 'label') }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>

                <label class="flex min-h-16 items-center gap-3 rounded-[1.35rem] bg-slate-50 px-4 text-slate-900">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-primary shadow-[0_12px_28px_-18px_rgba(255,106,0,0.5)]">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-500">Điểm đến</span>
                        <select
                            name="destination"
                            class="w-full min-w-0 bg-transparent text-sm font-semibold text-slate-900 outline-none"
                            data-frontsite-select
                            data-frontsite-select-hide-placeholder="true"
                            data-frontsite-select-max-width="22rem"
                            data-frontsite-select-placeholder="{{ $destinationPlaceholder }}"
                            data-frontsite-select-search="true"
                            data-frontsite-select-single-line="true"
                            data-frontsite-select-variant="ghost"
                        >
                            <option value="">{{ $destinationPlaceholder }}</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ data_get($destination, 'value') }}" @selected((string) data_get($selected, 'destination') === (string) data_get($destination, 'value'))>
                                    {{ data_get($destination, 'label') }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>

                <label class="flex min-h-16 items-center gap-3 rounded-[1.35rem] bg-slate-50 px-4 text-slate-900">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-primary shadow-[0_12px_28px_-18px_rgba(255,106,0,0.5)]">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-500">Ngày đi</span>
                        <input
                            type="date"
                            name="departure_date"
                            value="{{ data_get($selected, 'departure_date') }}"
                            min="{{ $minimumDepartureDate }}"
                            class="w-full min-w-0 border-0 bg-transparent px-0 text-sm font-semibold text-slate-900 outline-none"
                            data-frontsite-datepicker
                            data-frontsite-datepicker-enabled='@json($departureDates)'
                            data-frontsite-datepicker-max-width="20rem"
                            data-frontsite-datepicker-min-date="{{ $minimumDepartureDate }}"
                            data-frontsite-datepicker-month-selector="static"
                            data-frontsite-datepicker-placeholder="Chọn ngày"
                            data-frontsite-datepicker-variant="ghost"
                        >
                    </span>
                </label>

                <button type="submit" class="inline-flex min-h-16 items-center justify-center gap-2.5 rounded-[1.35rem] bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] px-6 text-base font-bold text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.68)] transition hover:brightness-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2">
                    <i class="fa-solid fa-magnifying-glass text-lg" aria-hidden="true"></i>
                    {{ $buttonLabel }}
                </button>
            </form>

            @if ($popularLinks->isNotEmpty() || $hasActiveFilters)
                <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    @if ($popularLinks->isNotEmpty())
                        <div class="grid min-w-0 flex-1 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2 sm:grid-cols-[auto_auto_minmax(0,1fr)_auto]" data-tour-search-popular>
                            <span class="col-span-3 shrink-0 text-sm font-semibold text-slate-800 sm:col-span-1">Tìm kiếm nổi bật:</span>
                            <button type="button" class="inline-flex aspect-square min-h-[46px] min-w-[46px] items-center justify-center rounded-full border border-orange-200 bg-white p-[15px] text-primary transition hover:border-primary hover:bg-orange-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-35" data-tour-search-popular-prev aria-label="Xem tìm kiếm nổi bật phía trước">
                                <i class="fa-solid fa-play rotate-180 text-[0.7rem]" aria-hidden="true"></i>
                            </button>
                            <div class="min-w-0 overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-tour-search-popular-track>
                                <div class="flex min-w-max items-center gap-2">
                                    @foreach ($popularLinks as $link)
                                        <a href="{{ data_get($link, 'url') }}" class="inline-flex min-h-9 items-center gap-2 rounded-full bg-orange-50 px-3 text-xs font-semibold text-primary transition hover:bg-orange-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                            <i class="fa-regular fa-star" aria-hidden="true"></i>
                                            {{ data_get($link, 'label') }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="inline-flex aspect-square min-h-[46px] min-w-[46px] items-center justify-center rounded-full border border-orange-200 bg-white p-[15px] text-primary transition hover:border-primary hover:bg-orange-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-35" data-tour-search-popular-next aria-label="Xem tìm kiếm nổi bật tiếp theo">
                                <i class="fa-solid fa-play text-[0.7rem]" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif

                    @if ($hasActiveFilters)
                        <a href="{{ route('tours.search') }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            Xóa bộ lọc
                        </a>
                    @endif
                </div>
            @endif
            </div>
        </div>
    </div>
</section>
