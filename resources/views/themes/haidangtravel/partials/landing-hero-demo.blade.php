@php
    $block = is_array($block ?? null) ? $block : [];
    $settings = $siteSettings ?? null;
    $heroTour = $heroTour ?? null;
    $fallbackTitle = 'Du lịch hè 2026 cùng Haidangtravel';
    $fallbackDescription = 'Trải nghiệm hành trình tinh tế cùng Haidangtravel. Lịch trình độc bản, dịch vụ thượng lưu, tận hưởng mùa hè trọn vẹn trong từng khoảnh khắc.';
    $heroTitle = trim((string) data_get($block, 'title')) ?: trim((string) ($landing?->hero_title ?: $fallbackTitle));
    $heroDescription = \App\Support\RichText::normalizePlain((string) data_get($block, 'description'));
    $heroDescription = $heroDescription !== ''
        ? $heroDescription
        : \App\Support\RichText::normalizePlain((string) ($landing?->hero_excerpt ?: $fallbackDescription));
    $primaryLabel = trim((string) data_get($block, 'primary_label')) ?: ($landing?->cta_primary_label ?: 'Gửi yêu cầu tư vấn');
    $secondaryLabel = trim((string) data_get($block, 'secondary_label')) ?: ($landing?->cta_secondary_label ?: 'Xem tour nổi bật');
    $panelTitle = trim((string) data_get($block, 'panel_title')) ?: 'Bắt đầu từ nhóm tour phù hợp nhất';
    $sectionId = trim((string) ($sectionId ?? data_get($block, 'section_id', 'landing-hero-demo')));
    $sectionId = $sectionId !== '' ? $sectionId : 'landing-hero-demo';

    $normalizeUrl = static function (?string $url, string $fallback): string {
        $url = trim((string) $url);

        if ($url === '') {
            return $fallback;
        }

        return \App\Support\FrontsiteUrls::cleanInternalUrl($url, true) ?? $url;
    };
    $secondaryUrl = $normalizeUrl(
        trim((string) data_get($block, 'secondary_url')) ?: $landing?->cta_secondary_url,
        route('tours.domestic'),
    );

    $phone = trim((string) ($phone ?? ($settings?->hotline ?: $settings?->phone)));
    $phoneLink = trim((string) ($phoneLink ?? ''));
    $phoneLink = $phoneLink !== '' ? $phoneLink : ($phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact'));
    $email = trim((string) ($email ?? $settings?->primary_email));
    $qualityLabel = trim((string) ($settings?->quality_badge_label ?: 'Hỗ trợ trước và trong chuyến đi'));

    $emptyHeroCoverMedia = [
        \App\Support\FrontsiteMedia::SIZE_SMALL => null,
        \App\Support\FrontsiteMedia::SIZE_MEDIUM => null,
        \App\Support\FrontsiteMedia::SIZE_FULL => null,
    ];
    $blockHeroCoverMedia = data_get($block, 'hero_cover_media');
    $blockHeroCoverHasMedia = is_array($blockHeroCoverMedia) && collect($blockHeroCoverMedia)->filter()->isNotEmpty();
    $heroCoverMedia = $blockHeroCoverHasMedia
        ? $blockHeroCoverMedia
        : (is_array($heroCoverMedia ?? null)
            ? $heroCoverMedia
            : ($heroTour ? \App\Support\FrontsiteMedia::responsiveUrls($heroTour, 'cover', 'cover_image_url') : $emptyHeroCoverMedia));
    $resolvedHeroCover = $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_SMALL] ?? '';
    $resolvedHeroCoverMedium = $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $heroCoverMedia[\App\Support\FrontsiteMedia::SIZE_SMALL] ?? $resolvedHeroCover;
    $heroCover = trim((string) ($blockHeroCoverHasMedia ? $resolvedHeroCover : ($heroCover ?? $resolvedHeroCover)));
    $heroCoverMedium = trim((string) ($blockHeroCoverHasMedia ? $resolvedHeroCoverMedium : ($heroCoverMedium ?? $resolvedHeroCoverMedium)));
    $heroCoverAlt = trim((string) data_get($block, 'hero_cover_alt'))
        ?: ($heroTour ? trim((string) ($heroTour->cover_alt ?: $heroTour->title)) : $heroTitle);

    $scopeCards = collect($scopeCards ?? [])
        ->filter(fn ($card) => is_array($card))
        ->map(fn (array $card) => [
            'count' => (int) ($card['count'] ?? 0),
            'label' => trim((string) ($card['label'] ?? '')) ?: 'Xem nhóm tour',
            'url' => \App\Support\FrontsiteUrls::cleanInternalUrl(trim((string) ($card['url'] ?? ''))) ?? '#',
        ])
        ->values();

    if ($scopeCards->isEmpty()) {
        $scopeCards = collect([
            ['count' => 0, 'label' => 'Xem tour trong nước', 'url' => route('tours.domestic')],
            ['count' => 0, 'label' => 'Xem tour nước ngoài', 'url' => route('tours.international')],
            ['count' => 0, 'label' => 'Xem tour đoàn', 'url' => route('tours.group')],
        ]);
    }
@endphp

