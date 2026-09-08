@php
    $block = $block ?? [];
    $landingPage = $landing ?? null;
    $campaign = $voucherCampaign ?? null;
    $blockType = (string) ($block['type'] ?? \App\Support\LandingPageBlocks::TYPE_VOUCHER_PROMOTION);
    $requestedVariant = trim((string) request()->query('voucher_variant', ''));
    $configuredVariant = trim((string) ($block['variant'] ?? ''));
    $hasRequestedVariant = array_key_exists($requestedVariant, \App\Support\LandingPageBlocks::voucherPromotionVariants());
    $forcePremiumVariant = $landingPage?->slug === 'voucher-du-lich';
    if ($forcePremiumVariant && ! $hasRequestedVariant && $configuredVariant !== \App\Support\LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM) {
        $premiumDefaultBlock = \App\Support\LandingPageBlocks::defaultBlock(\App\Support\LandingPageBlocks::TYPE_VOUCHER_PROMOTION_PREMIUM);
        $premiumCopyKeys = [
            'badge_icon',
            'badge_label',
            'benefits',
            'countdown_label',
            'description',
            'inquiry_context',
            'inquiry_subject',
            'kicker',
            'modal_description',
            'modal_title',
            'offer_code',
            'offer_label',
            'offer_note',
            'panel_description',
            'panel_eyebrow',
            'panel_title',
            'primary_label',
            'secondary_label',
            'secondary_url',
            'show_countdown',
            'steps',
            'tag_label',
            'title_highlight',
            'title_prefix',
            'title_suffix',
            'trust_note',
        ];

        foreach ($premiumCopyKeys as $premiumCopyKey) {
            $block[$premiumCopyKey] = $premiumDefaultBlock[$premiumCopyKey] ?? null;
        }

        $configuredVariant = \App\Support\LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM;
    }
    $fallbackVariant = $blockType === \App\Support\LandingPageBlocks::TYPE_VOUCHER_PROMOTION_PREMIUM
        ? \App\Support\LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM
        : \App\Support\LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_CLASSIC;
    $variant = $hasRequestedVariant
        ? $requestedVariant
        : ($forcePremiumVariant
            ? \App\Support\LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM
            : (array_key_exists($configuredVariant, \App\Support\LandingPageBlocks::voucherPromotionVariants())
            ? $configuredVariant
            : $fallbackVariant));
    $isPremiumVariant = $variant === \App\Support\LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM;
    $campaignSlug = trim((string) ($block['voucher_campaign_slug'] ?? '')) ?: (string) ($voucherCampaignSlug ?? $campaign?->slug ?? '');
    $countdownIso = (bool) ($block['show_countdown'] ?? true) && $campaign?->ends_at
        ? $campaign->ends_at->toIso8601String()
        : null;
    $voucherValidUntilLabel = $campaign?->code_valid_until?->format('d/m/Y');
    $benefits = collect($block['benefits'] ?? [])
        ->filter(fn ($benefit) => is_array($benefit) && trim((string) ($benefit['text'] ?? '')) !== '')
        ->values();
    $steps = collect($block['steps'] ?? [])
        ->filter(fn ($step) => is_array($step) && (trim((string) ($step['title'] ?? '')) !== '' || trim((string) ($step['text'] ?? '')) !== ''))
        ->values();
    $badgeIcon = trim((string) ($block['badge_icon'] ?? 'fa-solid fa-gift')) ?: 'fa-solid fa-gift';
    $titlePrefix = trim((string) ($block['title_prefix'] ?? ''));
    $titleHighlight = trim((string) ($block['title_highlight'] ?? ''));
    $titleSuffix = trim((string) ($block['title_suffix'] ?? ''));
    $panelTitle = trim((string) ($block['panel_title'] ?? ''));
    $trustNote = trim((string) ($block['trust_note'] ?? ''));
    $premiumStepIcons = [
        'fa-solid fa-headset',
        'fa-solid fa-user-group',
        'fa-solid fa-ticket',
    ];
    $useVoucherLandingReferenceCopy = $isPremiumVariant && $forcePremiumVariant && ! $hasRequestedVariant;
    $premiumBadgeLabel = $useVoucherLandingReferenceCopy
        ? 'Nhận voucher miễn phí 200.000đ'
        : trim((string) ($block['badge_label'] ?? 'Nhận voucher miễn phí 200.000đ'));
    $premiumIntroTitle = $useVoucherLandingReferenceCopy
        ? 'Áp dụng tất cả tour trong & ngoài nước'
        : trim(implode(' ', array_filter([$titlePrefix, $titleHighlight, $titleSuffix])));
    $premiumIntroNote = $useVoucherLandingReferenceCopy
        ? 'Không giới hạn điểm đến - Hưởng ưu đãi ngay hôm nay'
        : 'Không giới hạn điểm đến - Hưởng ưu đãi ngay hôm nay';
    $premiumOfferAmount = $useVoucherLandingReferenceCopy
        ? '200.000đ'
        : ($titleHighlight !== '' ? $titleHighlight : '200.000đ');
    $premiumOfferPill = $useVoucherLandingReferenceCopy
        ? 'Miễn phí - Cho mọi tour trong & ngoài nước'
        : (trim((string) ($block['offer_label'] ?? '')) ?: 'Miễn phí - Cho mọi tour trong & ngoài nước');
    $premiumHighlightNote = $useVoucherLandingReferenceCopy
        ? 'Áp dụng toàn bộ tour: Thái Lan, Nhật Bản, Pháp, Đà Nẵng, Phú Quốc...'
        : (trim((string) ($block['offer_note'] ?? '')) ?: 'Áp dụng toàn bộ tour trong & ngoài nước.');
    $premiumHotline = trim((string) (($siteSettings ?? null)?->hotline ?: ($siteSettings ?? null)?->phone));
    $premiumJourneyIcons = [
        'fa-solid fa-earth-asia',
        'fa-solid fa-plane',
        'fa-solid fa-train-subway',
        'fa-solid fa-mountain-sun',
        'fa-solid fa-umbrella-beach',
    ];
