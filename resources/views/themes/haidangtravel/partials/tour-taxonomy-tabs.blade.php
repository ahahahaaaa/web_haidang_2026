@php
    $tabs = collect(data_get($block ?? [], 'tabs', []))
        ->filter(fn ($tab) => is_array($tab) && filled($tab['uuid'] ?? null))
        ->values();
    $blockUuid = trim((string) data_get($block ?? [], 'uuid', 'tour-taxonomy-tabs'));
    $ctaLabel = trim((string) data_get($block ?? [], 'cta_label', '')) ?: 'Xem thêm';
    $defaultTabId = trim((string) data_get($block ?? [], 'default_tab_id', ''));
    $sectionId = trim((string) ($sectionId ?? ''));
    $sectionClasses = trim((string) ($sectionClasses ?? 'bg-[linear-gradient(180deg,#fffaf6_0%,#f8fbff_100%)] px-4 py-8 sm:px-6 lg:px-8 lg:py-10'));
    $showRatings = ($showRatings ?? true) !== false;
    $isSlider = (bool) data_get($block ?? [], 'is_slider', false);
    $showFilters = (bool) data_get($block ?? [], 'show_filters', true);
    $tabsLabel = trim((string) ($tabsLabel ?? 'Nhóm tour theo taxonomy'));
    $popularSearches = collect(data_get($block ?? [], 'popular_searches', []))
        ->filter(fn ($item) => is_array($item)
            && filled($item['label'] ?? null)
            && \App\Support\TravelHomePageConfig::isSafeFeaturedTourPopularSearchUrl($item['url'] ?? null))
        ->values();

    if ($defaultTabId === '') {
        $defaultTabId = (string) ($tabs->first(fn (array $tab) => collect($tab['items'] ?? [])->isNotEmpty())['uuid'] ?? $tabs->first()['uuid'] ?? '');
    }

    $activeTab = $tabs->firstWhere('uuid', $defaultTabId) ?? $tabs->first();
    $visibleTabs = $showFilters ? $tabs : $tabs->where('uuid', 'all');
    $activeDescription = trim((string) data_get($activeTab, 'description', ''));
@endphp

