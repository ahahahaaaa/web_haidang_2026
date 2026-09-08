@php
    $tabs = collect(data_get($block ?? [], 'tabs', []))
        ->filter(fn ($tab) => is_array($tab) && filled($tab['uuid'] ?? null))
        ->values();
    $blockUuid = trim((string) data_get($block ?? [], 'uuid', 'region-taxonomy-tabs'));
    $cardSourceType = array_key_exists(
        (string) data_get($block ?? [], 'card_source_type', 'destination'),
        \App\Support\LandingPageBlocks::regionTaxonomyCardTypes(),
    )
        ? (string) data_get($block ?? [], 'card_source_type', 'destination')
        : 'destination';
    $ctaLabel = trim((string) data_get($block ?? [], 'cta_label', '')) ?: 'Xem hub vùng miền';
    $cardCtaLabel = trim((string) data_get($block ?? [], 'card_cta_label', '')) ?: match ($cardSourceType) {
        'tour_category' => 'Xem chủ đề tour',
        default => 'Xem hub điểm đến',
    };
    $defaultTabId = trim((string) data_get($block ?? [], 'default_tab_id', ''));
    $sectionId = trim((string) ($sectionId ?? ''));
    $sectionClasses = trim((string) ($sectionClasses ?? 'bg-white px-4 py-8 sm:px-6 lg:px-8 lg:py-10'));
    $tabsLabel = trim((string) ($tabsLabel ?? 'Vùng miền'));
    $blockTitle = trim((string) data_get($block ?? [], 'title', ''));
    $blockDescription = trim((string) data_get($block ?? [], 'description', ''));
    $taxonomyType = $cardSourceType === 'tour_category' ? 'tour_category' : 'destination';
    $visualStyle = $cardSourceType === 'tour_category' ? 'topic' : 'destination';
    $allowedImageSizes = [
        \App\Support\FrontsiteMedia::SIZE_SMALL,
        \App\Support\FrontsiteMedia::SIZE_MEDIUM,
        \App\Support\FrontsiteMedia::SIZE_FULL,
    ];
    $avatarSize = in_array((string) ($imageSize ?? ''), $allowedImageSizes, true)
        ? (string) $imageSize
        : \App\Support\FrontsiteMedia::SIZE_SMALL;

    if ($defaultTabId === '') {
        $defaultTabId = (string) ($tabs->first(fn (array $tab) => collect($tab['items'] ?? [])->isNotEmpty())['uuid'] ?? $tabs->first()['uuid'] ?? '');
    }

    $activeTab = $tabs->firstWhere('uuid', $defaultTabId) ?? $tabs->first();
    $activeDescription = trim((string) data_get($activeTab, 'description', ''));
@endphp

