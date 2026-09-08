@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $fixedContextMap = collect($fixedContextCards ?? [])->keyBy('label');
        $hideListingContextPanel = (bool) ($hideListingContextPanel ?? false);
        $pageFaqItems = collect($pageFaqItems ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) data_get($item, 'question')),
                'answer' => trim((string) data_get($item, 'answer')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values();
        $pageReviewItems = collect($pageReviewItems ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values();
        $pageReviewSummary = is_array($pageReviewSummary ?? null) ? $pageReviewSummary : null;
        $heroRatingAverage = filled(data_get($pageReviewSummary, 'average_value'))
            ? number_format((float) data_get($pageReviewSummary, 'average_value'), 1, ',', '.').'/5'
            : null;
        $heroRatingCount = is_numeric(data_get($pageReviewSummary, 'count')) ? (int) data_get($pageReviewSummary, 'count') : null;
        $listingHeroMetaItems = collect();
        if (($fixedContextMap->has('Danh mục') || $fixedContextMap->has('Điểm đến')) && $heroRatingAverage) {
            $listingHeroMetaItems->push([
                'icon' => 'fa-solid fa-star',
                'label' => $heroRatingAverage.($heroRatingCount ? ' • '.number_format($heroRatingCount, 0, ',', '.').' lượt đánh giá' : ''),
            ]);
        }
        $searchPlaceholder = match (true) {
            $fixedContextMap->has('Danh mục') => 'Tìm tour trong danh mục này...',
            $fixedContextMap->has('Điểm đến') => 'Tìm tour theo điểm đến này...',
            $fixedContextMap->has('Vùng miền') => 'Tìm tour trong vùng miền này...',
            default => 'Bạn muốn đi đâu?',
        };
        $listingSectionTitle = match (true) {
            $fixedContextMap->has('Danh mục') => 'Danh sách tour thuộc '.$pageTitle,
            $fixedContextMap->has('Điểm đến') => 'Khám phá tour đến '.$pageTitle,
            $fixedContextMap->has('Vùng miền') => 'Khám phá tour theo vùng miền '.$pageTitle,
            default => 'Tổng hợp tour mới 2026',
        };
        $listingSectionDescription = match (true) {
            $fixedContextMap->has('Danh mục') => 'So sánh nhanh ngày đi, mức giá và các hành trình trong cùng danh mục trước khi mở chi tiết tour.',
            $fixedContextMap->has('Điểm đến') => 'Lọc nhanh các hành trình liên quan đến điểm đến này để chốt ngày đi và ngân sách thuận tiện hơn.',
            $fixedContextMap->has('Vùng miền') => 'Rút ngắn thời gian chọn tour bằng cách so sánh nhanh lịch khởi hành, mức giá và các điểm đến trong khu vực này.',
            default => 'Khám phá danh sách tour đa dạng được thiết kế chuyên nghiệp, tối ưu thời gian và ngân sách nhưng vẫn đảm bảo tiêu chuẩn sang trọng tuyệt đối.',
        };
        $faqSectionTitle = match (true) {
            $fixedContextMap->has('Danh mục') => 'Câu hỏi thường gặp về '.$pageTitle,
            $fixedContextMap->has('Điểm đến') => 'Câu hỏi thường gặp khi đi '.$pageTitle,
            default => 'Câu hỏi thường gặp',
        };
        $reviewSectionTitle = match (true) {
            $fixedContextMap->has('Danh mục') => 'Đánh giá nổi bật về '.$pageTitle,
            $fixedContextMap->has('Điểm đến') => 'Nhận xét nổi bật về '.$pageTitle,
            default => 'Đánh giá nổi bật',
        };
        $reviewSectionDescription = match (true) {
            $fixedContextMap->has('Danh mục') => 'Những nhận xét này giúp người xem chốt nhanh xem nhóm tour này có hợp lịch trình, ngân sách và kỳ vọng trải nghiệm hay không.',
            $fixedContextMap->has('Điểm đến') => 'Những nhận xét này giúp người xem hình dung nhanh trải nghiệm thực tế trước khi mở từng tour chi tiết đến điểm đến này.',
            default => 'Những nhận xét này được hiển thị trực tiếp trên trang để hỗ trợ quyết định trước khi gửi yêu cầu.',
        };
        $sectionHeadingConfig = data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY);
        $listingReviewsHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_listing_reviews', ['title' => $pageTitle]);
        $listingFaqHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_listing_faq', ['title' => $pageTitle]);
        $listingCtaHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_listing_cta');
        $pageContentCollapsedMobile = match (true) {
            $fixedContextMap->has('Danh mục'), $fixedContextMap->has('Điểm đến') => 300,
            default => 400,
        };
        $pageContentCollapsedDesktop = match (true) {
            $fixedContextMap->has('Danh mục'), $fixedContextMap->has('Điểm đến') => 600,
            default => 800,
        };
        $landingContentBlocks = collect($landingContentBlocks ?? [])->values();
        $landingTourListBlockIndex = $landingContentBlocks->search(fn (array $block): bool => ($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_TOUR_LIST);
        $landingCtaBlockIndex = $landingContentBlocks->search(fn (array $block): bool => ($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_CTA);
        $landingCtaBlock = $landingCtaBlockIndex !== false ? $landingContentBlocks->get((int) $landingCtaBlockIndex) : null;
        $landingHasConfiguredCtaBlock = collect($landingConfiguredBlockTypes ?? [])->contains(\App\Support\LandingPageBlocks::TYPE_CTA);
        $landingRouteListAnchorIndex = $landingTourListBlockIndex !== false
            ? (int) $landingTourListBlockIndex
            : ($landingCtaBlockIndex !== false ? (int) $landingCtaBlockIndex : $landingContentBlocks->count());
        $filterRouteControlLandingBlocks = static fn ($blocks) => collect($blocks)
            ->reject(fn (array $block): bool => in_array($block['type'] ?? null, [
                \App\Support\LandingPageBlocks::TYPE_TOUR_LIST,
                \App\Support\LandingPageBlocks::TYPE_CTA,
            ], true))
            ->values();
        $landingBlocksBeforeRouteList = $filterRouteControlLandingBlocks($landingContentBlocks->slice(0, $landingRouteListAnchorIndex));
        $landingBlocksBetweenRouteListAndCta = match (true) {
            $landingTourListBlockIndex !== false && $landingCtaBlockIndex !== false && (int) $landingCtaBlockIndex > (int) $landingTourListBlockIndex
                => $filterRouteControlLandingBlocks($landingContentBlocks->slice((int) $landingTourListBlockIndex + 1, (int) $landingCtaBlockIndex - (int) $landingTourListBlockIndex - 1)),
            $landingTourListBlockIndex !== false && $landingCtaBlockIndex === false
                => $filterRouteControlLandingBlocks($landingContentBlocks->slice((int) $landingTourListBlockIndex + 1)),
            default => collect(),
        };
        $landingBlocksAfterRouteCta = $landingCtaBlockIndex !== false
            ? $filterRouteControlLandingBlocks($landingContentBlocks->slice((int) $landingCtaBlockIndex + 1))
            : collect();
    @endphp

    @include('themes.haidangtravel.partials.landing-hero', [
        'breadcrumbItems' => $breadcrumbs,
        'fallbackDescription' => $pageDescription,
        'fallbackEyebrow' => $pageBadge,
        'fallbackPrimaryLabel' => 'Gửi yêu cầu',
        'fallbackPrimaryUrl' => route('contact'),
        'fallbackSecondaryLabel' => 'Xem dịch vụ',
        'fallbackSecondaryUrl' => route('services.index'),
        'fallbackTitle' => $pageTitle,
        'hero' => $landingHero ?? [],
        'landing' => null,
        'metaItems' => $listingHeroMetaItems->all(),
    ])

    @if (! empty($fixedContextCards))
        <section class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="frontsite-text-reveal flex flex-wrap gap-3" data-reveal="meta">
                    @foreach ($fixedContextCards as $card)
                        <a href="{{ $card['url'] }}" class="inline-flex items-center rounded-sm border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-orange-200 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            {{ $card['label'] }}: {{ $card['value'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])

    @include('themes.haidangtravel.partials.shared-search-bar', [
        'action' => $currentUrl,
        'inputValue' => $filters['q'] ?? '',
        'placeholder' => $searchPlaceholder,
        'selectFields' => $searchSelectFields ?? [],
        'sectionClasses' => 'relative z-10 -mt-6 px-4 pb-8 sm:px-6 lg:-mt-8 lg:px-8',
    ])

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingBlocksBeforeRouteList,
    ])

    <section class="px-4 pb-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @unless ($hideListingContextPanel)
                <div class="space-y-6">
                    @include('themes.haidangtravel.partials.section-heading', [
                        'title' => $listingSectionTitle,
                        'description' => $listingSectionDescription,
                        'width' => 'max-w-none',
                    ])

                    @if ($pageContent)
                        <div class="theme-panel frontsite-text-reveal overflow-hidden p-0" data-reveal="copy">
                            @include('themes.haidangtravel.partials.expandable-rich-text', [
                                'contentHtml' => \App\Support\RichText::render($pageContent),
                                'contentClass' => 'theme-copy text-sm leading-7 text-slate-600 sm:text-base sm:leading-8',
                                'containerClass' => 'space-y-4 p-6 sm:p-8',
                                'desktopCollapsedHeight' => $pageContentCollapsedDesktop,
                                'expandableId' => 'tour-listing-content-panel',
                                'mobileCollapsedHeight' => $pageContentCollapsedMobile,
                            ])
                        </div>
                    @endif
                </div>
            @endunless

            <div class="space-y-6">
                <div class="frontsite-text-reveal flex items-center justify-between gap-3" data-reveal="meta">
                    <p class="text-sm font-medium text-slate-500">Hiện có {{ $tours->total() }} tour</p>
                    <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->hotline ?: $siteSettings->phone) }}" class="inline-flex items-center gap-2 rounded-sm bg-[color:var(--color-primary-soft)] px-4 py-2 text-sm font-semibold text-primary">
                        <i class="fa-solid fa-phone-volume"></i>
                        {{ $siteSettings->hotline ?: $siteSettings->phone }}
                    </a>
                </div>

                @php
                    $tourCardCount = $tours->count();
                    $tourCardGridClasses = \App\Support\FrontsiteCardGrid::classes($tourCardCount);
                    $tourCardVariant = \App\Support\FrontsiteCardGrid::tourVariant($tourCardCount);
                @endphp

                <div class="{{ $tourCardGridClasses }}">
                    @forelse ($tours as $tour)
                        @include('themes.haidangtravel.partials.tour-card', [
                            'tour' => $tour,
                            'variant' => $tourCardVariant,
                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                        ])
                    @empty
                        <div class="theme-panel frontsite-text-reveal col-span-full p-10 text-center text-slate-500" data-reveal="panel">
                            Không tìm thấy tour phù hợp bộ lọc hiện tại.
                        </div>
                    @endforelse
                </div>

                @if (collect($pageGallery ?? [])->isNotEmpty())
                    @include('themes.haidangtravel.partials.frontsite-media-gallery', [
                        'collectionKey' => 'tour-listing-page-gallery',
                        'desktopVerticalThumbs' => (bool) ($pageGalleryDesktopVerticalThumbs ?? false),
                        'gallery' => $pageGallery,
                        'sectionId' => 'tour-listing-gallery',
                        'title' => $pageTitle,
                        'primaryMediaDescription' => $fixedContextMap->has('Quốc gia') ? $pageDescription : '',
                    ])
                @endif

                @if ($tours->hasPages())
                    <div class="theme-panel frontsite-text-reveal p-4" data-reveal="panel">
                        {{ $tours->links() }}
                    </div>
                @endif

                @if ($pageReviewItems->isNotEmpty())
                    @include('themes.haidangtravel.partials.review-grid', [
                        'description' => $listingReviewsHeading['is_visible'] && $listingReviewsHeading['description'] !== '' ? $listingReviewsHeading['description'] : ($listingReviewsHeading['is_visible'] ? $reviewSectionDescription : ''),
                        'items' => $pageReviewItems,
                        'sectionId' => 'tour-listing-reviews',
                        'summary' => $pageReviewSummary,
                        'title' => $listingReviewsHeading['is_visible'] ? ($listingReviewsHeading['title'] ?: $reviewSectionTitle) : '',
                    ])
                @endif

                @if ($pageFaqItems->isNotEmpty())
                    <section class="space-y-5">
                        @include('themes.haidangtravel.partials.faq-block', [
                            'accordionId' => 'tour-listing-faq',
                            'headingWidth' => 'max-w-none',
                            'items' => $pageFaqItems,
                            'title' => $listingFaqHeading['is_visible'] ? ($listingFaqHeading['title'] ?: $faqSectionTitle) : '',
                        ])
                    </section>
                @endif
            </div>
        </div>
    </section>

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingBlocksBetweenRouteListAndCta,
    ])

    @if ($landingCtaBlock)
        @include('themes.haidangtravel.partials.landing-content-blocks', [
            'blocks' => [$landingCtaBlock],
        ])
    @elseif (! $landingHasConfiguredCtaBlock)
        @include('themes.haidangtravel.partials.cta-banner', [
            'description' => $listingCtaHeading['is_visible'] ? $listingCtaHeading['description'] : '',
            'primaryLabel' => 'Gửi yêu cầu',
            'primaryUrl' => route('contact'),
            'secondaryLabel' => 'Xem dịch vụ',
            'secondaryUrl' => route('services.index'),
            'title' => $listingCtaHeading['is_visible'] ? $listingCtaHeading['title'] : '',
        ])
    @endif

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingBlocksAfterRouteCta,
    ])

    @if (collect($pageGallery ?? [])->isNotEmpty())
        @include('themes.haidangtravel.partials.frontsite-gallery-lightbox')
    @endif
@endsection