@if ($tabs->isNotEmpty() && is_array($activeTab))
    <section class="{{ $sectionClasses }}" @if ($sectionId !== '') id="{{ $sectionId }}" @endif>
        <div class="mx-auto max-w-7xl" data-tour-list-tabs>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <h2 class="frontsite-text-reveal frontsite-h2" data-reveal="title" data-tour-list-summary-title>
                        <i class="fa-solid fa-route mr-2 text-primary" aria-hidden="true"></i>
                        {{ data_get($activeTab, 'title') }}
                    </h2>
                    <p
                        class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base @if ($activeDescription === '') hidden @endif"
                        data-reveal="body"
                        data-tour-list-summary-description
                    >
                        {{ $activeDescription }}
                    </p>
                </div>
                @include('themes.haidangtravel.partials.block-view-more-link', [
                    'label' => $ctaLabel,
                    'linkAttributes' => [
                        'data-reveal' => 'cta',
                        'data-tour-list-summary-link' => true,
                    ],
                    'url' => data_get($activeTab, 'url'),
                ])
            </div>

            @if ($showFilters)
            <div class="frontsite-mobile-tablist frontsite-tour-mobile-tablist mt-6" role="tablist" aria-label="{{ $tabsLabel }}">
                @foreach ($tabs as $tab)
                    @php
                        $tabId = (string) data_get($tab, 'uuid');
                        $isActiveTab = $tabId === $defaultTabId;
                        $tabItemsCount = collect(data_get($tab, 'items', []))->count();
                        $buttonId = 'tour-taxonomy-tab-'.$blockUuid.'-'.$tabId;
                        $panelId = 'tour-taxonomy-panel-'.$blockUuid.'-'.$tabId;
                    @endphp

                    <button
                        type="button"
                        id="{{ $buttonId }}"
                        role="tab"
                        aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                        aria-controls="{{ $panelId }}"
                        tabindex="{{ $isActiveTab ? '0' : '-1' }}"
                        data-tour-list-tab="{{ $tabId }}"
                        data-tour-list-tab-description="{{ trim((string) data_get($tab, 'description', '')) }}"
                        data-tour-list-tab-title="{{ trim((string) data_get($tab, 'title', '')) }}"
                        data-tour-list-tab-url="{{ data_get($tab, 'url') }}"
                        class="frontsite-text-reveal inline-flex min-h-9 items-center justify-center rounded-full border px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 md:min-h-11 md:px-5 md:py-3 {{ $isActiveTab ? 'border-transparent bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.58)]' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' }}"
                        data-reveal="meta"
                    >
                        {{ trim((string) data_get($tab, 'label', '')) }}
                        <span class="ml-2 rounded-full bg-black/8 px-2.5 py-1 text-[11px] font-semibold {{ $isActiveTab ? 'text-white/90' : 'text-slate-500' }}">
                            {{ $tabItemsCount }}
                        </span>
                    </button>
                @endforeach
            </div>
            @endif

            <div class="{{ $showFilters ? 'mt-8' : 'mt-6' }} space-y-6">
                @foreach ($visibleTabs as $tab)
                    @php
                        $tabId = (string) data_get($tab, 'uuid');
                        $isActiveTab = $tabId === $defaultTabId;
                        $panelId = 'tour-taxonomy-panel-'.$blockUuid.'-'.$tabId;
                        $buttonId = 'tour-taxonomy-tab-'.$blockUuid.'-'.$tabId;
                        $tabItems = collect(data_get($tab, 'items', []))->values();
                    @endphp

                    <section
                        id="{{ $panelId }}"
                        @if ($showFilters) role="tabpanel" aria-labelledby="{{ $buttonId }}" @endif
                        data-tour-list-panel="{{ $tabId }}"
                        @if (! $isActiveTab) hidden @endif
                    >
                        <div
                            class="frontsite-slider-stage"
                            @if ($isSlider)
                                data-card-carousel
                                data-desktop-slider="true"
                                data-tour-card-slider="true"
                                style="--desktop-columns: 4; --mobile-card-width: calc(83.333% - 0.17rem); --desktop-card-width: calc((100% - 3rem) / 4);"
                            @endif
                        >
                            @if ($isSlider && $tabItems->count() > 1)
                                <div class="frontsite-slider-nav">
                                    <button
                                        type="button"
                                        data-card-carousel-prev
                                        class="service-card-carousel-control"
                                        aria-label="Xem nhóm tour trước"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                    </button>
                                    <button
                                        type="button"
                                        data-card-carousel-next
                                        class="service-card-carousel-control"
                                        aria-label="Xem nhóm tour tiếp theo"
                                    >
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            @endif

                            <div class="{{ $isSlider ? 'service-card-carousel-track' : \App\Support\FrontsiteCardGrid::DEFAULT_CLASSES }}" @if ($isSlider) data-card-carousel-track @endif>
                                @forelse ($tabItems as $tour)
                                    @if ($isSlider)<div class="service-card-carousel-item" data-card-carousel-item>@endif
                                        @include('themes.haidangtravel.partials.tour-card', [
                                            'tour' => $tour,
                                            'variant' => 'default',
                                            'ctaVariant' => data_get($block, 'card_cta_variant'),
                                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                                            'showRating' => $showRatings,
                                        ])
                                    @if ($isSlider)</div>@endif
                                @empty
                                    <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm leading-7 text-slate-500">
                                        Chưa có tour phù hợp trong nhóm tab này. Bạn có thể mở trang taxonomy để xem thêm hành trình đang hoạt động.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            @if ($popularSearches->isNotEmpty())
                <nav class="mt-6 grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2 border-t border-slate-100 pt-5 sm:grid-cols-[auto_auto_minmax(0,1fr)_auto]" aria-label="Tìm kiếm tour nổi bật" data-tour-list-popular-searches data-popular-search-rail>
                    <span class="col-span-3 shrink-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-700 sm:col-span-1">Tìm kiếm nổi bật:</span>
                    <button type="button" class="inline-flex aspect-square min-h-[46px] min-w-[46px] items-center justify-center rounded-full border border-orange-200 bg-white p-[15px] text-primary transition hover:border-primary hover:bg-orange-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-35" data-popular-search-rail-prev aria-label="Xem tìm kiếm nổi bật phía trước">
                        <i class="fa-solid fa-play rotate-180 text-[0.7rem]" aria-hidden="true"></i>
                    </button>
                    <div class="min-w-0 overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-popular-search-rail-track>
                        <div class="flex min-w-max items-center gap-2">
                            @foreach ($popularSearches as $popularSearch)
                                @php
                                    $searchFilterUuid = trim((string) data_get($popularSearch, 'filter_uuid', ''));
                                    $searchIsVisible = $defaultTabId === 'all' || $searchFilterUuid === $defaultTabId;
                                @endphp
                                <a
                                    href="{{ $popularSearch['url'] }}"
                                    data-tour-list-popular-search-filter="{{ $searchFilterUuid }}"
                                    class="inline-flex min-h-9 shrink-0 items-center rounded-full border border-orange-200 bg-orange-50 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.04em] text-orange-700 transition hover:border-orange-300 hover:bg-orange-100 hover:text-orange-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-300 {{ $searchIsVisible ? '' : 'hidden' }}"
                                    @if (! $searchIsVisible) hidden aria-hidden="true" @endif
                                >
                                    {{ $popularSearch['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="inline-flex aspect-square min-h-[46px] min-w-[46px] items-center justify-center rounded-full border border-orange-200 bg-white p-[15px] text-primary transition hover:border-primary hover:bg-orange-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-35" data-popular-search-rail-next aria-label="Xem tìm kiếm nổi bật tiếp theo">
                        <i class="fa-solid fa-play text-[0.7rem]" aria-hidden="true"></i>
                    </button>
                </nav>
            @endif
        </div>
    </section>
@endif
