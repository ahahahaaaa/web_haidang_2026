@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $coverMedia = \App\Support\FrontsiteMedia::responsiveUrls($tour, 'cover', 'cover_image_url');
        $cover = $coverMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? null;
        $coverMedium = $coverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $cover;
        $phone = trim((string) ($tour->contact_phone ?: ($siteSettings->hotline ?: $siteSettings->phone)));
        $phoneLink = $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact');
        $zaloLogoPath = asset('images/zalo-footer-logo.svg');
        $zaloUrl = trim((string) $siteSettings->zalo_url);
        $tourGallery = collect(\App\Support\FrontsiteGalleryData::tour($tour));
        $today = \Illuminate\Support\Carbon::today();
        $departureDateRank = static function ($departure) use ($today): array {
            $date = $departure->departure_date;

            if (! $date) {
                return [2, PHP_INT_MAX];
            }

            $resolvedDate = $date instanceof \Carbon\CarbonInterface
                ? $date->copy()->startOfDay()
                : \Illuminate\Support\Carbon::parse($date)->startOfDay();

            if ($resolvedDate->greaterThanOrEqualTo($today)) {
                return [0, $resolvedDate->timestamp];
            }

            return [1, -$resolvedDate->timestamp];
        };
        $compareDepartureRank = static function (array $left, array $right): int {
            foreach ([0, 1] as $index) {
                if ($left[$index] === $right[$index]) {
                    continue;
                }

                return $left[$index] <=> $right[$index];
            }

            return 0;
        };
        $departures = $tour->departures
            ->filter(fn ($departure) => in_array((string) $departure->status, ['scheduled', 'published'], true))
            ->sort(function ($left, $right) use ($departureDateRank, $compareDepartureRank): int {
                $rankComparison = $compareDepartureRank($departureDateRank($left), $departureDateRank($right));

                if ($rankComparison !== 0) {
                    return $rankComparison;
                }

                return ((int) $left->sort_order <=> (int) $right->sort_order)
                    ?: ((int) $left->getKey() <=> (int) $right->getKey());
            })
            ->values();
        $itineraryItems = collect($tour->itinerary ?? [])
            ->map(fn ($item) => [
                'title' => trim((string) data_get($item, 'title')),
                'content' => trim((string) data_get($item, 'content')),
            ])
            ->filter(fn (array $item) => $item['title'] !== '' || $item['content'] !== '')
            ->values();
        $pricingTableItems = collect($tour->pricing_table ?? [])
            ->map(fn ($item) => [
                'label' => trim((string) data_get($item, 'label')),
                'price' => trim((string) data_get($item, 'price')),
            ])
            ->filter(fn (array $item) => $item['label'] !== '' || $item['price'] !== '')
            ->values();
        $inclusionItems = collect($tour->inclusions ?? [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $faqItems = collect($tour->faq_items ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) data_get($item, 'question')),
                'answer' => trim((string) data_get($item, 'answer')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values();
        $reviewItems = collect($reviewItems ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values();
        $reviewSummary = is_array($reviewSummary ?? null) ? $reviewSummary : null;
        $rawTourTermsTitle = trim((string) $siteSettings->tour_terms_title);
        $normalizedTourTermsTitle = \Illuminate\Support\Str::of($rawTourTermsTitle)->lower()->ascii()->squish()->value();
        $shouldUseCompactTourTermsTitle = $rawTourTermsTitle === ''
            || in_array($normalizedTourTermsTitle, ['quy dinh dieu khoan tour', 'qui dinh dieu khoan tour'], true);
        $tourTermsTitle = $shouldUseCompactTourTermsTitle ? 'ĐIỀU KHOẢN TOUR' : $rawTourTermsTitle;
        $tourTermsItems = collect($tour->tour_terms_items ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) data_get($item, 'question')),
                'answer' => trim((string) data_get($item, 'answer')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values();

        if ($tourTermsItems->isEmpty()) {
            $tourTermsItems = collect($siteSettings->tour_terms_items ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) data_get($item, 'title')),
                'answer' => trim((string) data_get($item, 'content')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values();
        }

        if ($tourTermsItems->isEmpty() && filled($siteSettings->tour_terms_content)) {
            $tourTermsItems = collect([[
                'question' => $tourTermsTitle,
                'answer' => trim((string) $siteSettings->tour_terms_content),
            ]]);
        }
        $tourDescription = trim((string) ($tour->excerpt ?: strip_tags((string) $tour->content)));
        $tourHeadingDescription = $tourDescription !== ''
            ? \Illuminate\Support\Str::limit($tourDescription, 220)
            : '';
        $renderedTourContent = filled($tour->content)
            ? \App\Support\RichText::render($tour->content)
            : null;
        $tourDetailIntro = trim((string) $tour->excerpt);
        $hasTourDetails = $tourDetailIntro !== '' || filled($renderedTourContent);
        $formatPrice = static function ($value): string {
            return filled($value) ? number_format((int) $value, 0, ',', '.').' đ' : 'Liên hệ';
        };
        $formatSupplementalPrice = static function ($value) use ($formatPrice): string {
            $resolved = trim((string) $value);

            if ($resolved === '') {
                return 'Liên hệ';
            }

            return preg_match('/^\d+$/', $resolved) === 1
                ? $formatPrice((int) $resolved)
                : $resolved;
        };
        $displayDepartureLocation = trim((string) ($tour->departure_location ?: data_get($departures->first(), 'departure_location', '')));
        $displayTransport = trim((string) ($tour->transport ?: data_get($departures->first(), 'transport_label', '')));
        $displayStandard = trim((string) ($tour->standard_label ?: data_get($departures->first(), 'standard_label', '')));
        $durationLabel = filled($tour->duration_days) || filled($tour->duration_nights)
            ? trim(collect([
                filled($tour->duration_days) ? $tour->duration_days.' ngày' : null,
                filled($tour->duration_nights) ? $tour->duration_nights.' đêm' : null,
            ])->filter()->implode(' '))
            : '';
        $hasPricingSection = $pricingTableItems->isNotEmpty();
        $ratingAverage = filled(data_get($reviewSummary, 'average_value'))
            ? number_format((float) data_get($reviewSummary, 'average_value'), 1, ',', '.')
            : null;
        $ratingCount = is_numeric(data_get($reviewSummary, 'count')) ? (int) data_get($reviewSummary, 'count') : null;
        $departureSalePriceMin = $departures
            ->map(fn ($departure) => $departure->sale_price ?: $departure->base_price)
            ->filter()
            ->min();
        $departureBasePriceMin = $departures
            ->map(fn ($departure) => $departure->base_price)
            ->filter()
            ->min();
        $startingPriceValue = collect([$tour->sale_price, $tour->base_price, $departureSalePriceMin])
            ->first(fn ($value) => filled($value));
        $startingBasePriceValue = collect([$tour->base_price, $departureBasePriceMin])
            ->first(fn ($value) => filled($value));
        $nextDeparture = $departures->first();
        $nextDepartureDateLabel = $nextDeparture?->departure_date?->format('d-m-Y');
        $nearestDepartureTabLabel = $nextDeparture?->departure_date?->format('d/m');
        $compactDurationLabel = filled($tour->duration_days) || filled($tour->duration_nights)
            ? trim(collect([
                filled($tour->duration_days) ? $tour->duration_days.'N' : null,
                filled($tour->duration_nights) ? $tour->duration_nights.'Đ' : null,
            ])->filter()->implode(''))
            : '';
        $contactShortcutHref = $phone !== '' ? $phoneLink : route('contact');
        $contactShortcutIcon = $phone !== '' ? 'fa-solid fa-phone-volume' : 'fa-regular fa-message';
        $contactShortcutLabel = $phone !== '' ? 'Gọi' : 'Liên hệ';
        $miniContactAction = $zaloUrl !== ''
            ? [
                'href' => $zaloUrl,
                'label' => 'Zalo',
            ]
            : null;
        $monthLabel = static function ($date): string {
            if (! $date) {
                return 'Lịch khởi hành khác';
            }

            $resolved = $date instanceof \Carbon\CarbonInterface
                ? $date
                : \Illuminate\Support\Carbon::parse($date);

            return 'Tháng '.$resolved->format('m/Y');
        };
        $departureGroups = $departures
            ->groupBy(fn ($departure) => $departure->departure_date?->format('Y-m') ?: 'other')
            ->map(function ($items, string $key) use ($monthLabel) {
                $firstDate = $items->first()?->departure_date;

                return [
                    'id' => $key !== 'other' ? 'month-'.$key : 'other',
                    'anchor' => $key !== 'other' ? 'departure-month-'.$key : 'departure-month-other',
                    'label' => $key !== 'other' && $firstDate ? $monthLabel($firstDate) : 'Lịch khởi hành khác',
                    'count' => $items->count(),
                    'items' => $items->values(),
                ];
            })
            ->values();
        $activeDepartureGroupId = data_get($departureGroups->first(), 'id');
        $statusMeta = static function (?string $status, bool $featured = false): array {
            $normalized = trim((string) $status);

            return match ($normalized) {
                'published' => [
                    'label' => $featured ? 'Nổi bật' : 'Mở bán',
                    'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                ],
                'scheduled' => [
                    'label' => $featured ? 'Nổi bật' : 'Còn chỗ',
                    'class' => 'border-orange-200 bg-orange-50 text-primary',
                ],
                'full' => [
                    'label' => 'Hết chỗ',
                    'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                ],
                'closed' => [
                    'label' => 'Tạm khóa',
                    'class' => 'border-slate-200 bg-slate-100 text-slate-700',
                ],
                default => [
                    'label' => $normalized !== '' ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $normalized)) : 'Liên hệ',
                    'class' => 'border-slate-200 bg-slate-100 text-slate-700',
                ],
            };
        };
        $heroFacts = collect([
            ['label' => 'Khởi hành', 'value' => $displayDepartureLocation, 'icon' => 'fa-solid fa-location-arrow'],
            ['label' => 'Thời lượng', 'value' => $durationLabel, 'icon' => 'fa-regular fa-clock'],
            ['label' => 'Phương tiện', 'value' => $displayTransport, 'icon' => 'fa-solid fa-bus-simple'],
            ['label' => 'Tiêu chuẩn', 'value' => $displayStandard ?: 'Liên hệ', 'icon' => 'fa-solid fa-hotel'],
        ])->filter(fn (array $item) => filled($item['value']))->values();
        $sidebarInfoItems = collect([
            $displayDepartureLocation !== '' ? ['icon' => 'fa-solid fa-location-arrow', 'label' => 'Khởi hành', 'value' => $displayDepartureLocation] : null,
            $nextDepartureDateLabel ? ['icon' => 'fa-regular fa-calendar-days', 'label' => 'Ngày khởi hành', 'value' => $nextDepartureDateLabel] : null,
            $compactDurationLabel !== '' ? ['icon' => 'fa-regular fa-clock', 'label' => 'Thời gian', 'value' => $compactDurationLabel] : null,
            $displayTransport !== '' ? ['icon' => 'fa-solid fa-route', 'label' => 'Di chuyển', 'value' => $displayTransport] : null,
            $displayStandard !== '' ? ['icon' => 'fa-solid fa-shield-heart', 'label' => 'Tiêu chuẩn', 'value' => $displayStandard] : null,
        ])->filter()->values();
        $sectionLinks = collect([
            $tourGallery->isNotEmpty() ? ['label' => 'Thư viện', 'href' => '#tour-gallery'] : null,
            $hasTourDetails ? ['label' => 'Chi tiết tour', 'href' => '#tour-details'] : null,
            $departureGroups->isNotEmpty() ? ['label' => 'Lịch khởi hành', 'href' => '#tour-departures'] : null,
            $hasPricingSection ? ['label' => 'Phụ thu & ghi chú', 'href' => '#tour-pricing'] : null,
            $itineraryItems->isNotEmpty() ? ['label' => 'Lịch trình', 'href' => '#tour-itinerary'] : null,
            $inclusionItems->isNotEmpty() ? ['label' => 'Quyền lợi', 'href' => '#tour-inclusions'] : null,
            $tourTermsItems->isNotEmpty() ? ['label' => 'Điều khoản tour', 'href' => '#tour-terms'] : null,
            $reviewItems->isNotEmpty() ? ['label' => 'Đánh giá', 'href' => '#tour-reviews'] : null,
            $faqItems->isNotEmpty() ? ['label' => 'FAQ', 'href' => '#tour-faq'] : null,
        ])->filter()->values();
        $sectionHeadingConfig = data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY);
        $tourDetailsHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_details');
        $tourDeparturesHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_departures');
        $tourItineraryHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_itinerary');
        $tourPricingHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_pricing');
        $tourInclusionsHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_inclusions');
        $tourReviewsHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_reviews');
        $tourFaqHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_faq');
        $tourRelatedHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_related');
        $tourSocialShareHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_social_share');
        $tourCtaHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'tour_cta');
    @endphp

    <section class="relative overflow-hidden bg-[#0d1730]" id="tour-hero">
        <div class="relative min-h-[72vh]">
            @if ($cover)
                <picture class="absolute inset-0 block h-full w-full">
                    @if ($coverMedium)
                        <source media="(max-width: 767px)" srcset="{{ $coverMedium }}">
                    @endif
                    <img
                        src="{{ $cover }}"
                        alt="{{ $tour->cover_alt ?: $tour->title }}"
                        class="absolute inset-0 h-full w-full object-cover"
                        width="1600"
                        height="900"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    >
                </picture>
                <div class="absolute inset-0 bg-slate-950/60"></div>
            @else
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,106,0,0.24),_transparent_28%),radial-gradient(circle_at_left,_rgba(0,74,153,0.18),_transparent_32%),linear-gradient(135deg,_#0d1730_0%,_#12396f_48%,_#0d1730_100%)]"></div>
            @endif

            <div class="theme-grid-pattern absolute inset-0 opacity-20"></div>

            <div class="relative mx-auto flex min-h-[72vh] max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
                <div class="max-w-4xl space-y-7">
                    <div class="space-y-4">
                        <div class="frontsite-text-reveal flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-100/85" data-reveal="meta">
                            @if ($tour->primaryCategory)
                                <a href="{{ route('tour-categories.show', $tour->primaryCategory) }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 transition hover:bg-white/14">
                                    <i class="fa-solid fa-compass text-orange-200"></i>
                                    {{ $tour->primaryCategory->name }}
                                </a>
                            @endif
                            @if ($tour->destination)
                                <a href="{{ route('destinations.show', $tour->destination) }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 transition hover:bg-white/14">
                                    <i class="fa-solid fa-location-dot text-orange-200"></i>
                                    {{ $tour->destination->name }}
                                </a>
                            @endif
                            @if ($tour->region)
                                <a href="{{ route('regions.show', $tour->region) }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 transition hover:bg-white/14">
                                    <i class="fa-regular fa-map text-orange-200"></i>
                                    {{ $tour->region->name }}
                                </a>
                            @endif
                            @if ($ratingAverage)
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2">
                                    <i class="fa-solid fa-star text-[color:var(--color-warning)]"></i>
                                    {{ $ratingAverage }}/5{{ $ratingCount ? ' • '.$ratingCount.' lượt đánh giá' : '' }}
                                </span>
                            @endif
                        </div>

                        <h1 class="frontsite-text-reveal font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" data-reveal="title">
                            {{ $tour->title }}
                        </h1>
                    </div>

                    <div class="frontsite-text-reveal flex flex-wrap gap-3" data-reveal="cta">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-[1rem] bg-primary px-6 py-4 text-sm font-semibold text-white transition hover:bg-primary-hover"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="tour"
                            data-travel-inquiry-tour-id="{{ $tour->id }}"
                            data-travel-inquiry-context="{{ $tour->title }}"
                            data-travel-inquiry-subject="{{ $tour->title }}"
                            data-travel-inquiry-modal-title="Thông tin đặt tour"
                            data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                        >
                            {{ $tour->cta_mode === 'contact' ? 'Nhận tư vấn tour' : 'Kiểm tra chỗ ngay' }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <a href="{{ $phoneLink }}" class="inline-flex items-center gap-2 rounded-[1rem] border border-white/15 bg-white/10 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/15">
                            <i class="fa-solid fa-phone-volume"></i>
                            {{ $phone !== '' ? $phone : 'Gọi tư vấn' }}
                        </a>

                        @if ($departureGroups->isNotEmpty())
                            <a href="#tour-departures" class="inline-flex items-center gap-2 rounded-[1rem] border border-white/15 bg-white/10 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/15">
                                <i class="fa-regular fa-calendar-days"></i>
                                Xem lịch khởi hành
                            </a>
                        @endif
                    </div>

                    @if ($heroFacts->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($heroFacts as $fact)
                                <div class="rounded-[1.4rem] border border-white/10 bg-white/10 p-4 text-white backdrop-blur-sm">
                                    <p class="flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-orange-100">
                                        <i class="{{ $fact['icon'] }}"></i>
                                        {{ $fact['label'] }}
                                    </p>
                                    <p class="mt-3 text-sm font-semibold leading-6 text-white">{{ $fact['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @include('themes.haidangtravel.partials.breadcrumbs', [
                'breadcrumbVariant' => 'plain',
                'items' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => $tour->scope->label(), 'url' => route($tour->scope->routeName())],
                    ['label' => $tour->title],
                ],
            ])
        </div>
    </section>

    @if ($sectionLinks->isNotEmpty())
        <section class="border-b border-slate-200/80 bg-white/80 px-4 py-4 backdrop-blur-sm sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-wrap gap-2">
                    @foreach ($sectionLinks as $link)
                        <a href="{{ $link['href'] }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)] hover:text-primary">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="px-4 py-9 sm:px-6 lg:px-8 lg:py-11">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="order-2 min-w-0 space-y-10 lg:order-1">
                @if ($tourGallery->isNotEmpty())
                    @include('themes.haidangtravel.partials.frontsite-media-gallery', [
                        'collectionKey' => 'tour-detail-current',
                        'gallery' => $tourGallery,
                        'sectionId' => 'tour-gallery',
                        'title' => $tour->title,
                    ])
                @endif

                @if ($hasTourDetails)
                    <section id="tour-details" class="space-y-5">
                        @if ($tourDetailsHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $tourDetailsHeading['title'],
                            ])
                        @endif

                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_34px_90px_-54px_rgba(15,23,42,0.28)]">
                            @include('themes.haidangtravel.partials.expandable-rich-text', [
                                'contentHtml' => $renderedTourContent,
                                'contentClass' => 'theme-copy text-base leading-8 text-slate-600',
                                'containerClass' => 'space-y-4 px-6 py-6 sm:px-8 sm:py-8',
                                'desktopCollapsedHeight' => 800,
                                'expandableId' => 'tour-details-expandable-panel',
                                'introText' => $tourDetailIntro,
                                'mobileCollapsedHeight' => 400,
                            ])
                        </div>
                    </section>
                @endif

                @if ($departureGroups->isNotEmpty())
                    <section id="tour-departures" class="space-y-5" data-tour-departure-tabs>
                        @if ($tourDeparturesHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $tourDeparturesHeading['title'],
                                'description' => $tourDeparturesHeading['description'],
                            ])
                        @endif

                        <div class="flex flex-wrap gap-3" role="tablist" aria-label="Lịch khởi hành theo tháng">
                            @foreach ($departureGroups as $group)
                                @php
                                    $isActiveDepartureGroup = $group['id'] === $activeDepartureGroupId;
                                @endphp

                                <button
                                    type="button"
                                    id="tour-departure-tab-{{ $group['id'] }}"
                                    role="tab"
                                    aria-selected="{{ $isActiveDepartureGroup ? 'true' : 'false' }}"
                                    aria-controls="{{ $group['anchor'] }}"
                                    tabindex="{{ $isActiveDepartureGroup ? '0' : '-1' }}"
                                    data-tour-departure-tab="{{ $group['id'] }}"
                                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border px-5 py-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 {{ $isActiveDepartureGroup ? 'border-transparent bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.58)]' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' }}"
                                >
                                    <span>{{ $group['label'] }}</span>
                                    @if ($loop->first && $nearestDepartureTabLabel)
                                        <span class="rounded-full bg-black/8 px-2.5 py-1 text-[11px] font-semibold text-current">
                                            Gần nhất {{ $nearestDepartureTabLabel }}
                                        </span>
                                    @endif
                                    <span class="rounded-full bg-black/8 px-2.5 py-1 text-[11px] font-semibold {{ $isActiveDepartureGroup ? 'text-white/90' : 'text-slate-500' }}">
                                        {{ $group['count'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="space-y-6">
                            @foreach ($departureGroups as $group)
                                <section
                                    id="{{ $group['anchor'] }}"
                                    role="tabpanel"
                                    aria-labelledby="tour-departure-tab-{{ $group['id'] }}"
                                    data-tour-departure-panel="{{ $group['id'] }}"
                                    class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_34px_90px_-54px_rgba(15,23,42,0.28)]"
                                >
                                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-8">
                                        <div>
                                            <h2 class="frontsite-h2-card">{{ $group['label'] }}</h2>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-[color:var(--color-primary-soft)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-primary">
                                            {{ $group['count'] }} lịch
                                        </span>
                                    </div>

                                    <div class="hidden overflow-x-auto lg:block">
                                        <table class="min-w-[720px] w-full border-collapse">
                                            <caption class="sr-only">{{ $group['label'] }}</caption>
                                            <thead>
                                                <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                    <th class="px-2 py-2">Ngày đi</th>
                                                    <th class="px-2 py-2">Khởi hành</th>
                                                    <th class="px-2 py-2">Tiêu chuẩn</th>
                                                    <th class="px-2 py-2">Giá</th>
                                                    <th class="px-2 py-2">Trạng thái</th>
                                                    <th class="px-2 py-2 text-right">Tác vụ</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($group['items'] as $departure)
                                                    @php
                                                        $rowStatus = $statusMeta($departure->status, (bool) $departure->is_featured);
                                                        $rowPriceValue = $departure->sale_price ?: $departure->base_price;
                                                        $rowSubject = trim($tour->title.' - '.($departure->departure_date?->format('d/m/Y') ?: 'liên hệ'));
                                                    @endphp
                                                    <tr class="align-top text-sm text-slate-600">
                                                        <td class="px-2 py-2 whitespace-nowrap">
                                                            <p class="font-semibold text-slate-950">{{ $departure->departure_date?->format('d/m/Y') ?: 'Liên hệ' }}</p>
                                                            @if ($departure->transport_label || $displayTransport !== '')
                                                                <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $departure->transport_label ?: $displayTransport }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-2 py-2 whitespace-nowrap">
                                                            <p class="font-medium text-slate-950">{{ filled($departure->departure_location) ? $departure->departure_location : (filled($displayDepartureLocation) ? $displayDepartureLocation : 'Theo tư vấn') }}</p>
                                                            @if ($tour->destination)
                                                                <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $tour->destination->name }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-2 py-2 whitespace-nowrap">
                                                            <p class="font-medium text-slate-950">{{ filled($departure->standard_label) ? $departure->standard_label : (filled($displayStandard) ? $displayStandard : 'Liên hệ') }}</p>
                                                            @if ($durationLabel !== '')
                                                                <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $durationLabel }}</p>
                                                            @endif
                                                            @if ($departure->pricing_note)
                                                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $departure->pricing_note }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-2 py-2 whitespace-nowrap">
                                                            <p class="font-heading text-xl font-bold text-[color:var(--color-price)] whitespace-nowrap">{{ $formatPrice($rowPriceValue) }}</p>
                                                            @if ($departure->base_price && $departure->sale_price && $departure->base_price !== $departure->sale_price)
                                                                <p class="mt-2 whitespace-nowrap text-sm text-slate-400 line-through">{{ $formatPrice($departure->base_price) }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-2 py-2 whitespace-nowrap">
                                                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $rowStatus['class'] }}">
                                                                {{ $rowStatus['label'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-2 py-2 text-right whitespace-nowrap">
                                                            <button
                                                                type="button"
                                                                class="inline-flex items-center gap-2 rounded-[0.95rem] border border-orange-200 px-4 py-2.5 text-sm font-semibold text-primary whitespace-nowrap transition hover:bg-orange-50"
                                                                data-travel-inquiry-open
                                                                data-travel-inquiry-source="tour"
                                                                data-travel-inquiry-tour-id="{{ $tour->id }}"
                                                                data-travel-inquiry-context="{{ $rowSubject }}"
                                                                data-travel-inquiry-subject="{{ $rowSubject }}"
                                                                data-travel-inquiry-modal-title="Kiểm tra chỗ còn lại"
                                                                data-travel-inquiry-modal-description="Điền nhanh thông tin để Hải Đăng Travel kiểm tra chỗ và tư vấn đúng ngày đi bạn đang chọn."
                                                            >
                                                                {{ $tour->cta_mode === 'contact' ? 'Nhận tư vấn' : 'Đặt tour' }}
                                                                <i class="fa-solid fa-arrow-right"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="grid gap-4 p-4 sm:p-6 lg:hidden">
                                        @foreach ($group['items'] as $departure)
                                            @php
                                                $cardStatus = $statusMeta($departure->status, (bool) $departure->is_featured);
                                                $cardPriceValue = $departure->sale_price ?: $departure->base_price;
                                                $cardSubject = trim($tour->title.' - '.($departure->departure_date?->format('d/m/Y') ?: 'liên hệ'));
                                            @endphp
                                            <article class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-[0_18px_45px_-38px_rgba(15,23,42,0.35)]">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Ngày đi</p>
                                                        <p class="mt-1 font-heading text-[22px] font-black leading-none text-slate-950">{{ $departure->departure_date?->format('d/m/Y') ?: 'Liên hệ' }}</p>
                                                    </div>

                                                    <span class="shrink-0 rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] {{ $cardStatus['class'] }}">
                                                        {{ $cardStatus['label'] }}
                                                    </span>
                                                </div>

                                                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                                    <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Khởi hành</p>
                                                        <p class="mt-0.5 font-semibold text-slate-900">{{ filled($departure->departure_location) ? $departure->departure_location : (filled($displayDepartureLocation) ? $displayDepartureLocation : 'Theo tư vấn') }}</p>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Tiêu chuẩn</p>
                                                        <p class="mt-0.5 font-semibold text-slate-900">{{ filled($departure->standard_label) ? $departure->standard_label : (filled($displayStandard) ? $displayStandard : 'Liên hệ') }}</p>
                                                    </div>
                                                </div>

                                                <div class="mt-4 border-t border-slate-100 pt-4">
                                                    <div class="grid grid-cols-12 items-end gap-3 sm:flex sm:items-end sm:justify-between">
                                                        <div class="col-span-6 min-w-0 sm:col-auto">
                                                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Giá từ</p>

                                                            @if ($departure->base_price && $departure->sale_price && $departure->base_price !== $departure->sale_price)
                                                                <p class="whitespace-nowrap text-right text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                                                    <span class="block whitespace-nowrap text-[11px] leading-none text-slate-400 line-through">
                                                                        {{ $formatPrice($departure->base_price) }}
                                                                    </span>
                                                                </p>
                                                            @endif

                                                            <p class="mt-1 whitespace-nowrap text-right font-heading text-[20px] font-black leading-none text-[color:var(--color-price)] sm:text-[26px]">{{ $formatPrice($cardPriceValue) }}</p>
                                                        </div>

                                                        <div class="col-span-6 sm:col-auto">
                                                            <button
                                                                type="button"
                                                                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-2xl border border-orange-200 bg-orange-50 px-4 text-sm font-bold text-primary transition hover:bg-orange-100"
                                                                data-travel-inquiry-open
                                                                data-travel-inquiry-source="tour"
                                                                data-travel-inquiry-tour-id="{{ $tour->id }}"
                                                                data-travel-inquiry-context="{{ $cardSubject }}"
                                                                data-travel-inquiry-subject="{{ $cardSubject }}"
                                                                data-travel-inquiry-modal-title="Kiểm tra chỗ còn lại"
                                                                data-travel-inquiry-modal-description="Điền nhanh thông tin để Hải Đăng Travel kiểm tra chỗ và tư vấn đúng ngày đi bạn đang chọn."
                                                            >
                                                                {{ $tour->cta_mode === 'contact' ? 'Nhận tư vấn' : 'Đặt tour' }}
                                                                <i class="fa-solid fa-arrow-right"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($itineraryItems->isNotEmpty())
                    <section id="tour-itinerary" class="space-y-5">
                        @if ($tourItineraryHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $tourItineraryHeading['title'],
                                'description' => $tourItineraryHeading['description'] !== '' ? $tourItineraryHeading['description'] : $tourHeadingDescription,
                            ])
                        @endif

                        <div class="space-y-2" data-faq-accordion data-faq-single="true">
                                @foreach ($itineraryItems as $item)
                                    @php
                                        $isOpen = $loop->first;
                                        $panelId = 'tour-itinerary-panel-'.$loop->iteration;
                                        $triggerId = 'tour-itinerary-trigger-'.$loop->iteration;
                                        $itineraryTitle = trim((string) $item['title']);
                                        $normalizedItineraryTitle = \Illuminate\Support\Str::of($itineraryTitle)
                                            ->lower()
                                            ->ascii()
                                            ->replaceMatches('/[^a-z0-9]+/', ' ')
                                        ->squish()
                                        ->value();
                                    $isGenericItineraryTitle = $itineraryTitle === ''
                                        || preg_match('/^(chang|ngay|day)\s*0*\d+$/', $normalizedItineraryTitle) === 1;
                                    $displayItineraryTitle = $isGenericItineraryTitle ? '' : $itineraryTitle;
                                @endphp
                                <article class="theme-panel overflow-hidden rounded-[1.7rem] border border-slate-200/70 bg-[linear-gradient(180deg,_#ffffff_0%,_#fffaf7_100%)] ring-transparent shadow-[0_22px_60px_-52px_rgba(15,23,42,0.24)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_28px_74px_-52px_rgba(15,23,42,0.3)]" data-faq-item data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.06, 2, '.', '') }}">
                                    <h3 class="w-full">
                                        <button
                                            type="button"
                                            id="{{ $triggerId }}"
                                            class="group flex w-full items-center px-2 py-2 text-left sm:px-2.5 sm:py-1.5"
                                            aria-controls="{{ $panelId }}"
                                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                            data-faq-trigger
                                        >
                                            <span class="flex min-h-[3rem] min-w-0 flex-1 items-center sm:min-h-[1.25rem]">
                                                @if ($displayItineraryTitle !== '')
                                                    <span class="block font-heading text-base font-extrabold leading-tight tracking-tight text-balance text-primary sm:text-[1.2rem]">{{ $displayItineraryTitle }}</span>
                                                @endif
                                            </span>
                                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-[0.95rem] border border-orange-100/80 bg-white text-primary shadow-[0_16px_34px_-28px_rgba(255,106,0,0.32)] transition group-hover:bg-[color:var(--color-primary-soft)] sm:size-10" aria-hidden="true">
                                                <i class="fa-solid {{ $isOpen ? 'fa-minus' : 'fa-plus' }} text-sm" data-faq-icon></i>
                                            </span>
                                        </button>
                                    </h3>

                                    <div
                                        id="{{ $panelId }}"
                                        class="px-2 pb-2 sm:px-2.5 sm:pb-2.5"
                                        role="region"
                                        aria-labelledby="{{ $triggerId }}"
                                        data-faq-panel
                                        @if (! $isOpen) hidden @endif
                                    >
                                        <div class="rounded-[1.2rem] bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] px-3 py-3 text-sm leading-7 text-slate-600 sm:px-3.5 sm:py-3.5 sm:text-base">
                                            {!! \App\Support\RichText::render($item['content']) !!}
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($hasPricingSection)
                    <section id="tour-pricing" class="space-y-5">
                        @if ($tourPricingHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $tourPricingHeading['title'],
                                'description' => $tourPricingHeading['description'],
                            ])
                        @endif

                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_34px_90px_-54px_rgba(15,23,42,0.28)]">
                            @if ($pricingTableItems->isNotEmpty())
                                <div>
                                    <div class="hidden lg:block">
                                        <table class="w-full border-collapse">
                                            <caption class="sr-only">Các ghi chú giá bổ sung của tour</caption>
                                            <thead>
                                                <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                    <th class="px-6 py-4">Hạng mục</th>
                                                    <th class="px-6 py-4 text-right">Mức giá</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($pricingTableItems as $item)
                                                    <tr class="text-sm text-slate-600">
                                                        <td class="px-6 py-5">
                                                            <p class="font-medium text-slate-950">{{ $item['label'] !== '' ? $item['label'] : 'Liên hệ tư vấn' }}</p>
                                                        </td>
                                                        <td class="px-6 py-5 text-right">
                                                            <p class="font-heading text-xl font-bold text-[color:var(--color-price)] whitespace-nowrap">{{ $formatSupplementalPrice($item['price']) }}</p>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="grid gap-4 p-4 sm:p-6 lg:hidden">
                                        @foreach ($pricingTableItems as $item)
                                            <article class="rounded-[1.6rem] border border-slate-200 bg-slate-50/70 p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Hạng mục</p>
                                                <p class="mt-2 text-base font-semibold text-slate-950">{{ $item['label'] !== '' ? $item['label'] : 'Liên hệ tư vấn' }}</p>
                                                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Mức giá</p>
                                                <p class="mt-2 font-heading text-2xl font-bold text-[color:var(--color-price)]">{{ $formatSupplementalPrice($item['price']) }}</p>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                @if ($inclusionItems->isNotEmpty())
                    <section id="tour-inclusions" class="space-y-5">
                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_30px_80px_-58px_rgba(15,23,42,0.3)]">
                            @if ($tourInclusionsHeading['is_visible'] && $tourInclusionsHeading['title'] !== '')
                                <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
                                    <h2 class="frontsite-h2-compact">{{ $tourInclusionsHeading['title'] }}</h2>
                                </div>
                            @endif

                            <ul class="grid gap-3 px-6 py-6 sm:px-8">
                                @foreach ($inclusionItems as $item)
                                    <li class="flex items-start gap-3 rounded-[1.35rem] bg-slate-50 px-4 py-4 text-sm leading-7 text-slate-600">
                                        <span class="mt-1 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-[color:var(--color-success-soft)] text-[11px] text-success">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif

                @if ($tourTermsItems->isNotEmpty())
                    <section id="tour-terms" class="space-y-5">
                        @include('themes.haidangtravel.partials.faq-block', [
                            'accordionId' => 'tour-terms',
                            'accordionIconClass' => 'inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-orange-100 bg-[color:var(--color-primary-soft)] text-primary transition',
                            'accordionTriggerClass' => 'flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left sm:px-4 sm:py-3',
                            'items' => $tourTermsItems,
                            'title' => $tourTermsTitle,
                        ])
                    </section>
                @endif

                @if ($reviewItems->isNotEmpty())
                    @include('themes.haidangtravel.partials.review-grid', [
                        'description' => $tourReviewsHeading['is_visible'] ? $tourReviewsHeading['description'] : '',
                        'items' => $reviewItems,
                        'sectionId' => 'tour-reviews',
                        'summary' => $reviewSummary,
                        'title' => $tourReviewsHeading['is_visible'] ? $tourReviewsHeading['title'] : '',
                    ])
                @endif

                @if ($faqItems->isNotEmpty())
                    <section id="tour-faq" class="space-y-5">
                        @include('themes.haidangtravel.partials.faq-block', [
                            'accordionId' => 'tour-faq',
                            'accordionIconClass' => 'inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-orange-100 bg-[color:var(--color-primary-soft)] text-primary transition',
                            'accordionTriggerClass' => 'flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left sm:px-4 sm:py-3',
                            'items' => $faqItems,
                            'title' => $tourFaqHeading['is_visible'] ? $tourFaqHeading['title'] : '',
                        ])
                    </section>
                @endif

                @if ($relatedTours->isNotEmpty())
                    <section class="space-y-5">
                        @php
                            $relatedTourCount = $relatedTours->count();
                            $shouldUseRelatedTourSlider = $relatedTourCount >= 4;
                            $relatedTourCardGridClasses = \App\Support\FrontsiteCardGrid::classes($relatedTourCount, 'grid gap-2 lg:grid-cols-2 xl:grid-cols-3');
                            $relatedTourCardVariant = $shouldUseRelatedTourSlider
                                ? 'default'
                                : \App\Support\FrontsiteCardGrid::tourVariant($relatedTourCount);
                        @endphp

                        @if ($tourRelatedHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $tourRelatedHeading['title'],
                                'description' => $tourRelatedHeading['description'],
                            ])
                        @endif

                        @if ($shouldUseRelatedTourSlider)
                            <div
                                data-card-carousel
                                data-desktop-slider="true"
                                style="--mobile-card-width: calc(83.333% - 0.17rem); --desktop-card-width: calc((100% - 2rem) / 3);"
                            >
                                <div class="mb-4 flex items-center justify-end gap-3 frontsite-slider-nav">
                                    <button
                                        type="button"
                                        data-card-carousel-prev
                                        class="service-card-carousel-control"
                                        aria-label="Xem tour liên quan trước"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                    </button>
                                    <button
                                        type="button"
                                        data-card-carousel-next
                                        class="service-card-carousel-control"
                                        aria-label="Xem tour liên quan tiếp theo"
                                    >
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>

                                <div class="service-card-carousel-track" data-card-carousel-track>
                                    @foreach ($relatedTours as $relatedTour)
                                        <div class="service-card-carousel-item" data-card-carousel-item>
                                            @include('themes.haidangtravel.partials.tour-card', [
                                                'tour' => $relatedTour,
                                                'variant' => $relatedTourCardVariant,
                                                'revealDelay' => number_format(($loop->index % 3) * 0.05, 2, '.', ''),
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="{{ $relatedTourCardGridClasses }}">
                                @foreach ($relatedTours as $relatedTour)
                                    @include('themes.haidangtravel.partials.tour-card', [
                                        'tour' => $relatedTour,
                                        'variant' => $relatedTourCardVariant,
                                        'revealDelay' => number_format(($loop->index % 3) * 0.05, 2, '.', ''),
                                    ])
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <aside class="order-1 space-y-5 lg:order-2 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-[1.9rem] border border-slate-200 bg-white p-5 shadow-[0_30px_80px_-58px_rgba(15,23,42,0.3)] sm:p-6">
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-[1.35rem] font-bold leading-none text-slate-950">Giá:</p>
                                @if ($startingBasePriceValue && $startingPriceValue && $startingBasePriceValue !== $startingPriceValue)
                                    <p class="pt-1 text-[0.8rem] font-semibold whitespace-nowrap text-slate-300 line-through">{{ $formatPrice($startingBasePriceValue) }} / Khách</p>
                                @endif
                            </div>

                            @if ($startingPriceValue)
                                <div class="flex flex-wrap items-end gap-2">
                                    <p class="font-heading text-[1.9rem] font-extrabold leading-none whitespace-nowrap text-[color:var(--color-price)]">
                                        {{ $formatPrice($startingPriceValue) }}
                                    </p>
                                    <p class="pb-0.5 text-[1.25rem] font-semibold leading-none whitespace-nowrap text-slate-900">/ Khách</p>
                                </div>
                            @else
                                <p class="font-heading text-[1.75rem] font-extrabold leading-none text-[color:var(--color-price)]">
                                    Liên hệ
                                </p>
                            @endif
                        </div>

                        @if ($sidebarInfoItems->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($sidebarInfoItems as $item)
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-[1rem] border border-slate-200 bg-slate-50 text-slate-600">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </span>
                                        <p class="min-w-0 pt-1 text-[0.98rem] font-semibold leading-6 text-slate-900">
                                            {{ $item['label'] }}:
                                            <span class="font-bold text-secondary">{{ $item['value'] }}</span>
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="grid gap-3 {{ $miniContactAction ? 'grid-cols-[auto_minmax(0,1fr)] sm:grid-cols-[auto_minmax(0,1fr)_minmax(0,1fr)]' : 'grid-cols-1 sm:grid-cols-2' }}">
                            @if ($miniContactAction)
                                <a
                                    href="{{ $miniContactAction['href'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex size-12 items-center justify-center rounded-[1rem] border border-slate-200 bg-white px-1 transition hover:bg-slate-50"
                                    aria-label="{{ $miniContactAction['label'] }}"
                                    title="{{ $miniContactAction['label'] }}"
                                >
                                    <img
                                        src="{{ $zaloLogoPath }}"
                                        alt=""
                                        class="h-9 w-auto"
                                        width="50"
                                        height="50"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    <span class="sr-only">{{ $miniContactAction['label'] }}</span>
                                </a>
                            @endif

                            <a href="{{ $contactShortcutHref }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-[1rem] border border-orange-200 bg-white px-5 py-3 text-sm font-semibold text-primary transition hover:bg-orange-50">
                                <i class="{{ $contactShortcutIcon }}"></i>
                                {{ $contactShortcutLabel }}
                            </a>

                            <button
                                type="button"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-[1rem] bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover {{ $miniContactAction ? 'col-span-2 sm:col-span-1' : '' }}"
                                data-travel-inquiry-open
                                data-travel-inquiry-source="tour"
                                data-travel-inquiry-tour-id="{{ $tour->id }}"
                                data-travel-inquiry-context="{{ $tour->title }}"
                                data-travel-inquiry-subject="{{ $tour->title }}"
                                data-travel-inquiry-modal-title="Thông tin đặt tour"
                                data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                            >
                                Đặt tour
                            </button>
                        </div>

                        @if ($departureGroups->isNotEmpty())
                            <a href="#tour-departures" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-primary">
                                <i class="fa-regular fa-calendar-days"></i>
                                Xem toàn bộ lịch khởi hành
                            </a>
                        @endif

                        @include('themes.haidangtravel.partials.social-share', [
                            'containerClass' => 'border-t border-slate-100 pt-5',
                            'description' => $tour->excerpt ?: $tourHeadingDescription,
                            'heading' => $tourSocialShareHeading['is_visible'] ? $tourSocialShareHeading['title'] : '',
                            'intro' => $tourSocialShareHeading['is_visible'] ? $tourSocialShareHeading['description'] : '',
                            'title' => $tour->title,
                            'url' => $tour->canonical_url ?: route('tours.show', $tour),
                            'variant' => 'compact',
                        ])
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @include('themes.haidangtravel.partials.frontsite-gallery-lightbox')

    @include('themes.haidangtravel.partials.cta-banner', [
        'description' => $tourCtaHeading['is_visible'] ? $tourCtaHeading['description'] : '',
        'secondaryLabel' => 'Xem thêm tour',
        'secondaryUrl' => route($tour->scope->routeName()),
        'title' => $tourCtaHeading['is_visible'] ? $tourCtaHeading['title'] : '',
    ])
@endsection
