@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $homeConfig = is_array($homeConfig ?? null) ? $homeConfig : \App\Support\TravelHomePageConfig::prepare($landing?->home_config);
        $heroTour = $featuredTours->first();
        $heroCoverMedia = $heroTour
            ? \App\Support\FrontsiteMedia::responsiveUrls($heroTour, 'cover', 'cover_image_url')
            : [
                \App\Support\FrontsiteMedia::SIZE_SMALL => null,
                \App\Support\FrontsiteMedia::SIZE_MEDIUM => null,
                \App\Support\FrontsiteMedia::SIZE_FULL => null,
            ];
        $heroCover = $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? null;
        $heroCoverMedium = $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $heroCover;
        $phone = trim((string) ($siteSettings->hotline ?: $siteSettings->phone));
        $phoneLink = $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact');
        $email = trim((string) $siteSettings->primary_email);
        $normalizeUrl = static function (?string $url, string $fallback): string {
            $url = trim((string) $url);

            if ($url === '') {
                return $fallback;
            }

            foreach (['http://', 'https://', 'mailto:', 'tel:', '#'] as $prefix) {
                if (str_starts_with($url, $prefix)) {
                    return $url;
                }
            }

            return url($url);
        };
        $homeFaqItems = collect($homeFaqItems ?? []);
        $homeBlockVisibility = is_array($homeBlockVisibility ?? null) ? $homeBlockVisibility : [];
        $homeBlockEnabled = static fn (string $key): bool => (bool) ($homeBlockVisibility[$key] ?? true);
        $homeFaqHeading = \App\Support\FrontsiteSectionHeadings::resolve(
            data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY),
            'home_faq',
        );

        $stats = [
            ['value' => $siteSettings->experience_years ? $siteSettings->experience_years.'+' : '19+', 'label' => 'Năm kinh nghiệm', 'icon' => 'fa-solid fa-award'],
            ['value' => $siteSettings->completed_projects_count ? number_format($siteSettings->completed_projects_count, 0, ',', '.') : '1.500+', 'label' => 'Lượt khách & chương trình', 'icon' => 'fa-solid fa-users'],
            ['value' => $siteSettings->team_size ? $siteSettings->team_size.'+' : '60+', 'label' => 'Nhân sự hỗ trợ', 'icon' => 'fa-solid fa-user-tie'],
            ['value' => $siteSettings->quality_badge_label ?: 'GP LHQT', 'label' => 'Pháp lý & năng lực', 'icon' => 'fa-solid fa-file-shield'],
        ];

        $scopeCards = [
            [
                'eyebrow' => 'Tour trong nước',
                'title' => 'Chọn nhanh hành trình biển, núi và cao nguyên khởi hành thuận tiện',
                'description' => 'Phù hợp với nhu cầu đi ngắn ngày, gia đình, nhóm bạn hoặc khách muốn chốt lịch sớm từ TP.HCM.',
                'url' => route('tours.domestic'),
                'label' => 'Xem tour trong nước',
                'count' => $domesticTours->count(),
            ],
            [
                'eyebrow' => 'Tour nước ngoài',
                'title' => 'Khám phá châu Á và các tuyến quốc tế có hỗ trợ hồ sơ đi kèm',
                'description' => 'Dễ theo dõi giá, lịch khởi hành và các dịch vụ hỗ trợ như visa, vé máy bay hay SIM du lịch.',
                'url' => route('tours.international'),
                'label' => 'Xem tour nước ngoài',
                'count' => $internationalTours->count(),
            ],
            [
                'eyebrow' => 'Tour đoàn',
                'title' => 'Thiết kế riêng cho doanh nghiệp, MICE, gia đình lớn và chương trình theo brief',
                'description' => 'Ưu tiên nhận lead sớm để đề xuất lịch trình, quy mô tổ chức và các dịch vụ hỗ trợ phù hợp.',
                'url' => route('tours.group'),
                'label' => 'Xem tour đoàn',
                'count' => $groupTours->count(),
            ],
        ];

        $searchConfig = data_get($homeConfig, 'search', []);
        $topicRailConfig = data_get($homeConfig, 'topic_rail', []);
        $featuredToursConfig = data_get($homeConfig, 'featured_tours', []);
        $featuredTourTabsConfig = data_get($featuredToursConfig, 'tabs', []);
        $featuredTourCtaLabel = trim((string) data_get($featuredToursConfig, 'cta_label', '')) ?: 'Xem danh sách tour';
        $destinationSliderConfig = data_get($homeConfig, 'destination_slider', []);
        $servicesConfig = data_get($homeConfig, 'services', []);
        $processConfig = data_get($homeConfig, 'process', []);
        $blogPreviewConfig = data_get($homeConfig, 'blog_preview', []);
        $trustConfig = data_get($homeConfig, 'trust', []);
        $homeTrustTitle = trim((string) data_get($trustConfig, 'title', '')) ?: 'Hải Đăng Travel phù hợp khi bạn cần chốt rõ và nhanh';
        $homeTrustDescription = trim((string) data_get($trustConfig, 'description', ''));
        $homeTrustCards = collect(data_get($trustConfig, 'cards', []))
            ->filter(fn ($card) => is_array($card))
            ->map(fn (array $card) => [
                'icon' => trim((string) ($card['icon'] ?? '')),
                'highlight' => trim((string) ($card['highlight'] ?? '')),
                'title' => trim((string) ($card['title'] ?? '')),
                'text' => trim((string) ($card['text'] ?? '')),
            ])
            ->filter(fn (array $card) => $card['title'] !== '' && $card['text'] !== '')
            ->values();
        $servicesTitle = trim((string) data_get($servicesConfig, 'title', '')) ?: 'Dịch vụ hỗ trợ';
        $servicesDescription = trim((string) data_get($servicesConfig, 'description', ''));
        $servicesCtaLabel = trim((string) data_get($servicesConfig, 'cta_label', '')) ?: 'Xem tất cả dịch vụ';
        $servicesCtaUrl = $normalizeUrl(data_get($servicesConfig, 'cta_url'), route('services.index'));
        $processTitle = trim((string) data_get($processConfig, 'title', '')) ?: 'Quy trình tư vấn';
        $processDescription = trim((string) data_get($processConfig, 'description', ''));
        $processCards = collect(data_get($processConfig, 'cards', []))
            ->filter(fn ($card) => is_array($card))
            ->map(fn (array $card) => [
                'title' => trim((string) ($card['title'] ?? '')),
                'description' => trim((string) ($card['description'] ?? '')),
                'image_url' => trim((string) ($card['image_url'] ?? '')),
                'image_alt' => trim((string) ($card['image_alt'] ?? '')),
            ])
            ->filter(fn (array $card) => $card['title'] !== '' && $card['description'] !== '')
            ->values();
        $blogPreviewTitle = trim((string) data_get($blogPreviewConfig, 'title', '')) ?: 'Cẩm nang du lịch';
        $blogPreviewDescription = trim((string) data_get($blogPreviewConfig, 'description', ''));
        $blogPreviewCtaLabel = trim((string) data_get($blogPreviewConfig, 'cta_label', '')) ?: 'Xem tất cả bài viết';
        $blogPreviewCtaUrl = $normalizeUrl(data_get($blogPreviewConfig, 'cta_url'), route('blog.index'));
        $articleCardGridClasses = \App\Support\FrontsiteCardGrid::classes();

        $featuredTabs = [
            [
                'description' => trim((string) data_get($featuredTourTabsConfig, 'domestic.description', '')) ?: 'Ưu tiên những tour trong nước được cập nhật gần đây nhất để bạn theo dõi lịch khởi hành, thời lượng và mức giá thuận tiện hơn.',
                'id' => 'domestic',
                'items' => $domesticTours,
                'label' => trim((string) data_get($featuredTourTabsConfig, 'domestic.label', '')) ?: 'Tour trong nước',
                'title' => trim((string) data_get($featuredTourTabsConfig, 'domestic.title', '')) ?: 'Tour trong nước',
                'url' => route('tours.domestic'),
            ],
            [
                'description' => trim((string) data_get($featuredTourTabsConfig, 'international.description', '')) ?: 'Gom các tour nước ngoài vừa được cập nhật để thuận tiện so sánh ngày đi, chi phí và hồ sơ đi kèm.',
                'id' => 'international',
                'items' => $internationalTours,
                'label' => trim((string) data_get($featuredTourTabsConfig, 'international.label', '')) ?: 'Tour nước ngoài',
                'title' => trim((string) data_get($featuredTourTabsConfig, 'international.title', '')) ?: 'Tour nước ngoài',
                'url' => route('tours.international'),
            ],
            [
                'description' => trim((string) data_get($featuredTourTabsConfig, 'group.description', '')) ?: 'Dành cho tour đoàn, MICE và nhu cầu thiết kế chương trình riêng với danh sách ưu tiên theo lần cập nhật mới nhất.',
                'id' => 'group',
                'items' => $groupTours,
                'label' => trim((string) data_get($featuredTourTabsConfig, 'group.label', '')) ?: 'Tour đoàn',
                'title' => trim((string) data_get($featuredTourTabsConfig, 'group.title', '')) ?: 'Tour đoàn',
                'url' => route('tours.group'),
            ],
        ];
        $defaultFeaturedTabId = (string) (collect($featuredTabs)->first(fn (array $tab) => $tab['items']->isNotEmpty())['id'] ?? 'domestic');
        $activeFeaturedTab = collect($featuredTabs)->firstWhere('id', $defaultFeaturedTabId) ?? $featuredTabs[0];
        $homeHeroDemoBlock = is_array($homeHeroDemoBlock ?? null)
            ? $homeHeroDemoBlock
            : collect(\App\Support\LandingPageBlocks::normalize($landing?->blocks ?? []))
                ->first(fn (array $block) => ($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE
                    && (bool) ($block['is_enabled'] ?? true));
        $customHomeHero = ! is_array($homeHeroDemoBlock)
            && ((($landingHero['mode'] ?? 'default') !== 'default') || ! empty($landingHero['slides'] ?? []));
        $homeBlockTitleClasses = 'frontsite-text-reveal frontsite-h2';
        $homeBlockTitleCenterClasses = $homeBlockTitleClasses.' mx-auto text-center';
    @endphp

    @if (is_array($homeHeroDemoBlock))
        @include('themes.haidangtravel.partials.landing-hero-demo', [
            'block' => $homeHeroDemoBlock,
            'email' => $email,
            'heroCover' => $heroCover,
            'heroCoverMedia' => $heroCoverMedia,
            'heroCoverMedium' => $heroCoverMedium,
            'heroTour' => $heroTour,
            'landing' => $landing,
            'phone' => $phone,
            'phoneLink' => $phoneLink,
            'scopeCards' => $scopeCards,
            'sectionId' => 'home-hero',
        ])
    @elseif ($customHomeHero)
        @include('themes.haidangtravel.partials.landing-hero', [
            'fallbackDescription' => $landing?->hero_excerpt ?: 'Khám phá nhanh các nhóm tour nổi bật, điểm đến đang được quan tâm và dịch vụ hỗ trợ đi kèm. Khi cần, bạn có thể gửi một yêu cầu duy nhất để được tư vấn theo ngân sách, ngày đi và quy mô đoàn.',
            'fallbackEyebrow' => $landing?->hero_badge ?: 'Du lịch Hải Đăng Travel',
            'fallbackPrimaryLabel' => $landing?->cta_primary_label ?: 'Gửi yêu cầu tư vấn',
            'fallbackPrimaryUrl' => $landing?->cta_primary_url ?: route('contact'),
            'fallbackSecondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nổi bật',
            'fallbackSecondaryUrl' => $landing?->cta_secondary_url ?: route('tours.domestic'),
            'fallbackTitle' => $landing?->hero_title ?: 'Du lịch hè 2026 cùng Haidangtravel',
            'hero' => $landingHero ?? [],
            'landing' => $landing,
        ])
    @else
        @include('themes.haidangtravel.partials.landing-hero-demo', [
            'email' => $email,
            'heroCover' => $heroCover,
            'heroCoverMedia' => $heroCoverMedia,
            'heroCoverMedium' => $heroCoverMedium,
            'heroTour' => $heroTour,
            'landing' => $landing,
            'phone' => $phone,
            'phoneLink' => $phoneLink,
            'scopeCards' => $scopeCards,
            'sectionId' => 'home-hero',
        ])
    @endif

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

    @if ($homeBlockEnabled('topic_rail'))
        @include('themes.haidangtravel.partials.home-tour-topics', [
            'description' => trim((string) data_get($topicRailConfig, 'description', '')),
            'title' => trim((string) data_get($topicRailConfig, 'title', '')),
            'tourCategories' => $homeTourCategories ?? collect(),
        ])
    @endif

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

    @includeWhen($homeTourTaxonomyTabsBlock, 'themes.haidangtravel.partials.tour-taxonomy-tabs', [
        'block' => $homeTourTaxonomyTabsBlock,
        'sectionId' => 'home-tour-taxonomy-tabs',
        'tabsLabel' => 'Tour theo vùng, điểm đến và chủ đề',
    ])

    @includeWhen($homeRegionTaxonomyTabsBlock, 'themes.haidangtravel.partials.region-taxonomy-tabs', [
        'block' => $homeRegionTaxonomyTabsBlock,
        'imageSize' => \App\Support\FrontsiteMedia::SIZE_MEDIUM,
        'sectionId' => 'home-region-taxonomy-tabs',
        'tabsLabel' => 'Vùng miền nổi bật',
    ])

    @if ($homeBlockEnabled('destination_slider'))
        @include('themes.haidangtravel.partials.home-destination-slider', [
            'cardCtaLabel' => trim((string) data_get($destinationSliderConfig, 'card_cta_label', '')) ?: 'Xem hub điểm đến',
            'description' => trim((string) data_get($destinationSliderConfig, 'description', '')) ?: 'Lướt nhanh các hub điểm đến đang có tour hoạt động để chọn hướng đi phù hợp trước khi xem sâu hơn ở phần Điểm đến yêu thích.',
            'destinations' => $homeDestinations ?? collect(),
            'title' => trim((string) data_get($destinationSliderConfig, 'title', '')) ?: 'Điểm đến nổi bật',
        ])
    @endif

    @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingHtmlWidgetBlocks ?? [],
    ])

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
                    <article class="frontsite-text-reveal flex h-full flex-col rounded-[1.75rem] border border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fff9f3_100%)] p-7 shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition hover:-translate-y-1 hover:border-orange-200" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}">
                        <div class="text-primary">
                            <i class="{{ $service->icon_class ?: 'fa-solid fa-suitcase-rolling' }} text-4xl"></i>
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
                                <img src="{{ $card['image_url'] }}" alt="{{ $card['image_alt'] !== '' ? $card['image_alt'] : $card['title'] }}" class="h-full w-full object-cover">
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

    @if ($homeBlockEnabled('faq') && $homeFaqItems->isNotEmpty())
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

    @if ($homeBlockEnabled('cta'))
        @include('themes.haidangtravel.partials.cta-banner', [
            'description' => $landing?->cta_excerpt ?: 'Bạn có thể gửi yêu cầu một lần để nhận gợi ý tour, dịch vụ đi kèm và phương án phù hợp hơn với hành trình thực tế.',
            'secondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nổi bật',
            'secondaryUrl' => $landing?->cta_secondary_url ?: route('tours.domestic'),
            'title' => $landing?->cta_title ?: 'Cần tư vấn tour phù hợp với ngân sách, thời gian và quy mô đoàn?',
        ])
    @endif
@endsection
