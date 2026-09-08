@php
    $tabs = collect(data_get($block ?? [], 'tabs', []))
        ->filter(fn ($tab) => is_array($tab) && filled($tab['uuid'] ?? null))
        ->values();
    $blockUuid = trim((string) data_get($block ?? [], 'uuid', 'tour-taxonomy-tabs'));
    $ctaLabel = trim((string) data_get($block ?? [], 'cta_label', '')) ?: 'Xem danh sách tour';
    $defaultTabId = trim((string) data_get($block ?? [], 'default_tab_id', ''));
    $sectionId = trim((string) ($sectionId ?? ''));
    $sectionClasses = trim((string) ($sectionClasses ?? 'bg-[linear-gradient(180deg,#fffaf6_0%,#f8fbff_100%)] px-4 py-8 sm:px-6 lg:px-8 lg:py-10'));
    $showRatings = ($showRatings ?? false) === true;
    $tabsLabel = trim((string) ($tabsLabel ?? 'Nhóm tour theo taxonomy'));

    if ($defaultTabId === '') {
        $defaultTabId = (string) ($tabs->first(fn (array $tab) => collect($tab['items'] ?? [])->isNotEmpty())['uuid'] ?? $tabs->first()['uuid'] ?? '');
    }

    $activeTab = $tabs->firstWhere('uuid', $defaultTabId) ?? $tabs->first();
    $activeDescription = trim((string) data_get($activeTab, 'description', ''));
@endphp

@if ($tabs->isNotEmpty() && is_array($activeTab))
    <section class="{{ $sectionClasses }}" @if ($sectionId !== '') id="{{ $sectionId }}" @endif>
        <div class="mx-auto max-w-7xl" data-tour-list-tabs>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h2 class="frontsite-text-reveal frontsite-h2" data-reveal="title" data-tour-list-summary-title>
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
                <a href="{{ data_get($activeTab, 'url') }}" class="frontsite-text-reveal inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-secondary transition hover:text-primary" data-reveal="cta" data-tour-list-summary-link>
                    {{ $ctaLabel }}
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

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

            <div class="mt-8 space-y-6">
                @foreach ($tabs as $tab)
                    @php
                        $tabId = (string) data_get($tab, 'uuid');
                        $isActiveTab = $tabId === $defaultTabId;
                        $panelId = 'tour-taxonomy-panel-'.$blockUuid.'-'.$tabId;
                        $buttonId = 'tour-taxonomy-tab-'.$blockUuid.'-'.$tabId;
                        $tabItems = collect(data_get($tab, 'items', []))->values();
                    @endphp

                    <section
                        id="{{ $panelId }}"
                        role="tabpanel"
                        aria-labelledby="{{ $buttonId }}"
                        data-tour-list-panel="{{ $tabId }}"
                        @if (! $isActiveTab) hidden @endif
                    >
                        <div
                            data-card-carousel
                            data-mobile-two-rows="true"
                            style="--desktop-columns: 4; --mobile-card-width: calc(83.333% - 0.17rem);"
                        >
                            @if ($tabItems->count() > 2)
                                <div class="mb-4 flex items-center justify-end gap-3 md:hidden frontsite-slider-nav">
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

                            <div class="service-card-carousel-track" data-card-carousel-track>
                                @forelse ($tabItems as $tour)
                                    <div class="service-card-carousel-item" data-card-carousel-item>
                                        @include('themes.haidangtravel.partials.tour-card', [
                                            'tour' => $tour,
                                            'variant' => 'default',
                                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                                            'showRating' => $showRatings,
                                        ])
                                    </div>
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
        </div>
    </section>
@endif
