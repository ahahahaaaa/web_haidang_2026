@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $coverMedia = \App\Support\FrontsiteMedia::responsiveUrls($service, 'cover', 'cover_image_url');
        $cover = $coverMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? null;
        $coverMedium = $coverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $cover;
        $phone = trim((string) ($siteSettings->hotline ?: $siteSettings->phone));
        $phoneLink = $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact');
        $faqItems = collect($service->faq_items ?? [])
            ->filter(fn ($item) => filled(data_get($item, 'question')) && filled(data_get($item, 'answer')))
            ->values();
        $relatedQuestions = collect($service->related_questions ?? [])
            ->filter(fn ($question) => filled($question))
            ->values();
        $sectionHeadingConfig = data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY);
        $serviceQuestionsHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'service_related_questions', ['title' => $service->title]);
        $serviceHighlightsHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'service_highlights');
        $serviceFaqHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'service_faq');
        $serviceInquiryHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'service_inquiry');
        $serviceRelatedHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'service_related');
    @endphp

    <section class="relative overflow-hidden bg-[#0d1730]" id="service-hero">
        <div class="relative min-h-[68vh]">
            @if ($cover)
                <picture class="absolute inset-0 block h-full w-full">
                    @if ($coverMedium)
                        <source media="(max-width: 767px)" srcset="{{ $coverMedium }}">
                    @endif
                    <img src="{{ $cover }}" alt="{{ $service->cover_alt ?: $service->title }}" class="absolute inset-0 h-full w-full object-cover" width="1600" height="900" loading="eager" fetchpriority="high" decoding="async">
                </picture>
                <div class="absolute inset-0 bg-slate-950/65"></div>
            @else
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,106,0,0.24),_transparent_28%),radial-gradient(circle_at_left,_rgba(0,74,153,0.18),_transparent_32%),linear-gradient(135deg,_#0d1730_0%,_#12396f_48%,_#0d1730_100%)]"></div>
            @endif

            <div class="theme-grid-pattern absolute inset-0 opacity-20"></div>

            <div class="relative mx-auto flex min-h-[68vh] max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
                <div class="max-w-3xl space-y-7">
                    <div class="space-y-4">
                        <h1 class="frontsite-text-reveal font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" data-reveal="title">
                            {{ $service->title }}
                        </h1>
                        <p class="frontsite-text-reveal max-w-2xl text-lg leading-8 text-slate-200 sm:text-xl" data-reveal="body">
                            {{ $service->excerpt ?: strip_tags((string) $service->content) }}
                        </p>
                    </div>

                    <div class="frontsite-text-reveal flex flex-wrap gap-3" data-reveal="cta">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-sm bg-primary px-6 py-4 text-sm font-semibold text-white transition hover:bg-primary-hover"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="service"
                            data-travel-inquiry-service-id="{{ $service->id }}"
                            data-travel-inquiry-context="{{ $service->title }}"
                            data-travel-inquiry-subject="{{ $service->title }}"
                            data-travel-inquiry-modal-title="Thông tin đặt tour"
                            data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                        >
                            Nhận tư vấn dịch vụ
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 rounded-sm border border-white/15 bg-white/10 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/15">
                            <i class="fa-solid fa-layer-group"></i>
                            Xem tất cả dịch vụ
                        </a>
                        @if ($phone !== '')
                            <a href="{{ $phoneLink }}" class="inline-flex items-center gap-2 rounded-sm border border-white/15 bg-white/10 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/15">
                                <i class="fa-solid fa-phone-volume"></i>
                                Gọi tư vấn
                            </a>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-[1.25rem] bg-white/10 p-4 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-orange-100">Danh mục</p>
                            <p class="mt-2 font-semibold">{{ $service->category?->name ?: 'Dịch vụ du lịch' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-white/10 p-4 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-orange-100">Trạng thái</p>
                            <p class="mt-2 font-semibold">{{ $service->status === 'published' ? 'Đang hoạt động' : 'Đang cập nhật' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-white/10 p-4 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-orange-100">Ghi chú</p>
                            <p class="mt-2 font-semibold">{{ $service->price_note ?: 'Liên hệ để nhận tư vấn chi tiết' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @include('themes.haidangtravel.partials.breadcrumbs', [
                'items' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => 'Dịch vụ', 'url' => route('services.index')],
                    ...($service->category ? [['label' => $service->category->name, 'url' => $serviceCategoryUrl]] : []),
                    ['label' => $service->title],
                ],
            ])
        </div>
    </section>

    <section class="px-4 py-9 sm:px-6 lg:px-8 lg:py-11">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-8">
                @include('themes.haidangtravel.partials.geo-answer-panel', [
                    'geo' => $geo ?? [],
                    'wrap' => false,
                    'panelClasses' => 'theme-panel frontsite-text-reveal overflow-hidden p-0',
                ])

                @if ($relatedQuestions->isNotEmpty())
                    <article class="theme-panel frontsite-text-reveal p-6 sm:p-8 lg:p-10" data-reveal="copy">
                        @if ($serviceQuestionsHeading['is_visible'] && $serviceQuestionsHeading['title'] !== '')
                            <h2 class="frontsite-h2-compact">{{ $serviceQuestionsHeading['title'] }}</h2>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-3">
                            @foreach ($relatedQuestions as $question)
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                                    <i class="fa-solid fa-circle-question text-primary"></i>
                                    {{ $question }}
                                </span>
                            @endforeach
                        </div>
                    </article>
                @endif

                <article class="theme-panel frontsite-text-reveal p-6 sm:p-8 lg:p-10" data-reveal="copy">
                    <div class="theme-copy text-base leading-8 text-slate-600">
                        {!! \App\Support\RichText::render($service->content) !!}
                    </div>
                </article>

                @if (filled(data_get($service->detail_config, 'highlights')))
                    <section class="space-y-5">
                        @if ($serviceHighlightsHeading['is_visible'])
                            @include('themes.haidangtravel.partials.section-heading', [
                                'title' => $serviceHighlightsHeading['title'],
                                'description' => $serviceHighlightsHeading['description'],
                            ])
                        @endif

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach (data_get($service->detail_config, 'highlights', []) as $highlight)
                                <article class="theme-panel frontsite-text-reveal p-6" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-[color:var(--color-primary-soft)] text-primary">
                                            <i class="fa-solid fa-check text-xs"></i>
                                        </span>
                                        <p class="text-sm leading-7 text-slate-600">{{ $highlight }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($faqItems->isNotEmpty())
                    <section class="space-y-5">
                        @include('themes.haidangtravel.partials.faq-block', [
                            'accordionId' => 'service-faq',
                            'items' => $faqItems,
                            'title' => $serviceFaqHeading['is_visible'] ? $serviceFaqHeading['title'] : '',
                        ])
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                @include('themes.haidangtravel.partials.inquiry-cta-panel', [
                    'badge' => $serviceInquiryHeading['is_visible'] ? $serviceInquiryHeading['title'] : '',
                    'buttonLabel' => 'Mở form tư vấn dịch vụ',
                    'contextTitle' => $service->title,
                    'description' => 'Popup form dùng chung giúp bạn gửi yêu cầu dịch vụ ngay trên trang này với đủ thông tin đặt tour, số khách người lớn, số trẻ em và nội dung chi tiết.',
                    'modalDescription' => 'Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn.',
                    'modalTitle' => 'Thông tin đặt tour',
                    'serviceId' => $service->id,
                    'source' => 'service',
                    'subject' => $service->title,
                    'title' => $serviceInquiryHeading['is_visible'] ? $serviceInquiryHeading['title'] : '',
                ])

                @if ($relatedServices->isNotEmpty())
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        @if ($serviceRelatedHeading['is_visible'] && $serviceRelatedHeading['title'] !== '')
                            <h2 class="frontsite-h2-compact">{{ $serviceRelatedHeading['title'] }}</h2>
                        @endif
                        <div class="mt-5 space-y-4">
                            @foreach ($relatedServices as $relatedService)
                                <a href="{{ route('services.show', $relatedService) }}" class="block rounded-[1.25rem] bg-slate-50 p-4 transition hover:bg-[color:var(--color-primary-soft)]">
                                    @if ($relatedService->category?->name)
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary">{{ $relatedService->category->name }}</p>
                                    @endif
                                    <h3 class="mt-2 font-semibold text-slate-950">{{ $relatedService->title }}</h3>
                                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $relatedService->excerpt }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
