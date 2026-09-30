@php
    $phone = trim((string) ($siteSettings->hotline ?: $siteSettings->phone));
    $phoneLink = $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact');
    $logoMedia = \App\Support\FrontsiteMedia::responsiveUrls($siteSettings, 'logo', 'logo_url');
    $logoUrl = $logoMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? null;
    $logoSmallUrl = $logoMedia[\App\Support\FrontsiteMedia::SIZE_SMALL] ?? $logoUrl;
    $menuItems = collect($headerMenu ?? [])
        ->filter(fn ($item) => filled($item->label ?? null) && filled($item->url ?? null))
        ->values();

    $childrenByParent = $menuItems->groupBy(fn ($item) => (int) ($item->parent_id ?? 0));

    $mapMenuTree = function (int $parentId = 0) use (&$mapMenuTree, $childrenByParent): array {
        return $childrenByParent
            ->get($parentId, collect())
            ->sortBy([
                ['order', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn ($item) => [
                'id' => (int) $item->getKey(),
                'label' => trim((string) $item->label),
                'url' => \App\Support\FrontsiteUrls::cleanInternalUrl($item->url) ?? $item->url,
                'target' => $item->target,
                'icon' => $item->icon,
                'children' => $mapMenuTree((int) $item->getKey()),
            ])
            ->values()
            ->all();
    };

    $navItems = collect($mapMenuTree());
    $sitewideTourSearch = is_array($sitewideTourSearchFilter ?? null) ? $sitewideTourSearchFilter : [];
    $mobileDepartureLocations = collect(data_get($sitewideTourSearch, 'departure_locations', []));
    $selectedDepartureKey = (string) data_get($sitewideTourSearch, 'selected.departure_location', '');
    $selectedDeparture = $mobileDepartureLocations->first(
        fn (array $location) => (string) data_get($location, 'value') === $selectedDepartureKey
    );
    $mobileDepartureLabel = (string) data_get(
        $selectedDeparture ?: $mobileDepartureLocations->first(),
        'label',
        'Chọn điểm đi'
    );
@endphp

<header class="relative z-40 border-b border-slate-200/70 bg-white/92 shadow-[0_10px_30px_-24px_rgba(15,23,42,0.5)] backdrop-blur-xl lg:sticky lg:top-0">
    <div class="relative mx-auto flex max-w-[1500px] items-center justify-between gap-2 px-4 py-3 sm:gap-3 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="Về trang chủ {{ $siteSettings->company_name ?: $siteSettings->site_name }}">
            @if ($logoUrl)
                <picture class="block">
                    @if ($logoSmallUrl)
                        <source media="(max-width: 767px)" srcset="{{ $logoSmallUrl }}">
                    @endif
                    <img
                        src="{{ $logoUrl }}"
                        alt="{{ $siteSettings->company_name ?: $siteSettings->site_name }}"
                        class="h-11 w-auto max-w-[112px] object-contain sm:h-12 sm:max-w-[176px] lg:max-w-[200px]"
                        width="200"
                        height="48"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    >
                </picture>
            @else
                <div class="flex h-11 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] px-4 text-sm font-bold tracking-[0.3em] text-white shadow-lg shadow-orange-500/20">
                    HĐ
                </div>
            @endif
        </a>

        <nav class="hidden items-center gap-1 min-[1360px]:flex min-[1480px]:gap-2">
            @foreach ($navItems as $item)
                @include('themes.haidangtravel.partials.header-desktop-nav-item', ['item' => $item])
            @endforeach
        </nav>

        <div class="hidden shrink-0 items-center gap-3 min-[1360px]:flex">
            @if ($phone !== '')
                <a
                    href="{{ $phoneLink }}"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-secondary transition hover:border-orange-200 hover:text-primary"
                    aria-label="Gọi {{ $phone }}"
                >
                    <i class="fa-solid fa-phone-volume text-primary"></i>
                    <span>{{ $phone }}</span>
                </a>
            @endif

        </div>

        <details class="group static ml-auto min-w-0 min-[1360px]:hidden" data-mobile-departure-selector>
            <summary class="flex min-h-11 max-w-[7.5rem] cursor-pointer list-none items-center gap-1.5 rounded-full px-1.5 text-sm font-medium text-slate-700 transition hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 sm:max-w-[9rem] [&::-webkit-details-marker]:hidden">
                <i class="fa-regular fa-circle-dot shrink-0 text-primary" aria-hidden="true"></i>
                <span class="truncate border-b border-primary/60">{{ $mobileDepartureLabel }}</span>
                <i class="fa-solid fa-chevron-down shrink-0 text-[0.6rem] text-slate-400 transition group-open:rotate-180" aria-hidden="true"></i>
            </summary>

            <div class="absolute inset-x-4 top-full z-50 mt-2 rounded-[1.25rem] border border-slate-200 bg-white p-5 shadow-[0_28px_70px_-30px_rgba(15,23,42,0.42)] sm:left-auto sm:right-16 sm:w-[22rem]">
                <h2 class="font-heading text-xl font-extrabold text-slate-950">Chọn địa điểm khởi hành</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">Chọn điểm đi để xem nhanh các hành trình phù hợp.</p>

                @if ($mobileDepartureLocations->isNotEmpty())
                    <div class="mt-4 grid grid-cols-2 gap-x-5 gap-y-1">
                        @foreach ($mobileDepartureLocations as $location)
                            <a
                                href="{{ route('tours.search', ['departure_location' => data_get($location, 'value')]) }}"
                                @class([
                                    'rounded-lg px-2 py-2 text-sm font-medium transition hover:bg-orange-50 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30',
                                    'bg-orange-50 text-primary' => (string) data_get($location, 'value') === $selectedDepartureKey,
                                    'text-slate-700' => (string) data_get($location, 'value') !== $selectedDepartureKey,
                                ])
                            >
                                {{ data_get($location, 'label') }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">Chưa có điểm khởi hành đang mở bán.</p>
                @endif
            </div>
        </details>

        <details class="relative min-[1360px]:hidden">
            <summary class="flex size-11 list-none items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-orange-200 hover:text-primary">
                <i class="fa-solid fa-bars text-base"></i>
                <span class="sr-only">Mở menu</span>
            </summary>

            <div class="absolute right-0 top-full z-30 mt-3 w-[min(92vw,360px)] rounded-3xl border border-slate-200 bg-white p-4 shadow-[0_28px_70px_-36px_rgba(15,23,42,0.5)]">
                <div class="grid gap-3">
                    @if ($phone !== '')
                        <a
                            href="{{ $phoneLink }}"
                            class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-secondary transition hover:border-orange-200 hover:text-primary"
                            aria-label="Gọi {{ $phone }}"
                        >
                            <i class="fa-solid fa-phone-volume text-primary"></i>
                            <span>{{ $phone }}</span>
                        </a>
                    @endif

                    <div class="grid gap-2">
                        @foreach ($navItems as $item)
                            @include('themes.haidangtravel.partials.header-mobile-nav-item', ['item' => $item, 'level' => 1])
                        @endforeach
                    </div>

                </div>
            </div>
        </details>
    </div>

    @include('themes.haidangtravel.partials.mobile-tour-discovery', [
        'filter' => $sitewideTourSearch,
    ])
</header>
