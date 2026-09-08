@extends('themes.haidangtravel.layouts.app')

@section('content')
    @include('themes.haidangtravel.partials.landing-hero', [
        'breadcrumbItems' => [
            ['label' => 'Trang chủ', 'url' => route('home')],
            ['label' => $landing?->title ?: 'Về chúng tôi'],
        ],
        'fallbackPrimaryLabel' => $landing?->cta_primary_label ?: 'Liên hệ ngay',
        'fallbackPrimaryUrl' => $landing?->cta_primary_url ?: route('contact'),
        'fallbackSecondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour đoàn',
        'fallbackSecondaryUrl' => $landing?->cta_secondary_url ?: route('tours.group'),
        'fallbackTitle' => $landing?->hero_title ?: $landing?->title ?: 'Về chúng tôi',
        'hero' => $landingHero ?? [],
        'landing' => $landing,
    ])

    @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])

    @php
        $aboutLandingContentBlocks = collect($landingContentBlocks ?? ($landingHtmlWidgetBlocks ?? []))->values();
        $aboutHasStructuredContentBlocks = $aboutLandingContentBlocks
            ->reject(fn (array $block): bool => ($block['type'] ?? null) === \App\Support\LandingPageBlocks::TYPE_HTML_WIDGET)
            ->isNotEmpty();
    @endphp

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $aboutLandingContentBlocks,
    ])

    @if (! $aboutHasStructuredContentBlocks)
        <section class="mx-auto max-w-7xl px-4 pb-5 pt-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    @if (filled($landing?->intro_title))
                        <h2 class="frontsite-h2-compact">{{ $landing?->intro_title }}</h2>
                    @endif
                    <p class="mt-4 text-base leading-8 text-slate-600">{{ $landing?->intro_excerpt }}</p>
                    <div class="theme-copy mt-6 text-base leading-8 text-slate-600">{!! \App\Support\RichText::render($landing?->body) !!}</div>
                </div>

                <div class="rounded-[2rem] bg-slate-950 p-8 text-white">
                    <h2 class="frontsite-h2-compact frontsite-h2-inverse">{{ $landing?->cta_title ?: 'Cần đội ngũ Hải Đăng Travel đồng hành?' }}</h2>
                    <p class="mt-4 text-base leading-8 text-slate-300">{{ $landing?->cta_excerpt ?: 'Chúng tôi có thể tư vấn theo nhu cầu gia đình, doanh nghiệp hoặc hành trình riêng cần tối ưu trải nghiệm và chi phí.' }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="general"
                            data-travel-inquiry-context="{{ $landing?->title ?: 'Liên hệ chung' }}"
                            data-travel-inquiry-subject="{{ $landing?->title ?: 'Tư vấn du lịch' }}"
                            data-travel-inquiry-modal-title="Thông tin đặt tour"
                            data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                        >
                            {{ $landing?->cta_primary_label ?: 'Liên hệ ngay' }}
                        </button>
                        <a href="{{ $landing?->cta_secondary_url ?: route('tours.group') }}" class="rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white">{{ $landing?->cta_secondary_label ?: 'Xem tour đoàn' }}</a>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
