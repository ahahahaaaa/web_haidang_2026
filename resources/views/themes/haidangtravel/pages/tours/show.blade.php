@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $coverMedia = $tourHeroMedia ?? \App\Support\FrontsiteMedia::responsiveUrls($tour, 'cover', 'cover_image_url');
        $cover = $coverMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? null;
        $coverMedium = $coverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $cover;
        $phone = trim((string) ($tour->contact_phone ?: ($siteSettings->hotline ?: $siteSettings->phone)));
        $phoneLink = $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact');
        $zaloLogoPath = asset('images/zalo-footer-logo.svg');
        $zaloUrl = trim((string) $siteSettings->zalo_url);
        $tourGallery = collect(\App\Support\FrontsiteGalleryData::tour($tour));
        $activeFlashSaleOffer = is_array($flashSaleOffer ?? null) ? $flashSaleOffer : null;
        $flashSaleDeparture = data_get($activeFlashSaleOffer, 'departure');
        $today = \Illuminate\Support\Carbon::today(config('app.timezone'));
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
            ->filter(function ($departure) use ($today): bool {
                if (! in_array((string) $departure->status, ['scheduled', 'published'], true)) {
                    return false;
                }

                $date = $departure->departure_date;

                if (! $date) {
                    return true;
                }

                $resolvedDate = $date instanceof \Carbon\CarbonInterface
                    ? $date->copy()->startOfDay()
                    : \Illuminate\Support\Carbon::parse($date)->startOfDay();

                return $resolvedDate->greaterThanOrEqualTo($today);
            })
            ->sort(function ($left, $right) use ($departureDateRank, $compareDepartureRank): int {
                $rankComparison = $compareDepartureRank($departureDateRank($left), $departureDateRank($right));

                if ($rankComparison !== 0) {
                    return $rankComparison;
                }

                return ((int) $left->sort_order <=> (int) $right->sort_order)
                    ?: ((int) $left->getKey() <=> (int) $right->getKey());
            })
            ->values();
        $requestedFlashSaleSlug = trim((string) data_get($flashSaleRequest ?? [], 'slug'));
        $requestedFlashDepartureId = (int) data_get($flashSaleRequest ?? [], 'departure_id');
        $requestedFlashDeparture = $requestedFlashDepartureId > 0
            ? $departures->first(fn ($departure): bool => (int) $departure->getKey() === $requestedFlashDepartureId)
            : null;
        $itineraryItems = collect($tour->itinerary ?? [])
            ->map(function ($item) use ($tour): array {
                $uuid = trim((string) data_get($item, 'uuid'));
                $externalImageUrl = \App\Support\FrontsiteMedia::validatedUrl((string) data_get($item, 'image_url'));
                $imageUrls = $uuid !== ''
                    ? \App\Support\FrontsiteMedia::responsiveUrls(
                        $tour,
                        \App\Support\ContentGallery::tourItineraryCollection($uuid),
                        null,
                    )
                    : ['small' => null, 'medium' => null, 'full' => null];

                $imageUrls['full'] = $imageUrls['full'] ?: $externalImageUrl;
                $imageUrls['medium'] = $imageUrls['medium'] ?: $imageUrls['full'];
                $imageUrls['small'] = $imageUrls['small'] ?: $imageUrls['medium'];

                return [
                    'uuid' => $uuid,
                    'title' => trim((string) data_get($item, 'title')),
                    'meals' => trim((string) data_get($item, 'meals')),
                    'image_alt' => trim((string) data_get($item, 'image_alt')),
                    'image_urls' => $imageUrls,
                    'content' => trim((string) data_get($item, 'content')),
                ];
            })
            ->filter(fn (array $item) => $item['title'] !== ''
                || $item['content'] !== ''
                || $item['meals'] !== ''
                || filled(data_get($item, 'image_urls.medium')))
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
        $reviewFormEnabled = (bool) config('travel_reviews.enabled', true);
        $tourReviewBatches = collect($tourReviewBatches ?? [])->filter()->values();
        $tourReviewStatusMessage = session('tour_review_status');
        $tourReviewFields = ['author_name', 'author_email', 'author_phone', 'author_title', 'title', 'rating_value', 'content', 'tour_review_batch_id', 'g-recaptcha-response'];
        $tourReviewFeedbackMessages = collect($tourReviewFields)->flatMap(fn (string $field) => $errors->get($field))->filter()->values();
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
        $renderedTourContent = filled($tour->content)
            ? \App\Support\RichText::render($tour->content)
            : null;
        $tourShareDescription = trim((string) $tour->excerpt);
        $hasTourDetails = filled($renderedTourContent);
        $showTourDetailHero = \App\Support\FrontsiteAppearance::tourDetailHeroIsVisible($siteSettings->structured_data);
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
        $nextDeparture = $departures->first();
        $sidebarDeparture = $flashSaleDeparture instanceof \Src\Domains\Cms\Models\TourDeparture
            ? $flashSaleDeparture
            : ($requestedFlashDeparture ?: $departures->first(function ($departure) use ($today): bool {
            $date = $departure->departure_date;

            if (! $date) {
                return false;
            }

            $resolvedDate = $date instanceof \Carbon\CarbonInterface
                ? $date->copy()->startOfDay()
                : \Illuminate\Support\Carbon::parse($date)->startOfDay();

            return $resolvedDate->greaterThanOrEqualTo($today);
            }));
        $hasSidebarDeparture = $sidebarDeparture !== null;
        $sidebarTourDepartureDateLabel = \App\Support\TourDepartureSchedule::firstCurrentLabel($tour->departure_schedules ?? []);
        $nextDepartureTransport = trim((string) ($nextDeparture?->transport_label ?? ''));
        $displayDepartureLocation = trim((string) ($tour->departure_location ?: data_get($departures->first(), 'departure_location', '')));
        $displayTransport = trim((string) ($tour->transport ?: $nextDepartureTransport));
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
        $sidebarPriceValue = $hasSidebarDeparture
            ? ($sidebarDeparture->sale_price ?: $sidebarDeparture->base_price)
            : ($tour->sale_price ?: $tour->base_price);
        $sidebarBasePriceValue = $hasSidebarDeparture
            ? $sidebarDeparture->base_price
            : $tour->base_price;
        if ($activeFlashSaleOffer) {
            $sidebarPriceValue = (int) data_get($activeFlashSaleOffer, 'flash_price');
            $sidebarBasePriceValue = data_get($activeFlashSaleOffer, 'regular_price');
        }
        $sidebarBookingDepartureId = $sidebarDeparture?->getKey();
        $sidebarBookingFlashSaleSlug = $activeFlashSaleOffer
            ? trim((string) data_get($activeFlashSaleOffer, 'campaign_slug'))
            : ($requestedFlashDeparture && (int) $requestedFlashDeparture->getKey() === (int) $sidebarBookingDepartureId
                ? $requestedFlashSaleSlug
                : '');
        $sidebarBookingPriceType = $activeFlashSaleOffer
            ? 'flash_sale'
            : ($sidebarBookingFlashSaleSlug !== '' ? 'flash_unavailable' : 'regular');
        $nearestDepartureTabLabel = $nextDeparture?->departure_date?->format('d/m');
        $sidebarDepartureLocation = $activeFlashSaleOffer && $hasSidebarDeparture
            ? trim((string) ($sidebarDeparture->departure_location ?: $tour->departure_location))
            : trim((string) $tour->departure_location);
        $sidebarDepartureDateLabel = $hasSidebarDeparture
            ? $sidebarDeparture?->departure_date?->format('d-m-Y')
            : $sidebarTourDepartureDateLabel;
        $sidebarTransport = $hasSidebarDeparture
            ? trim((string) $sidebarDeparture->transport_label)
            : trim((string) $tour->transport);
        $sidebarStandard = $hasSidebarDeparture
            ? trim((string) ($sidebarDeparture->standard_label ?: $tour->standard_label))
            : trim((string) $tour->standard_label);
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
        $departureDateLabel = static function ($date): string {
            if (! $date) {
                return 'Liên hệ';
            }

            $resolved = $date instanceof \Carbon\CarbonInterface
                ? $date
                : \Illuminate\Support\Carbon::parse($date);
            $weekdayLabels = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

            return $weekdayLabels[$resolved->dayOfWeek].', '.$resolved->format('d/m/Y');
        };
        $tourSyncStates = collect($tour->agencySyncStates ?? []);
        $fallbackTourCode = trim((string) data_get(
            $tourSyncStates->first(fn ($state) => filled($state->tour_code ?? null)),
            'tour_code'
        ));
        $departureTourCodes = $tourSyncStates
            ->filter(fn ($state) => filled($state->tour_departure_id ?? null) && filled($state->tour_code ?? null))
            ->groupBy(fn ($state) => (int) $state->tour_departure_id)
            ->map(fn ($states) => trim((string) $states->first()->tour_code));
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
                    'month_label' => $key !== 'other' && $firstDate ? 'Tháng '.((int) $firstDate->format('m')) : 'Lịch khác',
                    'year_label' => $key !== 'other' && $firstDate ? $firstDate->format('Y') : null,
                    'count' => $items->count(),
                    'items' => $items->values(),
                ];
            })
            ->values();
        $activeDepartureGroupId = data_get($departureGroups->first(), 'id');
        $heroFacts = collect([
            ['label' => 'Khởi hành', 'value' => $displayDepartureLocation, 'icon' => \App\Support\TourUiIcons::DEPARTURE_LOCATION],
            ['label' => 'Thời lượng', 'value' => $durationLabel, 'icon' => \App\Support\TourUiIcons::DURATION],
            ['label' => 'Phương tiện', 'value' => $displayTransport, 'icon' => \App\Support\TourUiIcons::transport($displayTransport)],
            ['label' => 'Tiêu chuẩn', 'value' => $displayStandard ?: 'Liên hệ', 'icon' => \App\Support\TourUiIcons::STANDARD],
        ])->filter(fn (array $item) => filled($item['value']))->values();
        $sidebarInfoItems = collect([
            $sidebarDepartureLocation !== '' ? ['icon' => \App\Support\TourUiIcons::DEPARTURE_LOCATION, 'label' => 'Khởi hành', 'value' => $sidebarDepartureLocation] : null,
            $sidebarDepartureDateLabel ? ['icon' => \App\Support\TourUiIcons::DEPARTURE_DATE, 'label' => 'Ngày khởi hành', 'value' => $sidebarDepartureDateLabel] : null,
            $compactDurationLabel !== '' ? ['icon' => \App\Support\TourUiIcons::DURATION, 'label' => 'Thời gian', 'value' => $compactDurationLabel] : null,
            $sidebarTransport !== '' ? ['icon' => \App\Support\TourUiIcons::transport($sidebarTransport), 'label' => 'Di chuyển', 'value' => $sidebarTransport] : null,
            $sidebarStandard !== '' ? ['icon' => \App\Support\TourUiIcons::STANDARD, 'label' => 'Tiêu chuẩn', 'value' => $sidebarStandard] : null,
        ])->filter()->values();
        $sectionLinks = collect([
            $tourGallery->isNotEmpty() ? ['label' => 'Thư viện', 'href' => '#tour-gallery'] : null,
            $hasTourDetails ? ['label' => 'Chi tiết tour', 'href' => '#tour-details'] : null,
            $departureGroups->isNotEmpty() ? ['label' => 'Lịch khởi hành', 'href' => '#tour-departures'] : null,
            $hasPricingSection ? ['label' => 'Phụ thu & ghi chú', 'href' => '#tour-pricing'] : null,
            $itineraryItems->isNotEmpty() ? ['label' => 'Lịch trình', 'href' => '#tour-itinerary'] : null,
            $inclusionItems->isNotEmpty() ? ['label' => 'Quyền lợi', 'href' => '#tour-inclusions'] : null,
            $tourTermsItems->isNotEmpty() ? ['label' => 'Điều khoản tour', 'href' => '#tour-terms'] : null,
            ($reviewItems->isNotEmpty() || $reviewFormEnabled) ? ['label' => 'Đánh giá', 'href' => $reviewItems->isNotEmpty() ? '#tour-reviews' : '#tour-review-form'] : null,
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

    @if ($showTourDetailHero)
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

            <div class="relative mx-auto flex min-h-[72vh] max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
                <div class="w-full space-y-7">
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

                        <h1 class="frontsite-text-reveal font-heading text-lg font-extrabold leading-tight tracking-tight text-white sm:text-2xl lg:text-3xl" data-reveal="title">
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
                            data-travel-inquiry-departure-id="{{ $sidebarBookingDepartureId }}"
                            data-travel-inquiry-flash-sale-slug="{{ $sidebarBookingFlashSaleSlug }}"
                            data-travel-inquiry-price-type="{{ $sidebarBookingPriceType }}"
                            data-travel-inquiry-price-label="{{ $sidebarPriceValue ? $formatPrice($sidebarPriceValue) : '' }}"
                            data-travel-inquiry-regular-price-label="{{ $sidebarBasePriceValue ? $formatPrice($sidebarBasePriceValue) : ($sidebarPriceValue ? $formatPrice($sidebarPriceValue) : '') }}"
                            data-travel-inquiry-flash-tickets-remaining="{{ (int) data_get($activeFlashSaleOffer, 'remaining_ticket_quantity', 0) }}"
                            data-travel-inquiry-context="{{ $tour->title }}"
                            data-travel-inquiry-subject="{{ $tour->title }}"
                            data-travel-inquiry-modal-title="Thông tin đặt tour"
                            data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                        >
                            {{ $tour->cta_mode === 'contact' ? 'Tư vấn' : 'Kiểm tra chỗ ngay' }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <a href="{{ $phoneLink }}" class="inline-flex items-center gap-2 rounded-[1rem] border border-white/15 bg-white/10 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/15">
                            <i class="fa-solid fa-phone-volume"></i>
                            {{ $phone !== '' ? $phone : 'Gọi tư vấn' }}
                        </a>

                        @includeWhen(! empty($tourVoucherCta ?? null), 'themes.haidangtravel.partials.tour-voucher-cta-button', [
                            'containerClasses' => 'contents',
                            'innerClasses' => 'contents',
                            'buttonClasses' => 'tour-voucher-cta-button inline-flex min-h-[52px] max-w-full flex-nowrap items-center justify-center gap-2 rounded-[1rem] bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] px-5 py-4 text-sm font-extrabold uppercase leading-5 tracking-[0.08em] whitespace-nowrap text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.78)] transition hover:-translate-y-0.5 hover:shadow-[0_24px_55px_-24px_rgba(255,106,0,0.82)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2',
                            'voucherCta' => $tourVoucherCta ?? null,
                        ])

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
    @endif

    <section class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @include('themes.haidangtravel.partials.breadcrumbs', [
                'breadcrumbVariant' => 'plain',
                'items' => $breadcrumbs ?? [],
            ])

            @if (! $showTourDetailHero)
                <h1 class="mt-4 w-full font-heading text-[0.9375rem] font-extrabold leading-tight tracking-tight text-slate-900 sm:text-lg lg:text-2xl">
                    {{ $tour->title }}
                </h1>
            @endif
        </div>
    </section>

    <div data-tour-content-shell>
        @if ($sectionLinks->isNotEmpty())
            <section
                class="sticky top-[var(--tour-sticky-header-offset)] z-30 border-b border-slate-200/80 bg-white/95 px-4 py-4 shadow-[0_14px_30px_-28px_rgba(15,23,42,0.45)] backdrop-blur-xl sm:px-6 lg:px-8"
                data-tour-section-nav
            >
                <div class="mx-auto max-w-7xl">
                    <nav class="-mx-1 overflow-x-auto px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Điều hướng nội dung tour">
                        <div class="flex min-w-max gap-2">
                            @foreach ($sectionLinks as $link)
                                <a href="{{ $link['href'] }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)] hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2" data-tour-section-link>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </nav>
                </div>
            </section>
        @endif

    @include('themes.haidangtravel.partials.geo-answer-panel', [
        'geo' => $geo ?? [],
        'sectionClasses' => 'px-4 py-8 sm:px-6 lg:px-8',
    ])

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
                                'introText' => '',
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

                        <div class="-mx-1 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            <div class="flex min-w-max gap-3" role="tablist" aria-label="Lịch khởi hành theo tháng">
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
                                        class="inline-flex min-h-[60px] min-w-[96px] flex-col items-center justify-center rounded-[1rem] border px-5 py-2.5 text-sm font-semibold leading-tight transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2 {{ $isActiveDepartureGroup ? 'border-transparent bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.58)]' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' }}"
                                    >
                                        <span class="sr-only">
                                            {{ $group['label'] }}.
                                            @if ($loop->first && $nearestDepartureTabLabel)
                                                Gần nhất {{ $nearestDepartureTabLabel }}.
                                            @endif
                                        </span>
                                        <span aria-hidden="true">{{ $group['month_label'] }}</span>
                                        @if ($group['year_label'])
                                            <span aria-hidden="true">{{ $group['year_label'] }}</span>
                                        @endif
                                        <span class="sr-only {{ $isActiveDepartureGroup ? 'text-white/90' : 'text-slate-500' }}">
                                            {{ $group['count'] }} lịch khởi hành
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-6">
                            @foreach ($departureGroups as $group)
                                @php
                                    $isActiveDepartureGroup = $group['id'] === $activeDepartureGroupId;
                                @endphp

                                <div
                                    id="{{ $group['anchor'] }}"
                                    role="tabpanel"
                                    aria-labelledby="tour-departure-tab-{{ $group['id'] }}"
                                    data-tour-departure-panel="{{ $group['id'] }}"
                                    class="space-y-3"
                                    @if (! $isActiveDepartureGroup) hidden @endif
                                >
                                    @foreach ($group['items'] as $departure)
                                        @php
                                            $rowRegularPriceValue = $departure->sale_price ?: $departure->base_price;
                                            $rowIsFlashSale = $activeFlashSaleOffer
                                                && (int) data_get($activeFlashSaleOffer, 'departure_id') === (int) $departure->getKey();
                                            $rowFlashSaleRequested = $requestedFlashSaleSlug !== ''
                                                && $requestedFlashDepartureId === (int) $departure->getKey();
                                            $rowPriceValue = $rowIsFlashSale
                                                ? (int) data_get($activeFlashSaleOffer, 'flash_price')
                                                : $rowRegularPriceValue;
                                            $rowOriginalPriceValue = $rowIsFlashSale
                                                ? $rowRegularPriceValue
                                                : ($departure->base_price && $departure->sale_price ? $departure->base_price : null);
                                            $rowFlashSaleSlug = $rowIsFlashSale || $rowFlashSaleRequested ? $requestedFlashSaleSlug : '';
                                            $rowDateLabel = $departureDateLabel($departure->departure_date);
                                            $rowTourCode = trim((string) ($departureTourCodes->get((int) $departure->getKey()) ?: $fallbackTourCode));
                                            $rowDepartureLocation = trim((string) ($departure->departure_location ?: $displayDepartureLocation));
                                            $rowSubject = trim($tour->title.' - '.($departure->departure_date?->format('d/m/Y') ?: 'liên hệ'));
                                        @endphp

                                        <article class="grid grid-cols-2 items-center gap-4 rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-[0_22px_55px_-46px_rgba(15,23,42,0.42)] md:grid-cols-[minmax(0,1fr)_auto_auto] md:gap-6 md:rounded-full md:px-5 md:py-4">
                                            <div class="col-span-2 flex min-w-0 items-center gap-3 md:col-span-1">
                                                <time
                                                    class="inline-flex min-h-11 shrink-0 items-center rounded-full bg-slate-50 px-4 py-2 text-sm font-bold whitespace-nowrap text-primary"
                                                    @if ($departure->departure_date) datetime="{{ $departure->departure_date->format('Y-m-d') }}" @endif
                                                >
                                                    {{ $rowDateLabel }}
                                                </time>

                                                @if ($rowTourCode !== '')
                                                    <p class="min-w-0 break-all text-xs font-semibold leading-5 text-slate-700 sm:text-sm" title="Mã tour: {{ $rowTourCode }}">
                                                        <i class="fa-solid fa-ticket mr-1.5 text-slate-400" aria-hidden="true"></i>
                                                        <span class="sr-only">Mã tour: </span>{{ $rowTourCode }}
                                                    </p>
                                                @elseif ($rowDepartureLocation !== '')
                                                    <p class="min-w-0 line-clamp-2 text-sm font-semibold leading-5 text-slate-700" title="Khởi hành: {{ $rowDepartureLocation }}">
                                                        <i class="fa-solid fa-location-dot mr-1.5 text-slate-400" aria-hidden="true"></i>
                                                        <span class="sr-only">Khởi hành: </span>{{ $rowDepartureLocation }}
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="min-w-0 md:text-right">
                                                @if ($rowOriginalPriceValue && $rowOriginalPriceValue !== $rowPriceValue)
                                                    <p class="mb-1 text-xs font-semibold whitespace-nowrap text-slate-400 line-through">{{ $formatPrice($rowOriginalPriceValue) }}</p>
                                                @endif
                                                <p class="font-heading text-lg font-black whitespace-nowrap text-[color:var(--color-price)] sm:text-xl">{{ $formatPrice($rowPriceValue) }}</p>
                                            </div>

                                            <span class="frontsite-tour-card-cta-wrap inline-flex justify-self-end">
                                                <button
                                                    type="button"
                                                    class="frontsite-tour-card-cta frontsite-tour-booking-cta inline-flex min-h-11 items-center justify-center gap-2 border px-5 py-2.5 text-sm font-bold whitespace-nowrap transition focus-visible:outline-none"
                                                    aria-label="Đặt tour ngày {{ $rowDateLabel }}"
                                                    data-travel-inquiry-open
                                                    data-travel-inquiry-source="tour"
                                                    data-travel-inquiry-tour-id="{{ $tour->id }}"
                                                    data-travel-inquiry-departure-id="{{ $departure->getKey() }}"
                                                    data-travel-inquiry-flash-sale-slug="{{ $rowFlashSaleSlug }}"
                                                    data-travel-inquiry-price-type="{{ $rowIsFlashSale ? 'flash_sale' : ($rowFlashSaleRequested ? 'flash_unavailable' : 'regular') }}"
                                                    data-travel-inquiry-price-label="{{ $rowPriceValue ? $formatPrice($rowPriceValue) : '' }}"
                                                    data-travel-inquiry-regular-price-label="{{ $rowRegularPriceValue ? $formatPrice($rowRegularPriceValue) : '' }}"
                                                    data-travel-inquiry-flash-tickets-remaining="{{ $rowIsFlashSale ? (int) data_get($activeFlashSaleOffer, 'remaining_ticket_quantity', 0) : 0 }}"
                                                    data-travel-inquiry-context="{{ $rowSubject }}"
                                                    data-travel-inquiry-subject="{{ $rowSubject }}"
                                                    data-travel-inquiry-modal-title="Thông tin đặt tour"
                                                    data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel kiểm tra chỗ và tư vấn đúng ngày đi bạn đã chọn."
                                                >
                                                    <span>Đặt tour</span>
                                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                </button>
                                            </span>
                                        </article>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($itineraryItems->isNotEmpty())
                    <section id="tour-itinerary" class="space-y-5">
                        @if ($tourItineraryHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $tourItineraryHeading['title'],
                                'description' => $tourItineraryHeading['description'],
                            ])
                        @endif

                        <div class="relative space-y-5 before:absolute before:bottom-8 before:left-[1.1875rem] before:top-8 before:w-px before:bg-gradient-to-b before:from-orange-300 before:via-orange-200 before:to-orange-100 sm:before:left-[1.4375rem]">
                            @foreach ($itineraryItems as $item)
                                @php
                                    $itineraryTitle = trim((string) $item['title']);
                                    $normalizedItineraryTitle = \Illuminate\Support\Str::of($itineraryTitle)
                                        ->lower()
                                        ->ascii()
                                        ->replaceMatches('/[^a-z0-9]+/', ' ')
                                        ->squish()
                                        ->value();
                                    $isGenericItineraryTitle = $itineraryTitle === ''
                                        || preg_match('/^(chang|ngay|day)\s*0*\d+$/', $normalizedItineraryTitle) === 1;
                                    $displayItineraryTitle = $isGenericItineraryTitle
                                        ? ''
                                        : trim((string) preg_replace('/^(ngày|chặng|day)\s*0*\d+\s*[-:–—]?\s*/iu', '', $itineraryTitle));
                                    $displayItineraryTitle = $displayItineraryTitle !== '' ? $displayItineraryTitle : ($isGenericItineraryTitle ? '' : $itineraryTitle);
                                    $itineraryImage = data_get($item, 'image_urls.medium');
                                    $itineraryImageFull = data_get($item, 'image_urls.full') ?: $itineraryImage;
                                    $itineraryImageAlt = $item['image_alt'] !== ''
                                        ? $item['image_alt']
                                        : ($displayItineraryTitle !== '' ? $displayItineraryTitle : 'Hình lịch trình ngày '.$loop->iteration);
                                @endphp

                                <article class="relative pl-11 sm:pl-14" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.06, 2, '.', '') }}">
                                    <span class="absolute left-0 top-5 z-10 inline-flex size-10 items-center justify-center rounded-full border-4 border-white bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] text-white shadow-[0_12px_28px_-12px_rgba(255,106,0,0.78)] sm:size-12" title="Điểm đến chặng {{ $loop->iteration }}">
                                        <i class="fa-solid fa-location-dot text-base sm:text-lg" aria-hidden="true"></i>
                                        <span class="sr-only">Điểm đến chặng {{ $loop->iteration }}</span>
                                    </span>

                                    <details open data-tour-itinerary-details class="group theme-panel overflow-hidden rounded-[1.7rem] border border-orange-100/80 bg-[linear-gradient(180deg,_#ffffff_0%,_#fffaf7_100%)] ring-transparent shadow-[0_22px_60px_-52px_rgba(15,23,42,0.24)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_28px_74px_-52px_rgba(15,23,42,0.3)]">
                                        <summary class="relative grid w-full cursor-pointer list-none items-stretch text-left [&::-webkit-details-marker]:hidden {{ $itineraryImage ? 'sm:grid-cols-[minmax(0,1fr)_minmax(12rem,38%)]' : 'grid-cols-[minmax(0,1fr)_auto]' }}">
                                            <span class="flex min-w-0 flex-col justify-center gap-2 p-4 sm:p-5">
                                                <span class="font-heading text-lg font-extrabold leading-none tracking-tight text-primary">Ngày {{ $loop->iteration }}</span>

                                                @if ($displayItineraryTitle !== '')
                                                    <span class="block font-heading text-base font-bold leading-snug tracking-tight text-balance text-slate-900 sm:text-lg">{{ $displayItineraryTitle }}</span>
                                                @endif

                                                @if ($item['meals'] !== '')
                                                    <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                                                        <i class="fa-solid fa-utensils text-primary" aria-hidden="true"></i>
                                                        <span class="sr-only">Bữa ăn: </span>{{ $item['meals'] }}
                                                    </span>
                                                @endif
                                            </span>

                                            @if ($itineraryImage)
                                                <span class="relative block overflow-hidden bg-orange-50 sm:min-h-44">
                                                    <img
                                                        src="{{ $itineraryImage }}"
                                                        @if ($itineraryImageFull && $itineraryImageFull !== $itineraryImage) srcset="{{ $itineraryImage }} 1x, {{ $itineraryImageFull }} 2x" @endif
                                                        alt="{{ $itineraryImageAlt }}"
                                                        loading="lazy"
                                                        decoding="async"
                                                        class="h-48 w-full object-cover transition duration-500 group-hover:scale-[1.02] sm:h-full sm:min-h-44"
                                                    >
                                                    <span class="absolute right-3 top-3 inline-flex size-9 shrink-0 items-center justify-center rounded-[0.95rem] border border-orange-100/80 bg-white/95 text-primary shadow-[0_16px_34px_-28px_rgba(255,106,0,0.32)] backdrop-blur-sm transition group-hover:bg-[color:var(--color-primary-soft)]" aria-hidden="true">
                                                        <i class="fa-solid fa-minus text-sm" data-tour-itinerary-icon></i>
                                                    </span>
                                                </span>
                                            @else
                                                <span class="m-3 inline-flex size-9 shrink-0 items-center justify-center self-start rounded-[0.95rem] border border-orange-100/80 bg-white/95 text-primary shadow-[0_16px_34px_-28px_rgba(255,106,0,0.32)] transition group-hover:bg-[color:var(--color-primary-soft)] sm:m-4" aria-hidden="true">
                                                    <i class="fa-solid fa-minus text-sm" data-tour-itinerary-icon></i>
                                                </span>
                                            @endif
                                        </summary>

                                        <div class="border-t border-orange-100/70 p-2 sm:p-2.5">
                                            <div class="theme-copy rounded-[1.2rem] bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] px-3 py-3 text-sm leading-7 text-slate-600 sm:px-4 sm:py-4 sm:text-base">
                                                {!! \App\Support\RichText::render($item['content']) !!}
                                            </div>
                                        </div>
                                    </details>
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

                @if ($reviewFormEnabled)
                    <section id="tour-review-form" class="theme-panel p-5 sm:p-7">
                        <div class="mb-6">
                            <h2 class="frontsite-h2-compact">Gửi đánh giá tour</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Đánh giá mới sẽ được lưu ở trạng thái chờ duyệt trước khi hiển thị ngoài trang tour.</p>
                        </div>

                        <form action="{{ route('tour-reviews.web.store', $tour) }}" method="POST" class="grid gap-5" data-frontsite-ajax-form data-frontsite-recaptcha-form data-frontsite-recaptcha-action="tour_review" novalidate>
                            @csrf
                            @include('themes.haidangtravel.partials.recaptcha-v3-field')

                            <div
                                class="frontsite-form-feedback {{ $tourReviewStatusMessage || $tourReviewFeedbackMessages->isNotEmpty() ? ($tourReviewFeedbackMessages->isNotEmpty() ? 'is-error' : 'is-success') : 'hidden' }}"
                                data-frontsite-form-feedback
                                role="status"
                                tabindex="-1"
                            >
                                <p class="font-semibold" data-frontsite-form-feedback-message>
                                    {{ $tourReviewFeedbackMessages->isNotEmpty() ? 'Vui lòng kiểm tra lại thông tin đánh giá.' : $tourReviewStatusMessage }}
                                </p>
                                <ul class="frontsite-form-feedback-list {{ $tourReviewFeedbackMessages->isNotEmpty() ? '' : 'hidden' }}" data-frontsite-form-feedback-list>
                                    @foreach ($tourReviewFeedbackMessages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            @if ($tourReviewBatches->isNotEmpty())
                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Ngày khởi hành đã tham gia</span>
                                    <select name="tour_review_batch_id" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                        <option value="">Chưa chọn / đánh giá chung về tour</option>
                                        @foreach ($tourReviewBatches as $batch)
                                            @php
                                                $batchDate = $batch->departure_date?->format('d/m/Y');
                                                $batchLabel = collect([$batch->label, $batchDate])->filter()->implode(' - ');
                                            @endphp
                                            <option value="{{ $batch->id }}" @selected((string) old('tour_review_batch_id') === (string) $batch->id)>
                                                {{ $batchLabel !== '' ? $batchLabel : 'Lượt đánh giá #'.$batch->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="frontsite-form-field-error {{ $errors->has('tour_review_batch_id') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="tour_review_batch_id">{{ $errors->first('tour_review_batch_id') }}</p>
                                </label>
                            @endif

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Họ tên</span>
                                    <input type="text" name="author_name" value="{{ old('author_name') }}" autocomplete="name" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>
                                    <p class="frontsite-form-field-error {{ $errors->has('author_name') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_name">{{ $errors->first('author_name') }}</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Số điện thoại</span>
                                    <input type="text" name="author_phone" value="{{ old('author_phone') }}" autocomplete="tel" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>
                                    <p class="frontsite-form-field-error {{ $errors->has('author_phone') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_phone">{{ $errors->first('author_phone') }}</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Email</span>
                                    <input type="email" name="author_email" value="{{ old('author_email') }}" autocomplete="email" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                    <p class="frontsite-form-field-error {{ $errors->has('author_email') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_email">{{ $errors->first('author_email') }}</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Ngữ cảnh chuyến đi</span>
                                    <input type="text" name="author_title" value="{{ old('author_title') }}" placeholder="Gia đình, đoàn công ty, nhóm bạn..." class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                    <p class="frontsite-form-field-error {{ $errors->has('author_title') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_title">{{ $errors->first('author_title') }}</p>
                                </label>
                            </div>

                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-900">Tiêu đề ngắn</span>
                                <input type="text" name="title" value="{{ old('title') }}" placeholder="Ví dụ: Lịch trình rõ ràng, tư vấn chu đáo" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                <p class="frontsite-form-field-error {{ $errors->has('title') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="title">{{ $errors->first('title') }}</p>
                            </label>

                            <fieldset class="space-y-3">
                                <legend class="text-sm font-semibold text-slate-900">Điểm đánh giá</legend>
                                <div class="grid grid-cols-5 gap-2">
                                    @for ($rating = 1; $rating <= 5; $rating++)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating_value" value="{{ $rating }}" class="peer sr-only" @checked((string) old('rating_value', '5') === (string) $rating)>
                                            <span class="block rounded-2xl border border-orange-100 bg-orange-50 px-3 py-3 text-center text-sm font-semibold text-primary transition hover:border-orange-300 peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">
                                                <span class="block text-lg"><i class="fa-solid fa-star text-[color:var(--color-warning)]"></i></span>
                                                {{ $rating }}/5
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                                <p class="frontsite-form-field-error {{ $errors->has('rating_value') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="rating_value">{{ $errors->first('rating_value') }}</p>
                            </fieldset>

                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-900">Nội dung đánh giá</span>
                                <textarea name="content" rows="6" class="frontsite-form-control w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>{{ old('content') }}</textarea>
                                <p class="frontsite-form-field-error {{ $errors->has('content') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="content">{{ $errors->first('content') }}</p>
                            </label>

                            <p class="frontsite-form-field-error {{ $errors->has('g-recaptcha-response') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="g-recaptcha-response">{{ $errors->first('g-recaptcha-response') }}</p>

                            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_45px_-24px_rgba(255,106,0,0.78)] transition hover:bg-primary-hover">
                                <i class="fa-solid fa-paper-plane"></i>
                                Gửi đánh giá
                            </button>
                        </form>
                    </section>
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
                                class="frontsite-slider-stage"
                                data-card-carousel
                                data-desktop-slider="true"
                                style="--mobile-card-width: calc(83.333% - 0.17rem); --desktop-card-width: calc((100% - 2rem) / 3);"
                            >
                                <div class="frontsite-slider-nav">
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

            <aside class="order-1 space-y-5 lg:order-2 lg:sticky lg:top-[calc(var(--tour-sticky-header-offset)+var(--tour-section-nav-height)+1rem)] lg:self-start" data-tour-price-sidebar>
                <div class="frontsite-tour-price-panel overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white p-5 shadow-[0_30px_80px_-58px_rgba(15,23,42,0.3)] sm:p-6" data-tour-main-price-panel>
                    <div class="space-y-6">
                        @if ($activeFlashSaleOffer)
                            <div class="rounded-[1.25rem] bg-[linear-gradient(145deg,#E65F00_0%,#C2410C_58%,#9A3412_100%)] p-4 text-white shadow-[0_18px_40px_-28px_rgba(154,52,18,0.72)]">
                                <div class="flex items-start gap-3">
                                    <span class="text-xl text-amber-300"><i class="fa-solid fa-bolt"></i></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold">{{ data_get($activeFlashSaleOffer, 'campaign_title', 'Ưu đãi giờ chót') }}</p>
                                        <p class="mt-1 text-sm font-semibold text-white/90">Còn {{ (int) data_get($activeFlashSaleOffer, 'remaining_ticket_quantity', 0) }} vé</p>
                                        <div
                                            class="mt-2 inline-flex items-center gap-1 rounded-lg bg-white/15 px-2.5 py-1 font-mono text-sm font-bold"
                                            data-voucher-countdown
                                            data-voucher-countdown-target="{{ data_get($activeFlashSaleOffer, 'ends_at_iso') }}"
                                            data-voucher-countdown-expired="Ưu đãi đã kết thúc"
                                        >
                                            <span data-voucher-countdown-days>00</span> ngày
                                            <span data-voucher-countdown-hours>00</span>:<span data-voucher-countdown-minutes>00</span>:<span data-voucher-countdown-seconds>00</span>
                                            <span class="sr-only" data-voucher-countdown-status>Thời gian ưu đãi còn lại</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4 rounded-[1.25rem] border border-orange-100/80 bg-[linear-gradient(135deg,#fff7ed_0%,#ffffff_58%,#fff7ed_100%)] p-4" data-tour-main-price>
                            <div class="flex items-start justify-between gap-3">
                                <p class="inline-flex items-center gap-2.5 text-[1.35rem] font-bold leading-none text-slate-950">
                                    <i class="fa-solid fa-ticket text-primary" aria-hidden="true"></i>
                                    Giá:
                                </p>
                                @if ($sidebarBasePriceValue && $sidebarPriceValue && $sidebarBasePriceValue !== $sidebarPriceValue)
                                    <p class="pt-1 text-[0.8rem] font-semibold whitespace-nowrap text-slate-500 line-through">{{ $formatPrice($sidebarBasePriceValue) }} / Khách</p>
                                @endif
                            </div>

                            @if ($sidebarPriceValue)
                                <div class="flex flex-nowrap items-baseline gap-1.5 whitespace-nowrap">
                                    <p class="font-heading text-[clamp(1.125rem,6vw,1.9rem)] font-extrabold leading-none text-[color:var(--color-price)] lg:text-[1.5rem]">
                                        {{ $formatPrice($sidebarPriceValue) }}
                                    </p>
                                    <p class="shrink-0 text-[clamp(0.875rem,3.6vw,1.25rem)] font-semibold leading-none text-slate-900 lg:text-base">/ Khách</p>
                                </div>
                            @else
                                <p class="font-heading text-[1.75rem] font-extrabold leading-none text-[color:var(--color-price)]">
                                    Liên hệ
                                </p>
                            @endif
                        </div>

                        @if ($sidebarInfoItems->isNotEmpty())
                            <ul class="grid grid-cols-2 gap-x-3 gap-y-2" aria-label="Thông tin tour" data-tour-main-price-info>
                                @foreach ($sidebarInfoItems as $item)
                                    <li class="flex min-w-0 items-center gap-1.5 {{ $loop->last && $sidebarInfoItems->count() % 2 !== 0 ? 'col-span-2' : '' }}" title="{{ $item['label'] }}: {{ $item['value'] }}">
                                        <span class="inline-flex w-4 shrink-0 items-center justify-center text-[0.9rem] text-primary" data-tour-main-price-info-icon="{{ $item['icon'] }}">
                                            <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                        </span>
                                        <p class="min-w-0 text-[0.85rem] font-semibold leading-5 text-secondary">
                                            <span class="sr-only">{{ $item['label'] }}: </span>{{ $item['value'] }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
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

                            <span class="frontsite-tour-card-cta-wrap {{ $miniContactAction ? 'col-span-2 sm:col-span-1' : '' }}">
                                <button
                                    type="button"
                                    class="frontsite-tour-card-cta frontsite-tour-booking-cta frontsite-tour-price-cta inline-flex min-h-12 w-full items-center justify-center whitespace-nowrap border px-3 py-3 text-sm font-bold transition focus-visible:outline-none"
                                    data-travel-inquiry-open
                                    data-travel-inquiry-source="tour"
                                    data-travel-inquiry-tour-id="{{ $tour->id }}"
                                    data-travel-inquiry-departure-id="{{ $sidebarBookingDepartureId }}"
                                    data-travel-inquiry-flash-sale-slug="{{ $sidebarBookingFlashSaleSlug }}"
                                    data-travel-inquiry-price-type="{{ $sidebarBookingPriceType }}"
                                    data-travel-inquiry-price-label="{{ $sidebarPriceValue ? $formatPrice($sidebarPriceValue) : '' }}"
                                    data-travel-inquiry-regular-price-label="{{ $sidebarBasePriceValue ? $formatPrice($sidebarBasePriceValue) : ($sidebarPriceValue ? $formatPrice($sidebarPriceValue) : '') }}"
                                    data-travel-inquiry-flash-tickets-remaining="{{ (int) data_get($activeFlashSaleOffer, 'remaining_ticket_quantity', 0) }}"
                                    data-travel-inquiry-context="{{ $tour->title }}"
                                    data-travel-inquiry-subject="{{ $tour->title }}"
                                    data-travel-inquiry-modal-title="Thông tin đặt tour"
                                    data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                                >
                                    <span>Đặt tour</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>

                        @if ($departureGroups->isNotEmpty())
                            <a href="#tour-departures" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-primary">
                                <i class="fa-regular fa-calendar-days text-primary"></i>
                                Xem toàn bộ lịch khởi hành
                            </a>
                        @endif

                        @include('themes.haidangtravel.partials.social-share', [
                            'containerClass' => 'border-t border-slate-100 pt-5',
                            'description' => $tourShareDescription,
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
    </div>

    @include('themes.haidangtravel.partials.frontsite-gallery-lightbox')

    @include('themes.haidangtravel.partials.cta-banner', [
        'description' => $tourCtaHeading['is_visible'] ? $tourCtaHeading['description'] : '',
        'secondaryLabel' => 'Xem thêm tour',
        'secondaryUrl' => route($tour->scope->routeName()),
        'title' => $tourCtaHeading['is_visible'] ? $tourCtaHeading['title'] : '',
    ])
@endsection
