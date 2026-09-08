@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $homeConfig = is_array($homeConfig ?? null) ? $homeConfig : \App\Support\TravelHomePageConfig::prepare($landing?->home_config);
        $homeLayoutBlocks = collect($homeLayoutBlocks ?? []);
        $homeLayoutOrder = collect($homeLayoutOrder ?? \App\Support\TravelHomePageConfig::homeLayoutOrder($homeConfig, \App\Support\LandingPageBlocks::normalize($landing?->blocks ?? [])));
        $homePositionedBlockTypes = collect($homePositionedBlockTypes ?? []);
        $homeHasPositionedBlockType = static fn (string|array $types): bool => collect((array) $types)
            ->contains(fn (string $type): bool => $homePositionedBlockTypes->contains($type));
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

            return \App\Support\FrontsiteUrls::cleanInternalUrl($url, true) ?? $url;
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
        $serviceIconClass = static function ($service): string {
            $haystack = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(implode(' ', array_filter([
                $service->title ?? '',
                $service->slug ?? '',
                $service->excerpt ?? '',
                $service->category?->name ?? '',
            ]))));

            $iconRules = [
                'fa-solid fa-plane-departure' => ['ve may bay', 'may bay', 'hang khong', 'flight', 'airline'],
                'fa-solid fa-passport' => ['visa', 'passport', 'ho chieu', 'xuat nhap canh'],
                'fa-solid fa-sim-card' => ['sim', 'esim', '4g', '5g', 'ket noi', 'internet'],
                'fa-solid fa-graduation-cap' => ['du hoc', 'hoc bong', 'truong hoc', 'education', 'study'],
                'fa-solid fa-van-shuttle' => ['thue xe', 'xe du lich', 'dua don', 'limousine', 'van'],
                'fa-solid fa-hotel' => ['khach san', 'hotel', 'luu tru'],
                'fa-solid fa-shield-heart' => ['bao hiem', 'an toan'],
                'fa-solid fa-people-group' => ['tour doan', 'mice', 'team building', 'doanh nghiep', 'cong ty'],
                'fa-solid fa-route' => ['tour', 'hanh trinh', 'du lich'],
            ];

            foreach ($iconRules as $iconClass => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($haystack, $keyword)) {
                        return $iconClass;
                    }
                }
            }

            return trim((string) ($service->icon_class ?? '')) ?: 'fa-solid fa-suitcase-rolling';
        };
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
                'description' => trim((string) data_get($featuredTourTabsConfig, 'international.description', '')) ?: 'Gom các tour nước ngoài vừa được cập nhật để thuận tiện so sánh ngày đi, chi phí và hồ sơ đi kèm.',
                'id' => 'international',
                'items' => $internationalTours,
                'label' => trim((string) data_get($featuredTourTabsConfig, 'international.label', '')) ?: 'Tour nước ngoài',
                'title' => trim((string) data_get($featuredTourTabsConfig, 'international.title', '')) ?: 'Tour nước ngoài',
                'url' => route('tours.international'),
            ],
            [
                'description' => trim((string) data_get($featuredTourTabsConfig, 'domestic.description', '')) ?: 'Ưu tiên những tour trong nước được cập nhật gần đây nhất để bạn theo dõi lịch khởi hành, thời lượng và mức giá thuận tiện hơn.',
                'id' => 'domestic',
                'items' => $domesticTours,
                'label' => trim((string) data_get($featuredTourTabsConfig, 'domestic.label', '')) ?: 'Tour trong nước',
                'title' => trim((string) data_get($featuredTourTabsConfig, 'domestic.title', '')) ?: 'Tour trong nước',
                'url' => route('tours.domestic'),
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
        $defaultFeaturedTabId = (string) (collect($featuredTabs)->first(fn (array $tab) => $tab['items']->isNotEmpty())['id'] ?? 'international');
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

    @foreach ($homeLayoutOrder as $homeLayoutToken)
        @php
            $homeLayoutSectionKey = \App\Support\TravelHomePageConfig::homeLayoutSectionKey((string) $homeLayoutToken);
            $homeLayoutBlockUuid = \App\Support\TravelHomePageConfig::homeLayoutBlockUuid((string) $homeLayoutToken);
            $homeLayoutBlock = $homeLayoutBlockUuid ? $homeLayoutBlocks->get($homeLayoutBlockUuid) : null;
        @endphp

        @if ($homeLayoutSectionKey)
            @include('themes.haidangtravel.partials.home-section', [
                'renderSlotBlocks' => false,
                'sectionKey' => $homeLayoutSectionKey,
            ])
        @elseif (is_array($homeLayoutBlock))
            @include('themes.haidangtravel.partials.landing-content-blocks', [
                'blocks' => [$homeLayoutBlock],
            ])
        @endif
    @endforeach

    @include('themes.haidangtravel.partials.home-popup-slider', [
        'popup' => $homePopupSlider ?? null,
    ])
@endsection
