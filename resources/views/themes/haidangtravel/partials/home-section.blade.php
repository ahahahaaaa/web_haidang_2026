@php
    $sectionKey = (string) ($sectionKey ?? '');
    $sectionSlot = \App\Support\TravelHomePageConfig::homeSectionSlot($sectionKey);
    $renderSlotBlocks = ($renderSlotBlocks ?? true) === true;
@endphp

@if ($renderSlotBlocks && $sectionSlot)
    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $homeSlot($sectionSlot),
    ])
@endif

@switch($sectionKey)
    @case('search')
        @if ($homeBlockEnabled('search'))
            @include('themes.haidangtravel.partials.shared-search-bar', [
                'action' => route('tours.search'),
                'buttonLabel' => trim((string) data_get($searchConfig, 'button_label', '')) ?: 'Tìm',
                'containerClasses' => 'max-w-5xl',
                'inputValue' => request('q'),
                'placeholder' => trim((string) data_get($searchConfig, 'placeholder', '')) ?: 'Bạn muốn đi đâu?',
                'sectionClasses' => 'relative z-10 -mt-6 px-4 sm:px-6 lg:-mt-8 lg:px-8',
            ])
        @endif
        @break

    @case('geo_answer')
        @if ($homeBlockEnabled('geo_answer'))
            @include('themes.haidangtravel.partials.geo-answer-panel', [
                'geo' => $geo ?? [],
                'sectionClasses' => 'px-4 py-7 sm:px-6 lg:px-8',
            ])
        @endif
        @break

    @case('topic_rail')
        @if ($homeBlockEnabled('topic_rail') && ! $homeHasPositionedBlockType(\App\Support\LandingPageBlocks::TYPE_TOPIC_RAIL))
            @include('themes.haidangtravel.partials.home-tour-topics', [
                'description' => trim((string) data_get($topicRailConfig, 'description', '')),
                'eyebrow' => trim((string) data_get($topicRailConfig, 'eyebrow', '')),
                'title' => trim((string) data_get($topicRailConfig, 'title', '')),
                'tourCategories' => $homeTourCategories ?? collect(),
            ])
        @endif
        @break

    @case('featured_tours')
        @if ($homeBlockEnabled('featured_tours'))
            <section class="bg-white px-4 py-8 sm:px-6 lg:px-8 lg:py-10" id="featured-tours">
                <div class="mx-auto max-w-7xl" data-home-featured-tabs>
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <h2 class="frontsite-text-reveal frontsite-h2" data-reveal="title" data-home-featured-summary-title>{{ $activeFeaturedTab['title'] }}</h2>
                            <p class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body" data-home-featured-summary-description>{{ $activeFeaturedTab['description'] }}</p>
                        </div>
                        <a href="{{ $activeFeaturedTab['url'] }}" class="frontsite-text-reveal inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-secondary transition hover:text-primary" data-reveal="cta" data-home-featured-summary-link>
                            {{ $featuredTourCtaLabel }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="frontsite-mobile-tablist frontsite-featured-mobile-tablist mt-6" role="tablist" aria-label="Nhóm tour">
                        @foreach ($featuredTabs as $tab)
                            @php
                                $isActiveTab = $tab['id'] === $defaultFeaturedTabId;
                            @endphp

                            <button
                                type="button"
                                id="home-featured-tab-{{ $tab['id'] }}"
                                role="tab"
                                aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                                aria-controls="home-featured-panel-{{ $tab['id'] }}"
                                tabindex="{{ $isActiveTab ? '0' : '-1' }}"
                                data-home-featured-tab="{{ $tab['id'] }}"
                                data-home-featured-tab-description="{{ $tab['description'] }}"
                                data-home-featured-tab-title="{{ $tab['title'] }}"
                                data-home-featured-tab-url="{{ $tab['url'] }}"
                                class="frontsite-text-reveal inline-flex min-h-9 items-center justify-center rounded-full border px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 md:min-h-11 md:px-5 md:py-3 {{ $isActiveTab ? 'border-transparent bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.58)]' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' }}"
                                data-reveal="meta"
                            >
                                {{ $tab['label'] }}
                                <span class="ml-2 rounded-full bg-black/8 px-2.5 py-1 text-[11px] font-semibold {{ $isActiveTab ? 'text-white/90' : 'text-slate-500' }}">
                                    {{ $tab['items']->count() }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-8 space-y-6">
                        @foreach ($featuredTabs as $tab)
                            @php
                                $isActiveTab = $tab['id'] === $defaultFeaturedTabId;
                                $featuredTourCardGridClasses = \App\Support\FrontsiteCardGrid::DEFAULT_CLASSES;
                                $featuredTourCardVariant = 'default';
                            @endphp

                            <section
                                id="home-featured-panel-{{ $tab['id'] }}"
                                role="tabpanel"
                                aria-labelledby="home-featured-tab-{{ $tab['id'] }}"
                                data-home-featured-panel="{{ $tab['id'] }}"
                                @if (! $isActiveTab) hidden @endif
                            >
                                <div class="{{ $featuredTourCardGridClasses }}">
                                    @forelse ($tab['items'] as $tour)
                                        @include('themes.haidangtravel.partials.tour-card', [
                                            'tour' => $tour,
                                            'showRating' => true,
                                            'variant' => $featuredTourCardVariant,
                                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                                        ])
                                    @empty
                                        <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm leading-7 text-slate-500">
                                            Chưa có tour phù hợp trong nhóm này. Bạn có thể mở toàn bộ danh mục để xem thêm hành trình đang hoạt động.
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
        @break

    @case('tour_taxonomy_tabs')
        @includeWhen($homeBlockEnabled('tour_taxonomy_tabs') && $homeTourTaxonomyTabsBlock && ! $homeHasPositionedBlockType(\App\Support\LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS), 'themes.haidangtravel.partials.tour-taxonomy-tabs', [
            'block' => $homeTourTaxonomyTabsBlock,
            'sectionId' => 'home-tour-taxonomy-tabs',
            'tabsLabel' => 'Tour theo vùng, điểm đến và chủ đề',
        ])
        @break

    @case('region_taxonomy_tabs')
        @includeWhen($homeBlockEnabled('region_taxonomy_tabs') && $homeRegionTaxonomyTabsBlock && ! $homeHasPositionedBlockType(\App\Support\LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS), 'themes.haidangtravel.partials.region-taxonomy-tabs', [
            'block' => $homeRegionTaxonomyTabsBlock,
            'imageSize' => \App\Support\FrontsiteMedia::SIZE_MEDIUM,
            'sectionId' => 'home-region-taxonomy-tabs',
            'tabsLabel' => 'Vùng miền nổi bật',
        ])
        @break

    @case('destination_slider')
        @if ($homeBlockEnabled('destination_slider'))
            @include('themes.haidangtravel.partials.home-destination-slider', [
                'cardCtaLabel' => trim((string) data_get($destinationSliderConfig, 'card_cta_label', '')) ?: 'Xem hub điểm đến',
                'description' => trim((string) data_get($destinationSliderConfig, 'description', '')) ?: 'Lướt nhanh các hub điểm đến đang có tour hoạt động để chọn hướng đi phù hợp trước khi xem sâu hơn ở phần Điểm đến yêu thích.',
                'destinations' => $homeDestinations ?? collect(),
                'title' => trim((string) data_get($destinationSliderConfig, 'title', '')) ?: 'Điểm đến nổi bật',
            ])
        @endif
        @break

    @case('gallery')
        @if ($homeBlockEnabled('gallery') && ! $homeHasPositionedBlockType([
            \App\Support\LandingPageBlocks::TYPE_GALLERY_SLIDER,
            \App\Support\LandingPageBlocks::TYPE_GALLERY_MEDIA,
        ]))
            @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])
        @endif
        @break

    @case('services')
        @if ($homeBlockEnabled('services'))
            <section class="bg-white px-4 py-8 sm:px-6 lg:px-8 lg:py-10" id="core-services">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <h2 class="{{ $homeBlockTitleClasses }}" data-reveal="title">{{ $servicesTitle }}</h2>
                            @if ($servicesDescription !== '')
                                <p class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">{{ $servicesDescription }}</p>
                            @endif
                        </div>

                        <a href="{{ $servicesCtaUrl }}" class="frontsite-text-reveal inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-secondary transition hover:text-primary" data-reveal="cta">
                            {{ $servicesCtaLabel }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                        @forelse ($featuredServices as $service)
                            <article class="frontsite-text-reveal group flex h-full flex-col rounded-[1.75rem] border border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fff9f3_100%)] p-7 shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition hover:-translate-y-1 hover:border-orange-200" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}">
                                <div class="inline-flex size-16 items-center justify-center rounded-2xl bg-orange-50 text-primary ring-1 ring-orange-100 transition group-hover:bg-primary group-hover:text-white group-hover:ring-primary/20">
                                    <i class="{{ $serviceIconClass($service) }} text-[2.45rem]" aria-hidden="true"></i>
                                </div>
                                <h3 class="mt-6 font-heading text-xl font-bold text-slate-950">{{ $service->title }}</h3>
                                <p class="mt-4 flex-1 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags((string) $service->excerpt), 140) }}</p>
                                <a href="{{ route('services.show', $service) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-secondary transition hover:text-primary">
                                    Xem chi tiết
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </article>
                        @empty
                            <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm leading-7 text-slate-500">
                                Chưa có dịch vụ nổi bật để hiển thị.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
        @break

    @case('trust')
        @if ($homeBlockEnabled('trust'))
            @include('themes.haidangtravel.partials.trust-proof-section', [
                'cards' => $homeTrustCards,
                'description' => $homeTrustDescription,
                'sectionClasses' => 'overflow-hidden bg-[linear-gradient(180deg,#fffaf5_0%,#eef5ff_100%)] px-4 py-8 sm:px-6 lg:px-8 lg:py-10',
                'sectionId' => 'trust-and-proof',
                'stats' => $stats,
                'title' => $homeTrustTitle,
            ])
        @endif
        @break

    @case('process')
        @if ($homeBlockEnabled('process') && $processCards->isNotEmpty())
            <section class="bg-white px-4 py-8 sm:px-6 lg:px-8 lg:py-10" id="booking-process">
                <div class="mx-auto max-w-7xl">
                    <div class="mx-auto max-w-3xl text-center">
                        <h2 class="{{ $homeBlockTitleCenterClasses }}" data-reveal="title">{{ $processTitle }}</h2>
                        @if ($processDescription !== '')
                            <p class="frontsite-text-reveal mt-4 text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">{{ $processDescription }}</p>
                        @endif
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($processCards as $card)
                            <article class="frontsite-text-reveal overflow-hidden rounded-[1.75rem] border border-orange-100 bg-[linear-gradient(180deg,_#fff8f2_0%,_#ffffff_100%)] shadow-[0_24px_70px_-50px_rgba(255,106,0,0.25)]" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}">
                                @if ($card['image_url'] !== '')
                                    <div class="relative aspect-[4/3] overflow-hidden bg-[linear-gradient(135deg,_rgba(255,106,0,0.18),_rgba(255,140,0,0.12))]">
                                        <img
                                            src="{{ $card['image_url'] }}"
                                            alt="{{ $card['image_alt'] !== '' ? $card['image_alt'] : $card['title'] }}"
                                            class="h-full w-full object-cover"
                                            width="500"
                                            height="375"
                                            loading="lazy"
                                            decoding="async"
                                            fetchpriority="low"
                                        >
                                        <div class="absolute left-4 top-4 inline-flex size-14 items-center justify-center rounded-full bg-white/96 text-lg font-black text-primary shadow-sm">
                                            {{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}
                                        </div>
                                    </div>
                                @endif

                                <div class="p-6">
                                    @if ($card['image_url'] === '')
                                        <div class="inline-flex size-14 items-center justify-center rounded-full bg-white text-lg font-black text-primary shadow-sm">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</div>
                                    @endif

                                    <h3 class="mt-5 font-heading text-xl font-bold text-slate-950">{{ $card['title'] }}</h3>
                                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ $card['description'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
        @break

    @case('blog_preview')
        @if ($homeBlockEnabled('blog_preview'))
            <section class="bg-[color:var(--color-bg-soft)] px-4 py-8 sm:px-6 lg:px-8 lg:py-10" id="blog-preview">
                <div class="mx-auto max-w-7xl">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <h2 class="{{ $homeBlockTitleClasses }}" data-reveal="title">{{ $blogPreviewTitle }}</h2>
                            @if ($blogPreviewDescription !== '')
                                <p class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">{{ $blogPreviewDescription }}</p>
                            @endif
                        </div>
                        <a href="{{ $blogPreviewCtaUrl }}" class="frontsite-text-reveal inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-secondary transition hover:text-primary" data-reveal="cta">
                            {{ $blogPreviewCtaLabel }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="mt-10 {{ $articleCardGridClasses }}">
                        @forelse ($featuredPosts as $post)
                            @include('themes.haidangtravel.partials.article-card', ['post' => $post, 'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', '')])
                        @empty
                            <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-white p-10 text-center text-sm leading-7 text-slate-500">
                                Chưa có bài viết nổi bật để hiển thị.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
        @break

    @case('faq')
        @if ($homeBlockEnabled('faq') && $homeFaqItems->isNotEmpty() && ! $homeHasPositionedBlockType(\App\Support\LandingPageBlocks::TYPE_FAQ))
            <section class="bg-white px-4 py-8 sm:px-6 lg:px-8 lg:py-10" id="home-faq">
                <div class="mx-auto max-w-7xl">
                    @include('themes.haidangtravel.partials.faq-block', [
                        'accordionId' => 'home-faq',
                        'accordionWrapperClass' => 'mt-10',
                        'headingAlign' => 'center',
                        'headingWidth' => 'max-w-3xl',
                        'items' => $homeFaqItems,
                        'title' => $homeFaqHeading['is_visible'] ? $homeFaqHeading['title'] : '',
                    ])
                </div>
            </section>
        @endif
        @break

    @case('cta')
        @if ($homeBlockEnabled('cta') && ! $homeHasPositionedBlockType(\App\Support\LandingPageBlocks::TYPE_CTA))
            @include('themes.haidangtravel.partials.cta-banner', [
                'description' => $landing?->cta_excerpt ?: 'Bạn có thể gửi yêu cầu một lần để nhận gợi ý tour, dịch vụ đi kèm và phương án phù hợp hơn với hành trình thực tế.',
                'secondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nổi bật',
                'secondaryUrl' => $landing?->cta_secondary_url ?: route('tours.domestic'),
                'title' => $landing?->cta_title ?: 'Cần tư vấn tour phù hợp với ngân sách, thời gian và quy mô đoàn?',
            ])
        @endif

        @if ($renderSlotBlocks)
            @include('themes.haidangtravel.partials.landing-content-blocks', [
                'blocks' => $homeSlot(\App\Support\LandingPageBlocks::HOME_POSITION_AFTER_CTA),
            ])
        @endif
        @break
@endswitch