@endphp

@if ($isPremiumVariant)
    <section class="voucher-landing-section voucher-landing-section--premium" id="voucher-promotion" data-voucher-variant="{{ $variant }}">
        <div class="voucher-landing-shell">
            <header class="voucher-premium-intro frontsite-text-reveal" data-reveal="heading">
                <p class="voucher-premium-intro__badge">
                    <i class="{{ $badgeIcon }}" aria-hidden="true"></i>
                    {{ $premiumBadgeLabel }}
                </p>
                <h1>{{ $premiumIntroTitle }}</h1>
                <p class="voucher-premium-intro__note">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    {{ $premiumIntroNote }}
                </p>
            </header>

            <div class="voucher-poster frontsite-text-reveal" data-reveal="panel">
                <div class="voucher-card-heading">
                    <span aria-hidden="true"></span>
                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                    <h2>Quy trình nhận ưu đãi</h2>
                </div>

                @if ($steps->isNotEmpty())
                    <div class="voucher-step-grid" aria-label="Quy trình nhận voucher">
                        @foreach ($steps as $step)
                            @php
                                $stepIcon = trim((string) ($step['icon'] ?? '')) ?: ($premiumStepIcons[$loop->index] ?? 'fa-solid fa-circle-check');
                            @endphp
                            <div>
                                <div class="voucher-step-grid__mark">
                                    <span>{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                </div>

                                <div class="voucher-step-grid__copy">
                                    @if (filled($step['title'] ?? null))
                                        <div class="voucher-step-grid__title">
                                            <i class="{{ $stepIcon }}" aria-hidden="true"></i>
                                            <strong>{{ $step['title'] }}</strong>
                                        </div>
                                    @endif

                                    @if (filled($step['text'] ?? null))
                                        <p>{{ $step['text'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="voucher-journey-strip" aria-hidden="true">
                    @foreach ($premiumJourneyIcons as $journeyIcon)
                        <i class="{{ $journeyIcon }}"></i>
                    @endforeach
                </div>
                <p class="voucher-journey-caption"><i class="fa-solid fa-infinity" aria-hidden="true"></i> Hành trình không giới hạn</p>
            </div>

            <div class="voucher-action-panel frontsite-text-reveal" data-reveal="panel">
                <img
                    src="{{ asset('images/voucher/voucher-premium-plane.png') }}"
                    alt=""
                    class="voucher-action-panel__plane-watermark"
                    width="833"
                    height="433"
                    loading="lazy"
                    decoding="async"
                >

                <div class="voucher-offer-card">
                    <p class="voucher-action-panel__eyebrow">
                        <i class="fa-solid fa-crown" aria-hidden="true"></i>
                        Voucher đặc quyền
                    </p>

                    <strong class="voucher-offer-card__amount">{{ $premiumOfferAmount }}</strong>
                    <p class="voucher-offer-card__pill">
                        <i class="fa-solid fa-gem" aria-hidden="true"></i>
                        {{ $premiumOfferPill }}
                    </p>

                    <div class="voucher-offer-card__highlight">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>{{ $premiumHighlightNote }}</span>
                    </div>

                    @if ($benefits->isNotEmpty())
                        <ul class="voucher-benefit-list" aria-label="Quyền lợi voucher">
                            @foreach ($benefits as $benefit)
                                @php
                                    $benefitIcon = trim((string) ($benefit['icon'] ?? 'fa-solid fa-circle-check')) ?: 'fa-solid fa-circle-check';
                                @endphp
                                <li><i class="{{ $benefitIcon }}"></i> <span>{{ $benefit['text'] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($countdownIso)
                    <div
                        class="voucher-countdown"
                        data-voucher-countdown
                        data-voucher-countdown-target="{{ $countdownIso }}"
                        data-voucher-countdown-expired="{{ $block['countdown_expired_label'] ?? 'Chương trình voucher đã kết thúc' }}"
                        aria-label="Thời gian còn lại của voucher"
                    >
                        <p class="voucher-countdown__label"><i class="fa-regular fa-hourglass-half" aria-hidden="true"></i> {{ $block['countdown_label'] ?? 'Chương trình kết thúc sau' }}</p>
                        <div class="voucher-countdown__grid">
                            <span><strong data-voucher-countdown-days>00</strong><small>Ngày</small></span>
                            <span><strong data-voucher-countdown-hours>00</strong><small>Giờ</small></span>
                            <span><strong data-voucher-countdown-minutes>00</strong><small>Phút</small></span>
                            <span><strong data-voucher-countdown-seconds>00</strong><small>Giây</small></span>
                        </div>
                        <p class="voucher-countdown__status" data-voucher-countdown-status>
                            @if ($voucherValidUntilLabel)
                                Áp dụng đến hết ngày {{ $voucherValidUntilLabel }}
                            @endif
                        </p>
                    </div>
                @endif

                <div class="voucher-action-panel__actions">
                    @if (filled($block['primary_label'] ?? null))
                        <button
                            type="button"
                            class="voucher-primary-button"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="general"
                            data-travel-inquiry-context="{{ $block['inquiry_context'] ?? '' }}"
                            data-travel-inquiry-subject="{{ $block['inquiry_subject'] ?? '' }}"
                            data-travel-inquiry-voucher-campaign="{{ $campaignSlug }}"
                            data-travel-inquiry-voucher-variant="{{ $variant }}"
                            data-travel-inquiry-modal-title="{{ $block['modal_title'] ?? '' }}"
                            data-travel-inquiry-modal-description="{{ $block['modal_description'] ?? '' }}"
                        >
                            <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                            {{ $block['primary_label'] }}
                        </button>
                    @endif

                    @if (filled($block['offer_code'] ?? null))
                        <div class="voucher-code-card" aria-label="Mã ưu đãi đặc biệt">
                            <span><i class="fa-solid fa-tag" aria-hidden="true"></i> Mã ưu đãi đặc biệt:</span>
                            <strong>{{ $block['offer_code'] }}</strong>
                            <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>

                @if ($trustNote !== '')
                    <p class="voucher-action-panel__trust-note">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        {{ $trustNote }}
                    </p>
                @endif

                @if ($premiumHotline !== '')
                    <p class="voucher-hotline-note">
                        <i class="fa-solid fa-phone-volume" aria-hidden="true"></i>
                        Hotline {{ $premiumHotline }} - Nhận voucher ngay qua tổng đài
                    </p>
                @endif
            </div>
        </div>
    </section>
@else
    <section class="voucher-landing-section voucher-landing-section--classic" id="voucher-promotion" data-voucher-variant="{{ $variant }}">
        <div class="voucher-landing-shell">
            <div class="voucher-poster frontsite-text-reveal" data-reveal="panel">
                <div class="voucher-poster__motion" aria-hidden="true">
                    <i class="fa-solid fa-plane-departure voucher-poster__motion-plane"></i>
                    <i class="fa-solid fa-train voucher-poster__motion-train"></i>
                    <span class="voucher-poster__motion-track"></span>
                </div>

                <div class="voucher-poster__topline">
                    @if (filled($block['badge_label'] ?? null))
                        <span><i class="{{ $badgeIcon }}"></i> {{ $block['badge_label'] }}</span>
                    @endif

                    @if (filled($block['tag_label'] ?? null))
                        <span>{{ $block['tag_label'] }}</span>
                    @endif
                </div>

                <div class="voucher-poster__content">
                    @if (filled($block['kicker'] ?? null))
                        <p class="voucher-poster__kicker">{{ $block['kicker'] }}</p>
                    @endif

                    <h1>
                        @if ($titlePrefix !== '')
                            {{ $titlePrefix }}
                        @endif

                        @if ($titleHighlight !== '')
                            <span>{{ $titleHighlight }}</span>
                        @endif

                        @if ($titleSuffix !== '')
                            <br>{{ $titleSuffix }}
                        @endif
                    </h1>

                    @if (filled($block['description'] ?? null))
                        <p>{{ $block['description'] }}</p>
                    @endif
                </div>

                @if ($benefits->isNotEmpty())
                    <ul class="voucher-benefit-list" aria-label="Quyền lợi voucher">
                        @foreach ($benefits as $benefit)
                            @php
                                $benefitIcon = trim((string) ($benefit['icon'] ?? 'fa-solid fa-circle-check')) ?: 'fa-solid fa-circle-check';
                            @endphp
                            <li><i class="{{ $benefitIcon }}"></i> {{ $benefit['text'] }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (filled($block['offer_label'] ?? null) || filled($block['offer_code'] ?? null) || filled($block['offer_note'] ?? null))
                    <div class="voucher-code-card" aria-label="Tóm tắt ưu đãi">
                        @if (filled($block['offer_label'] ?? null))
                            <span>{{ $block['offer_label'] }}</span>
                        @endif

                        @if (filled($block['offer_code'] ?? null))
                            <strong>{{ $block['offer_code'] }}</strong>
                        @endif

                        @if (filled($block['offer_note'] ?? null))
                            <small>{{ $block['offer_note'] }}</small>
                        @endif
                    </div>
                @endif

                @if ($countdownIso)
                    <div
                        class="voucher-countdown"
                        data-voucher-countdown
                        data-voucher-countdown-target="{{ $countdownIso }}"
                        data-voucher-countdown-expired="{{ $block['countdown_expired_label'] ?? 'Chương trình voucher đã kết thúc' }}"
                        aria-label="Thời gian còn lại của voucher"
                    >
                        <p class="voucher-countdown__label">{{ $block['countdown_label'] ?? 'Thời gian còn lại' }}</p>
                        <div class="voucher-countdown__grid">
                            <span><strong data-voucher-countdown-days>00</strong><small>Ngày</small></span>
                            <span><strong data-voucher-countdown-hours>00</strong><small>Giờ</small></span>
                            <span><strong data-voucher-countdown-minutes>00</strong><small>Phút</small></span>
                            <span><strong data-voucher-countdown-seconds>00</strong><small>Giây</small></span>
                        </div>
                        <p class="voucher-countdown__status" data-voucher-countdown-status>
                            @if ($voucherValidUntilLabel)
                                Áp dụng đến hết ngày {{ $voucherValidUntilLabel }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <div class="voucher-action-panel frontsite-text-reveal" data-reveal="panel">
                <div>
                    @if (filled($block['panel_eyebrow'] ?? null))
                        <p class="voucher-action-panel__eyebrow">{{ $block['panel_eyebrow'] }}</p>
                    @endif

                    @if ($panelTitle !== '')
                        <h2>{{ $panelTitle }}</h2>
                    @endif

                    @if (filled($block['panel_description'] ?? null))
                        <p>{{ $block['panel_description'] }}</p>
                    @endif
                </div>

                @if ($steps->isNotEmpty())
                    <div class="voucher-step-grid">
                        @foreach ($steps as $step)
                            <div>
                                <span>{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>

                                <div class="voucher-step-grid__copy">
                                    @if (filled($step['title'] ?? null))
                                        <strong>{{ $step['title'] }}</strong>
                                    @endif

                                    @if (filled($step['text'] ?? null))
                                        <p>{{ $step['text'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="voucher-action-panel__actions">
                    @if (filled($block['primary_label'] ?? null))
                        <button
                            type="button"
                            class="voucher-primary-button"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="general"
                            data-travel-inquiry-context="{{ $block['inquiry_context'] ?? '' }}"
                            data-travel-inquiry-subject="{{ $block['inquiry_subject'] ?? '' }}"
                            data-travel-inquiry-voucher-campaign="{{ $campaignSlug }}"
                            data-travel-inquiry-voucher-variant="{{ $variant }}"
                            data-travel-inquiry-modal-title="{{ $block['modal_title'] ?? '' }}"
                            data-travel-inquiry-modal-description="{{ $block['modal_description'] ?? '' }}"
                        >
                            {{ $block['primary_label'] }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    @endif

                    @if (filled($block['secondary_label'] ?? null) && filled($block['secondary_url'] ?? null))
                        <a class="voucher-secondary-button" href="{{ $block['secondary_url'] }}">
                            {{ $block['secondary_label'] }}
                        </a>
                    @endif
                </div>

                @if ($trustNote !== '')
                    <p class="voucher-action-panel__trust-note">{{ $trustNote }}</p>
                @endif
            </div>
        </div>
    </section>
@endif
