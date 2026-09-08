@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $serviceCategoryContent = trim((string) ($selectedServiceCategory?->content ?? ''));
    @endphp

    @include('themes.haidangtravel.partials.landing-hero', [
        'breadcrumbItems' => [
            ['label' => 'Trang chủ', 'url' => route('home')],
            ['label' => $landing?->title ?: 'Dịch vụ', 'url' => route('services.index')],
            ...($selectedServiceCategory ? [['label' => $selectedServiceCategory->name]] : []),
        ],
        'fallbackPrimaryLabel' => $landing?->cta_primary_label ?: 'Liên hệ tư vấn',
        'fallbackPrimaryUrl' => $landing?->cta_primary_url ?: route('contact'),
        'fallbackSecondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nổi bật',
        'fallbackSecondaryUrl' => $landing?->cta_secondary_url ?: route('tours.domestic'),
        'fallbackEyebrow' => $selectedServiceCategory ? 'Danh mục dịch vụ' : null,
        'fallbackTitle' => $selectedServiceCategory ? $selectedServiceCategory->name : ($landing?->hero_title ?: $landing?->title ?: 'Dịch vụ'),
        'fallbackDescription' => $selectedServiceCategory?->description ?: null,
        'hero' => $landingHero ?? [],
        'landing' => $landing,
    ])

    @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingHtmlWidgetBlocks ?? [],
    ])

    @include('themes.haidangtravel.partials.shared-search-bar', [
        'action' => $selectedServiceCategory
            ? route('service-categories.show', ['category' => $selectedServiceCategory->slug])
            : route('services.index'),
        'inputValue' => request('q'),
        'placeholder' => 'Tìm dịch vụ, visa hoặc vé máy bay...',
    ])

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($landing?->body)
            <div class="theme-panel mb-10 p-6">
                <div class="theme-copy text-sm leading-7 text-slate-600">
                    {!! \App\Support\RichText::render($landing->body) !!}
                </div>
            </div>
        @endif

        <div class="mb-10">
            @include('themes.haidangtravel.partials.geo-answer-panel', [
                'geo' => $geo ?? [],
                'wrap' => false,
            ])
        </div>

        @if ($selectedServiceCategory && $serviceCategoryContent !== '')
            <section class="theme-panel mb-10 p-6 sm:p-8">
                <div class="theme-copy text-sm leading-7 text-slate-600">
                    {!! \App\Support\RichText::render($serviceCategoryContent) !!}
                </div>
            </section>
        @endif

        @if (($serviceCategories ?? collect())->isNotEmpty())
            <div class="mb-10 flex flex-wrap gap-3">
                <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $selectedServiceCategory ? 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' : 'border-orange-200 bg-[color:var(--color-primary-soft)] text-primary' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Tất cả dịch vụ</span>
                </a>

                @foreach ($serviceCategories as $category)
                    <a href="{{ route('service-categories.show', ['category' => $category->slug]) }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ ($selectedServiceCategory?->slug === $category->slug) ? 'border-orange-200 bg-[color:var(--color-primary-soft)] text-primary' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-primary' }}">
                        <i class="fa-solid fa-briefcase"></i>
                        <span>{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($services->isNotEmpty())
            <div class="mt-12 grid gap-5 lg:grid-cols-2 xl:grid-cols-4">
                @foreach ($services as $service)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        @if ($service->category?->name)
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary">{{ $service->category->name }}</p>
                        @endif
                        <h2 class="frontsite-h2-card mt-3">{{ $service->title }}</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $service->excerpt }}</p>
                        <p class="mt-4 text-sm font-medium text-slate-500">{{ $service->price_note }}</p>
                        <a href="{{ route('services.show', $service) }}" class="mt-6 inline-flex text-sm font-semibold text-primary" aria-label="Xem chi tiết dịch vụ {{ $service->title }}">Xem chi tiết</a>
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-12 rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <p class="text-lg font-semibold text-slate-900">Chưa có dịch vụ trong danh mục này.</p>
                <p class="mt-3 text-sm leading-7 text-slate-600">Bạn có thể quay lại danh sách tất cả dịch vụ hoặc chọn một danh mục khác để tiếp tục xem.</p>
            </div>
        @endif
    </section>
@endsection
