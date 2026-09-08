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
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/92 shadow-[0_10px_30px_-24px_rgba(15,23,42,0.5)] backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="Về trang chủ {{ $siteSettings->company_name ?: $siteSettings->site_name }}">
            @if ($logoUrl)
                <picture class="block">
                    @if ($logoSmallUrl)
                        <source media="(max-width: 767px)" srcset="{{ $logoSmallUrl }}">
                    @endif
                    <img
                        src="{{ $logoUrl }}"
                        alt="{{ $siteSettings->company_name ?: $siteSettings->site_name }}"
                        class="h-11 w-auto max-w-[144px] object-contain sm:h-12 sm:max-w-[176px] lg:max-w-[200px]"
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

        <nav class="hidden items-center gap-2 lg:flex">
            @foreach ($navItems as $item)
                @include('themes.haidangtravel.partials.header-desktop-nav-item', ['item' => $item])
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
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

        <details class="relative lg:hidden">
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
</header>
