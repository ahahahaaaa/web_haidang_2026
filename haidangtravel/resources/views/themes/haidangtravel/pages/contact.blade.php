@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $hotline = trim((string) ($siteSettings->hotline ?: $siteSettings->phone));
        $phone = trim((string) $siteSettings->phone);
        $email = trim((string) $siteSettings->primary_email);
        $address = trim((string) $siteSettings->address);
        $mapEmbedUrl = \App\Support\GoogleMapsEmbedUrl::normalize($siteSettings->map_embed_url);
        $mapHeading = \App\Support\FrontsiteSectionHeadings::resolve(
            data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY),
            'contact_map',
        );
        $contactItems = collect([
            $hotline !== '' ? [
                'icon' => 'fa-solid fa-phone-volume',
                'label' => 'Hotline',
                'value' => $hotline,
                'href' => 'tel:'.preg_replace('/\s+/', '', $hotline),
            ] : null,
            ($phone !== '' && $phone !== $hotline) ? [
                'icon' => 'fa-solid fa-phone',
                'label' => 'Điện thoại',
                'value' => $phone,
                'href' => 'tel:'.preg_replace('/\s+/', '', $phone),
            ] : null,
            $email !== '' ? [
                'icon' => 'fa-regular fa-envelope',
                'label' => 'Email',
                'value' => $email,
                'href' => 'mailto:'.$email,
            ] : null,
            $address !== '' ? [
                'icon' => 'fa-solid fa-location-dot',
                'label' => 'Địa chỉ',
                'value' => $address,
                'span' => 'md:col-span-2 xl:col-span-2',
            ] : null,
        ])->filter()->values();
    @endphp

    @include('themes.haidangtravel.partials.landing-hero', [
        'breadcrumbItems' => [
            ['label' => 'Trang chủ', 'url' => route('home')],
            ['label' => $landing?->title ?: 'Liên hệ'],
        ],
        'fallbackPrimaryLabel' => $landing?->cta_primary_label ?: 'Gửi yêu cầu tư vấn',
        'fallbackPrimaryUrl' => $landing?->cta_primary_url ?: '#contact-inquiry-form',
        'fallbackSecondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nổi bật',
        'fallbackSecondaryUrl' => $landing?->cta_secondary_url ?: route('tours.domestic'),
        'fallbackTitle' => $landing?->hero_title ?: $landing?->title ?: 'Liên hệ',
        'hero' => $landingHero ?? [],
        'landing' => $landing,
    ])

    @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingHtmlWidgetBlocks ?? [],
    ])

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-3xl">
                @if (filled($siteSettings->company_name))
                    <p class="text-sm font-semibold uppercase tracking-[0.26em] text-primary">{{ $siteSettings->company_name }}</p>
                @endif
                <h2 class="frontsite-h2 mt-3">{{ $landing?->intro_title ?: 'Thông tin liên hệ' }}</h2>
                @if (filled($landing?->intro_excerpt))
                    <p class="mt-3 text-base leading-8 text-slate-600">{{ $landing->intro_excerpt }}</p>
                @endif
            </div>

            @if ($contactItems->isNotEmpty())
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($contactItems as $item)
                        <div class="rounded-[1.5rem] bg-slate-50 px-5 py-5 {{ $item['span'] ?? '' }}">
                            <span class="inline-flex size-11 items-center justify-center rounded-full bg-orange-100 text-lg text-primary">
                                <i class="{{ $item['icon'] }}"></i>
                            </span>
                            <p class="mt-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $item['label'] }}</p>

                            @if (filled($item['href'] ?? null))
                                <a href="{{ $item['href'] }}" class="mt-3 block break-words text-lg font-semibold text-slate-950 transition hover:text-primary">
                                    {{ $item['value'] }}
                                </a>
                            @else
                                <p class="mt-3 text-lg font-semibold leading-8 text-slate-950">{{ $item['value'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="contact-inquiry-form" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('themes.haidangtravel.partials.inquiry-form', [
            'contextTitle' => 'Liên hệ chung',
            'description' => 'Dùng form liên hệ chung để gửi nhu cầu tour, dịch vụ hoặc các thông tin cần hỗ trợ khác. Đội ngũ Hải Đăng Travel sẽ phản hồi theo đúng nội dung bạn để lại.',
            'source' => 'general',
            'subject' => 'Liên hệ chung',
            'title' => 'Form liên hệ',
        ])
    </section>

    @if ($mapEmbedUrl !== '')
        <section class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                @if ($mapHeading['is_visible'] && ($mapHeading['title'] !== '' || $mapHeading['description'] !== ''))
                    <div class="border-b border-slate-200 px-6 py-6 sm:px-8">
                        @if ($mapHeading['title'] !== '')
                            <h2 class="frontsite-h2-compact">{{ $mapHeading['title'] }}</h2>
                        @endif
                        @if ($mapHeading['description'] !== '')
                            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">{{ $mapHeading['description'] }}</p>
                        @endif
                    </div>
                @endif

                <div class="aspect-[16/9] min-h-[320px] w-full bg-slate-100">
                    <iframe
                        src="{{ $mapEmbedUrl }}"
                        title="Google Maps - {{ $siteSettings->site_name }}"
                        class="h-full w-full"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($featuredServices as $service)
                <a href="{{ route('services.show', $service) }}" class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="frontsite-h2-card">{{ $service->title }}</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $service->excerpt }}</p>
                </a>
            @endforeach
        </div>
    </section>
@endsection