@if ($tabs->isNotEmpty() && is_array($activeTab))
    <section class="{{ $sectionClasses }}" @if ($sectionId !== '') id="{{ $sectionId }}" @endif>
        <div class="mx-auto max-w-7xl" data-tour-list-tabs>
            @if ($blockTitle !== '' || $blockDescription !== '')
                <div class="w-full">
                    @if ($blockTitle !== '')
                        <h2 class="frontsite-text-reveal frontsite-h2" data-reveal="title">
                            {{ $blockTitle }}
                        </h2>
                    @endif
                    @if ($blockDescription !== '')
                        <p class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">
                            {{ $blockDescription }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="mt-6 grid min-w-0 gap-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start xl:grid-cols-[18.5rem_minmax(0,1fr)] xl:gap-8">
                <div class="min-w-0 lg:sticky lg:top-24">
                    <div class="frontsite-mobile-tablist frontsite-region-mobile-tablist" role="tablist" aria-label="{{ $tabsLabel }}">
                        @foreach ($tabs as $tab)
                            @php
                                $tabId = (string) data_get($tab, 'uuid');
                                $isActiveTab = $tabId === $defaultTabId;
                                $tabItemsCount = collect(data_get($tab, 'items', []))->count();
                                $buttonId = 'region-taxonomy-tab-'.$blockUuid.'-'.$tabId;
                                $panelId = 'region-taxonomy-panel-'.$blockUuid.'-'.$tabId;
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
                                class="frontsite-text-reveal inline-flex min-h-10 w-full min-w-0 flex-row items-center justify-between gap-1.5 rounded-xl border px-2.5 py-2 text-left text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 sm:min-h-11 sm:gap-2 sm:px-3 sm:py-2 sm:text-sm lg:min-h-12 lg:gap-2 lg:rounded-[1.15rem] lg:px-4 lg:py-2.5 {{ $isActiveTab ? 'border-transparent bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.58)]' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' }}"
                                data-reveal="meta"
                            >
                                <span class="min-w-0 flex-1 truncate leading-5">{{ trim((string) data_get($tab, 'label', '')) }}</span>
                                <span class="shrink-0 rounded-full bg-black/8 px-2 py-0.5 text-[10px] font-semibold leading-5 {{ $isActiveTab ? 'text-white/90' : 'text-slate-500' }}">
                                    {{ $tabItemsCount }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="frontsite-text-reveal font-heading text-[1.7rem] font-bold leading-tight text-slate-950 sm:text-[1.95rem]" data-reveal="title" data-tour-list-summary-title>
                                {{ data_get($activeTab, 'title') }}
                            </h3>
                            <p
                                class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base @if ($activeDescription === '') hidden @endif"
                                data-reveal="body"
                                data-tour-list-summary-description
                            >
                                {{ $activeDescription }}
                            </p>
                        </div>

                        <a
                            href="{{ data_get($activeTab, 'url') }}"
                            class="frontsite-text-reveal inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-secondary transition hover:text-primary"
                            data-reveal="cta"
                            data-tour-list-summary-link
                        >
                            {{ $ctaLabel }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="mt-6 space-y-6">
                        @foreach ($tabs as $tab)
                            @php
                                $tabId = (string) data_get($tab, 'uuid');
                                $isActiveTab = $tabId === $defaultTabId;
                                $panelId = 'region-taxonomy-panel-'.$blockUuid.'-'.$tabId;
                                $buttonId = 'region-taxonomy-tab-'.$blockUuid.'-'.$tabId;
                                $tabItems = collect(data_get($tab, 'items', []));
                                $desktopSlider = $tabItems->count() > 3;
                                $normalizedItems = $tabItems
                                    ->filter(fn ($item) => filled(data_get($item, 'name')))
                                    ->take(8)
                                    ->values()
                                    ->map(function ($item) use ($avatarSize, $taxonomyType): array {
                                        $label = trim((string) data_get($item, 'name'));
                                        $avatar = \App\Support\FrontsiteMedia::taxonomyAvatarUrl(
                                            $item,
                                            $avatarSize,
                                            'cover_image_url',
                                            false,
                                        ) ?: '';
                                        $summary = trim((string) data_get($item, 'excerpt', ''));

                                        return [
                                            'alt' => trim((string) (data_get($item, 'cover_alt') ?: $label)),
                                            'avatar' => $avatar,
                                            'label' => $label,
                                            'summary' => $summary,
                                            'url' => match ($taxonomyType) {
                                                'tour_category' => route('tour-categories.show', $item),
                                                default => route('destinations.show', $item),
                                            },
                                        ];
                                    });
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
                                    @if ($desktopSlider) data-desktop-slider="true" @endif
                                    style="--desktop-columns: 3; --desktop-card-width: calc((100% - 2rem) / 3); --mobile-card-width: calc(83.333% - 0.17rem); --tablet-card-width: calc((100% - 1rem) / 2.18);"
                                >
                                    @if ($normalizedItems->count() > 1)
                                        <div class="mb-4 flex items-center justify-end gap-3 frontsite-slider-nav">
                                            <button
                                                type="button"
                                                data-card-carousel-prev
                                                class="service-card-carousel-control"
                                                aria-label="{{ $taxonomyType === 'tour_category' ? 'Xem chủ đề tour trước' : 'Xem điểm đến trước' }}"
                                            >
                                                <i class="fa-solid fa-arrow-left"></i>
                                            </button>
                                            <button
                                                type="button"
                                                data-card-carousel-next
                                                class="service-card-carousel-control"
                                                aria-label="{{ $taxonomyType === 'tour_category' ? 'Xem chủ đề tour tiếp theo' : 'Xem điểm đến tiếp theo' }}"
                                            >
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    @endif

                                    <div class="service-card-carousel-track grid grid-cols-3 gap-2" data-card-carousel-track>
                                        @forelse ($normalizedItems as $item)
                                            <div class="service-card-carousel-item" data-card-carousel-item>
                                                <a
                                                    href="{{ $item['url'] }}"
                                                    class="frontsite-text-reveal group flex h-full flex-col"
                                                    data-reveal="card"
                                                    data-reveal-delay="{{ number_format(($loop->index % 3) * 0.08, 2, '.', '') }}"
                                                >
                                                    <div class="relative aspect-[4/5] overflow-hidden rounded-[1.65rem] bg-[linear-gradient(135deg,_rgba(0,74,153,0.14),_rgba(255,106,0,0.18))] shadow-[0_26px_64px_-46px_rgba(15,23,42,0.38)]">
                                                        @if ($item['avatar'] !== '')
                                                            <img
                                                                src="{{ $item['avatar'] }}"
                                                                alt="{{ $item['alt'] }}"
                                                                class="frontsite-taxonomy-zoom-image h-full w-full object-cover"
                                                                width="500"
                                                                height="625"
                                                                loading="lazy"
                                                                decoding="async"
                                                                fetchpriority="low"
                                                            >
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(255,140,0,0.26),_transparent_38%),linear-gradient(135deg,_rgba(0,74,153,0.18),_rgba(255,106,0,0.12))]">
                                                                <span class="font-heading text-4xl font-black uppercase tracking-[0.18em] text-white/88">
                                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item['label'], 0, 2)) }}
                                                                </span>
                                                            </div>
                                                        @endif

                                                        <div class="absolute inset-x-0 bottom-0 h-28 bg-[linear-gradient(180deg,rgba(2,6,23,0)_0%,rgba(2,6,23,0.82)_100%)]"></div>

                                                        <div class="pointer-events-none absolute inset-x-0 bottom-0 p-4">
                                                            <div class="relative overflow-hidden rounded-[1.15rem] bg-black/26 px-4 py-3 shadow-[0_16px_30px_-24px_rgba(15,23,42,0.36)] backdrop-blur-sm transition-[padding-bottom,background-color,box-shadow] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:bg-black/42 group-hover:pb-[5.75rem] group-hover:shadow-[0_24px_46px_-28px_rgba(15,23,42,0.5)]">
                                                                <h3 class="relative z-10 line-clamp-2 font-heading text-sm font-bold leading-6 text-white sm:text-[0.95rem]">
                                                                    {{ $item['label'] }}
                                                                </h3>

                                                                <div class="absolute inset-x-4 bottom-3 opacity-0 translate-y-3 transition-[opacity,transform] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-y-0 group-hover:opacity-100">
                                                                    <p class="line-clamp-3 text-sm leading-5 text-white/88">
                                                                        {{ $item['summary'] !== '' ? \Illuminate\Support\Str::limit(strip_tags($item['summary']), 120) : $cardCtaLabel }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        @empty
                                            <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm leading-7 text-slate-500">
                                                Chưa có nội dung phù hợp trong vùng miền này.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
