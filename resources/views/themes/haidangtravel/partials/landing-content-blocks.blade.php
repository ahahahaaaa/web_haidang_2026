@php
    $blocks = collect($blocks ?? []);
    $onlyTypes = collect($onlyTypes ?? [])->filter()->values();
    $skipTypes = collect($skipTypes ?? [])->filter()->values();
    $landingPage = $landing ?? null;
    $voucherCampaign = $voucherCampaign ?? null;
    $voucherCampaignSlug = $voucherCampaignSlug ?? null;
@endphp

@foreach ($blocks as $blockIndex => $block)
    @continue($onlyTypes->isNotEmpty() && ! $onlyTypes->contains($block['type'] ?? null))
    @continue($skipTypes->contains($block['type'] ?? null))

    @if (in_array($block['type'] ?? null, [\App\Support\LandingPageBlocks::TYPE_HERO_SLIDER, \App\Support\LandingPageBlocks::TYPE_HERO_MEDIA], true))
        @include('themes.haidangtravel.partials.landing-hero', [
            'breadcrumbItems' => [
                ['label' => 'Trang chủ', 'url' => route('home')],
                ['label' => $landingPage?->title ?: ($block['title'] ?? 'Landing page')],
            ],
            'fallbackDescription' => trim((string) ($block['description'] ?? '')) ?: ($landingPage?->hero_excerpt ?: $landingPage?->intro_excerpt),
            'fallbackEyebrow' => trim((string) ($block['eyebrow'] ?? '')) ?: ($landingPage?->hero_badge ?: $landingPage?->title),
            'fallbackPrimaryLabel' => trim((string) ($block['primary_label'] ?? '')) ?: ($landingPage?->cta_primary_label ?: 'Gửi yêu cầu'),
            'fallbackPrimaryUrl' => trim((string) ($block['primary_url'] ?? '')) ?: ($landingPage?->cta_primary_url ?: route('contact')),
            'fallbackSecondaryLabel' => trim((string) ($block['secondary_label'] ?? '')) ?: $landingPage?->cta_secondary_label,
            'fallbackSecondaryUrl' => trim((string) ($block['secondary_url'] ?? '')) ?: $landingPage?->cta_secondary_url,
            'fallbackTitle' => trim((string) ($block['title'] ?? '')) ?: ($landingPage?->hero_title ?: $landingPage?->title),
            'hero' => $block['rendered_hero'] ?? [],
            'landing' => $landingPage,
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE)
        @include('themes.haidangtravel.partials.landing-hero-demo', [
            'block' => $block,
            'heroTour' => $block['hero_tour'] ?? null,
            'landing' => $landingPage,
            'scopeCards' => $block['scope_cards'] ?? [],
            'sectionId' => 'landing-hero-demo-'.$blockIndex,
        ])
    @endif

    @if (in_array($block['type'] ?? null, [\App\Support\LandingPageBlocks::TYPE_GALLERY_SLIDER, \App\Support\LandingPageBlocks::TYPE_GALLERY_MEDIA], true))
        @include('themes.haidangtravel.partials.landing-gallery', [
            'gallery' => $block['rendered_gallery'] ?? [],
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_RICH_TEXT)
        <section class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="theme-panel p-6 sm:p-8">
                    @if (filled($block['eyebrow'] ?? null) || filled($block['title'] ?? null) || filled($block['excerpt'] ?? null))
                        @include('themes.haidangtravel.partials.section-heading', [
                            'eyebrow' => $block['eyebrow'] ?? null,
                            'showEyebrow' => true,
                            'title' => $block['title'] ?? null,
                            'description' => $block['excerpt'] ?? null,
                        ])
                    @endif

                    @if (filled($block['rendered_body'] ?? null))
                        <div class="theme-copy mt-6 text-sm leading-7 text-slate-600">
                            {!! $block['rendered_body'] !!}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_HTML_WIDGET && filled($block['rendered_html'] ?? null))
        {!! $block['rendered_html'] !!}
    @endif

    @if (in_array($block['type'] ?? null, [
        \App\Support\LandingPageBlocks::TYPE_VOUCHER_PROMOTION,
        \App\Support\LandingPageBlocks::TYPE_VOUCHER_PROMOTION_PREMIUM,
    ], true))
        @include('themes.haidangtravel.partials.voucher-promotion-block', [
            'block' => $block,
            'landing' => $landingPage,
            'voucherCampaign' => $voucherCampaign,
            'voucherCampaignSlug' => $voucherCampaignSlug,
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_GEO_ANSWER)
        @include('themes.haidangtravel.partials.geo-answer-panel', [
            'geo' => $block['geo'] ?? [],
            'sectionClasses' => 'px-4 py-8 sm:px-6 lg:px-8',
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_TRUST_PROOF)
        @include('themes.haidangtravel.partials.trust-proof-section', [
            'cards' => $block['cards'] ?? [],
            'description' => $block['description'] ?? null,
            'sectionClasses' => 'overflow-hidden bg-[linear-gradient(180deg,#fffaf5_0%,#eef5ff_100%)] px-4 py-8 sm:px-6 lg:px-8',
            'sectionId' => 'landing-trust-proof-'.$blockIndex,
            'stats' => $block['stats'] ?? [],
            'title' => $block['title'] ?? 'Lý do khách chọn Hải Đăng Travel',
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_REGION_RAIL)
        @include('themes.haidangtravel.partials.taxonomy-card-carousel', [
            'cardCtaLabel' => $block['card_cta_label'] ?? null,
            'description' => $block['description'] ?? null,
            'items' => $block['items'] ?? [],
            'sectionId' => 'landing-region-rail-'.$blockIndex,
            'taxonomyType' => 'region',
            'title' => $block['title'] ?? null,
            'visualStyle' => 'destination',
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS)
        @include('themes.haidangtravel.partials.region-taxonomy-tabs', [
            'block' => $block,
            'imageSize' => \App\Support\FrontsiteMedia::SIZE_MEDIUM,
            'sectionId' => 'landing-region-taxonomy-tabs-'.$blockIndex,
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_TOPIC_RAIL)
        @include('themes.haidangtravel.partials.taxonomy-card-carousel', [
            'description' => $block['description'] ?? null,
            'eyebrow' => $block['eyebrow'] ?? null,
            'items' => $block['items'] ?? [],
            'sectionId' => 'landing-topic-rail-'.$blockIndex,
            'showNavigator' => (bool) ($block['show_navigation'] ?? true),
            'taxonomyType' => 'tour_category',
            'title' => $block['title'] ?? null,
            'useDefaultCopy' => false,
            'visualStyle' => 'topic',
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS)
        @include('themes.haidangtravel.partials.tour-taxonomy-tabs', [
            'block' => $block,
            'sectionId' => 'landing-tour-taxonomy-tabs-'.$blockIndex,
            'showRatings' => true,
        ])
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_TOUR_LIST)
        <section class="px-4 py-8 sm:px-6 lg:px-8">
            @php
                $tourBlockItems = collect($block['items'] ?? collect());
                $tourBlockCount = $tourBlockItems->count();
                $tourCardGridClasses = \App\Support\FrontsiteCardGrid::classes($tourBlockCount);
                $tourCardVariant = \App\Support\FrontsiteCardGrid::tourVariant($tourBlockCount);
            @endphp

            <div class="mx-auto max-w-7xl space-y-8">
                @include('themes.haidangtravel.partials.section-heading', [
                    'eyebrow' => $block['eyebrow'] ?? 'Tours',
                    'title' => $block['title'] ?? 'Danh sách tour',
                    'description' => $block['description'] ?? null,
                ])

                <div class="{{ $tourCardGridClasses }}">
                    @forelse ($tourBlockItems as $tour)
                        @include('themes.haidangtravel.partials.tour-card', [
                            'tour' => $tour,
                            'variant' => $tourCardVariant,
                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                            'showRating' => true,
                        ])
                    @empty
                        <div class="theme-panel col-span-full p-8 text-center text-slate-500">Chưa có tour phù hợp điều kiện block hiện tại.</div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_BLOG_LIST)
        <section class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-8">
                @include('themes.haidangtravel.partials.section-heading', [
                    'eyebrow' => $block['eyebrow'] ?? 'Blog',
                    'title' => $block['title'] ?? 'Bài viết nổi bật',
                    'description' => $block['description'] ?? null,
                ])

                <div class="{{ \App\Support\FrontsiteCardGrid::classes() }}">
                    @forelse (($block['items'] ?? collect()) as $post)
                        @include('themes.haidangtravel.partials.article-card', ['post' => $post, 'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', '')])
                    @empty
                        <div class="theme-panel col-span-full p-8 text-center text-slate-500">Chưa có bài viết phù hợp điều kiện block hiện tại.</div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_FAQ)
        <section class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-5xl space-y-8">
                @include('themes.haidangtravel.partials.faq-block', [
                    'accordionId' => 'landing-faq-'.$blockIndex,
                    'items' => $block['items'] ?? [],
                    'title' => $block['title'] ?? 'Câu hỏi thường gặp',
                ])
            </div>
        </section>
    @endif

    @php
        $isVoucherLandingFinalCta = $landingPage?->slug === 'voucher-du-lich'
            && ($block['uuid'] ?? '') === 'voucher-final-cta';
    @endphp

    @if (($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_CTA && ! $isVoucherLandingFinalCta)
        @include('themes.haidangtravel.partials.cta-banner', [
            'description' => $block['description'] ?? null,
            'primaryLabel' => $block['primary_label'] ?? 'Gửi yêu cầu',
            'primaryUrl' => $block['primary_url'] ?? route('contact'),
            'secondaryLabel' => $block['secondary_label'] ?? null,
            'secondaryUrl' => $block['secondary_url'] ?? null,
            'title' => $block['title'] ?? 'Cần Hải Đăng Travel tư vấn thêm?',
            'voucherCampaignSlug' => $voucherCampaignSlug,
        ])
    @endif
@endforeach