<section class="relative overflow-hidden bg-secondary" id="{{ $sectionId }}">
    @if ($heroCover !== '')
        <picture class="absolute inset-0 block h-full w-full">
            @if ($heroCoverMedium !== '')
                <source media="(max-width: 767px)" srcset="{{ $heroCoverMedium }}">
            @endif
            <img
                src="{{ $heroCover }}"
                alt="{{ $heroCoverAlt }}"
                class="absolute inset-0 h-full w-full object-cover"
                width="1600"
                height="900"
                loading="eager"
                fetchpriority="high"
                decoding="async"
            >
        </picture>
        <div class="absolute inset-0 bg-[linear-gradient(108deg,_rgba(3,18,43,0.92)_0%,_rgba(0,74,153,0.76)_48%,_rgba(255,106,0,0.28)_100%)]"></div>
    @else
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,140,0,0.28),_transparent_24%),radial-gradient(circle_at_right,_rgba(255,106,0,0.16),_transparent_30%),linear-gradient(135deg,_#03122b_0%,_#004A99_50%,_#032347_100%)]"></div>
    @endif

    <div class="absolute inset-0 bg-[linear-gradient(to_bottom,_rgba(15,23,42,0.05),_rgba(15,23,42,0.55))]"></div>

    <div class="relative mx-auto grid min-h-[calc(100svh-5rem)] max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_24rem] lg:items-center lg:px-8 lg:py-10">
        <div class="max-w-4xl space-y-8">
            <div class="space-y-5">
                <h1 class="frontsite-text-reveal max-w-4xl font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" data-reveal="title">
                    {{ $heroTitle }}
                </h1>
                <p class="frontsite-text-reveal max-w-3xl text-base leading-8 text-white/85 sm:text-lg lg:text-xl" data-reveal="body">
                    {{ $heroDescription }}
                </p>
            </div>

            <div class="frontsite-text-reveal flex flex-wrap gap-3 sm:gap-4" data-reveal="cta">
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover sm:px-8 sm:text-base"
                    data-travel-inquiry-open
                    data-travel-inquiry-source="general"
                    data-travel-inquiry-context="{{ $heroTitle ?: 'Liên hệ chung' }}"
                    data-travel-inquiry-subject="{{ $heroTitle ?: 'Tư vấn du lịch' }}"
                    data-travel-inquiry-modal-title="Thông tin đặt tour"
                    data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                >
                    {{ $primaryLabel }}
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
                <a href="{{ $secondaryUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/16 sm:px-8 sm:text-base">
                    {{ $secondaryLabel }}
                </a>
            </div>

            <div class="frontsite-text-reveal flex flex-wrap gap-3 text-sm text-white/80" data-reveal="meta">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 backdrop-blur">
                    <i class="fa-solid fa-phone-volume text-orange-200"></i>
                    {{ $phone !== '' ? $phone : 'Liên hệ tư vấn' }}
                </span>
                @if ($email !== '')
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 backdrop-blur">
                        <i class="fa-solid fa-envelope text-orange-200"></i>
                        {{ $email }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 backdrop-blur">
                    <i class="fa-solid fa-shield-heart text-orange-200"></i>
                    {{ $qualityLabel }}
                </span>
            </div>
        </div>

        <aside class="frontsite-text-reveal rounded-[2rem] border border-white/12 bg-white/10 p-5 text-white shadow-[0_28px_80px_-40px_rgba(15,23,42,0.65)] backdrop-blur-xl sm:p-6" data-reveal="panel">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="frontsite-h2-compact frontsite-h2-inverse">{{ $panelTitle }}</h2>
                </div>
                <a href="{{ $phoneLink }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full bg-white/12 text-white transition hover:bg-white/18" aria-label="Gọi hotline tư vấn">
                    <i class="fa-solid fa-phone-volume"></i>
                </a>
            </div>

            <div class="mt-6 grid gap-3">
                @foreach ($scopeCards as $card)
                    <a href="{{ $card['url'] }}" class="rounded-[1.35rem] border border-white/12 bg-white/8 px-4 py-4 transition hover:bg-white/14">
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white/80">{{ $card['count'] }} tour nổi bật</span>
                        </div>
                        <p class="text-base font-semibold leading-7 text-white">{{ $card['label'] }}</p>
                    </a>
                @endforeach
            </div>

            @if ($heroTour)
                <div class="mt-6 rounded-[1.5rem] border border-white/12 bg-slate-950/20 p-5">
                    <h3 class="font-heading text-xl font-bold leading-tight">{{ $heroTour->title }}</h3>
                    <div class="mt-4 grid gap-2 text-sm text-white/80">
                        <div class="flex items-center justify-between gap-3">
                            <span>Khởi hành</span>
                            <span class="font-semibold text-white">{{ $heroTour->departure_location ?: 'Liên hệ' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Thời lượng</span>
                            <span class="font-semibold text-white">{{ $heroTour->duration_days ?: '?' }} ngày {{ $heroTour->duration_nights ?: '0' }} đêm</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Giá</span>
                            <span class="font-semibold text-orange-100">
                                @if ($heroTour->sale_price)
                                    {{ number_format($heroTour->sale_price, 0, ',', '.') }} đ
                                @else
                                    Liên hệ
                                @endif
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('tours.show', $heroTour) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-white">
                        Xem chi tiết tour
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endif
        </aside>
    </div>
</section>
