@extends('themes.haidangtravel.layouts.app')

@section('content')
    <section class="relative overflow-hidden bg-[#0d1730]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,106,0,0.24),_transparent_28%),radial-gradient(circle_at_left,_rgba(0,74,153,0.18),_transparent_32%),linear-gradient(135deg,_#0d1730_0%,_#12396f_48%,_#0d1730_100%)]"></div>
        <div class="theme-grid-pattern absolute inset-0 opacity-20"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-9 sm:px-6 lg:px-8 lg:py-12">
            @if ($isPreview)
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-orange-100">
                    <i class="fa-solid fa-eye"></i>
                    Chế độ xem trước frontsite cho SEO page này
                </div>
            @endif

            <div class="max-w-4xl space-y-7">
                <div class="space-y-4">
                    <h1 class="frontsite-text-reveal font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" data-reveal="title">
                        {{ $page->h1 ?: $page->title }}
                    </h1>
                    <p class="frontsite-text-reveal max-w-3xl text-lg leading-8 text-slate-200 sm:text-xl" data-reveal="body">
                        {{ $summary }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($quickFacts as $fact)
                        <div class="rounded-[1.25rem] bg-white/10 p-4 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-orange-100">{{ $fact['label'] }}</p>
                            <p class="mt-2 font-semibold">{{ $fact['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @include('themes.haidangtravel.partials.breadcrumbs', ['items' => $breadcrumbItems])
        </div>
    </section>

    <section class="px-4 py-9 sm:px-6 lg:px-8 lg:py-11">
        <div class="mx-auto max-w-7xl grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-8">
                <section class="theme-panel frontsite-text-reveal p-6 sm:p-8 lg:p-10" data-reveal="copy" data-ai-summary>
                    <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        <span class="rounded-sm bg-[color:var(--color-primary-soft)] px-3 py-1 text-primary">{{ $pageTypeLabel }}</span>
                        @if ($focusLabel)
                            <span>{{ $focusLabel }}</span>
                        @endif
                        @if ($summaryUpdatedLabel)
                            <span>{{ $summaryUpdatedLabel }}</span>
                        @endif
                    </div>

                    @if ($summaryBullets !== [])
                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            @foreach ($summaryBullets as $bullet)
                                <div class="rounded-sm bg-slate-50 px-5 py-4 text-sm leading-7 text-slate-600">
                                    {{ $bullet }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($summaryLinks !== [])
                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            @foreach ($summaryLinks as $link)
                                <a href="{{ $link['url'] }}" class="block rounded-sm border border-slate-200 bg-white px-5 py-4 transition hover:border-primary/30 hover:bg-[color:var(--color-primary-soft)]">
                                    <p class="font-semibold text-slate-950">{{ $link['label'] }}</p>
                                    @if (! empty($link['excerpt']))
                                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $link['excerpt'] }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                @if ($isHubPage && $hubFocusCards !== [])
                    <section class="space-y-5">
                        @include('themes.haidangtravel.partials.section-heading', [
                            'eyebrow' => 'SEO hub',
                            'title' => $hubChecklistTitle,
                            'description' => 'Các travel hub page chỉ thực sự mạnh khi vừa giải thích rõ intent vừa dẫn tiếp sang đúng các lựa chọn tour hoặc dịch vụ liên quan.',
                        ])

                        <div class="grid gap-4 lg:grid-cols-3">
                            @foreach ($hubFocusCards as $card)
                                <article class="theme-panel frontsite-text-reveal p-6" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}">
                                    <h2 class="frontsite-h2-card">{{ $card['title'] }}</h2>
                                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $card['text'] }}</p>
                                </article>
                            @endforeach
                        </div>

                        @if ($hubChecklist !== [])
                            <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel">
                                <h3 class="font-heading text-2xl font-bold text-slate-950">Checklist triển khai travel hub</h3>
                                <ul class="mt-5 space-y-4">
                                    @foreach ($hubChecklist as $item)
                                        <li class="flex items-start gap-3 text-sm leading-7 text-slate-600">
                                            <span class="mt-2 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-[color:var(--color-success-soft)] text-[11px] text-success">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                @endif

                <article class="theme-panel frontsite-text-reveal p-6 sm:p-8 lg:p-10" data-reveal="copy">
                    <div class="theme-copy text-base leading-8 text-slate-600">
                        {!! $renderedContent !!}
                    </div>
                </article>

                @if ($faqItems !== [])
                    <section class="space-y-5">
                        @include('themes.haidangtravel.partials.faq-block', [
                            'accordionId' => 'seo-faq',
                            'items' => $faqItems,
                            'title' => 'Câu hỏi thường gặp',
                        ])
                    </section>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                @if ($isContactPage)
                    <div class="rounded-sm bg-secondary p-8 text-white shadow-2xl shadow-slate-900/20">
                        <h2 class="frontsite-h2-compact frontsite-h2-inverse">Kênh liên hệ phù hợp cho từng nhu cầu</h2>
                        <div class="mt-6 space-y-4">
                            @foreach ($contactChannels as $channel)
                                <div class="rounded-sm bg-white/10 p-5">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-300">{{ $channel['label'] }}</p>
                                    @if (! empty($channel['href']))
                                        <a href="{{ $channel['href'] }}" class="mt-2 block text-lg font-semibold">{{ $channel['value'] }}</a>
                                    @else
                                        <p class="mt-2 text-lg font-semibold">{{ $channel['value'] }}</p>
                                    @endif
                                    <p class="mt-2 text-sm leading-7 text-slate-200">{{ $channel['note'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel">
                        <h2 class="frontsite-h2-compact">Chuẩn bị brief trước khi gửi yêu cầu</h2>
                        <ul class="mt-5 space-y-4">
                            @foreach ($contactChecklist as $item)
                                <li class="flex items-start gap-3 text-sm leading-7 text-slate-600">
                                    <span class="mt-2 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-[color:var(--color-success-soft)] text-[11px] text-success">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel">
                        <h2 class="frontsite-h2-compact">Quy trình hỗ trợ sau khi nhận yêu cầu</h2>
                        <div class="mt-5 space-y-4">
                            @foreach ($contactSteps as $step)
                                <div class="rounded-sm bg-slate-50 p-5">
                                    <h3 class="font-semibold text-slate-950">{{ $step['title'] }}</h3>
                                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $step['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-sm bg-secondary p-8 text-white shadow-2xl shadow-slate-900/20">
                        <h2 class="frontsite-h2-compact frontsite-h2-inverse">{{ $ctaTitle }}</h2>
                        <p class="mt-4 text-base leading-8 text-slate-200">{{ $ctaDescription }}</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover"
                                data-travel-inquiry-open
                                data-travel-inquiry-source="general"
                                data-travel-inquiry-context="{{ $ctaTitle }}"
                                data-travel-inquiry-subject="{{ $ctaTitle }}"
                                data-travel-inquiry-modal-title="Thông tin đặt tour"
                                data-travel-inquiry-modal-description="Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                            >
                                {{ $ctaPrimaryLabel }}
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <a href="{{ $ctaSecondaryUrl }}" class="inline-flex items-center gap-2 rounded-sm border border-white/15 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                                {{ $ctaSecondaryLabel }}
                            </a>
                        </div>
                    </div>
                @endif

                @if ($relatedLinks !== [])
                    <div class="theme-panel frontsite-text-reveal p-6" data-reveal="panel">
                        <h2 class="frontsite-h2-compact">Liên kết nên xem tiếp</h2>
                        <div class="mt-5 space-y-4">
                            @foreach ($relatedLinks as $link)
                                <a href="{{ $link['url'] }}" class="block rounded-sm bg-slate-50 p-4 transition hover:bg-[color:var(--color-primary-soft)]">
                                    <h3 class="font-semibold text-slate-950">{{ $link['label'] }}</h3>
                                    @if (! empty($link['excerpt']))
                                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $link['excerpt'] }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </section>

    @include('themes.haidangtravel.partials.cta-banner', [
        'description' => $ctaDescription,
        'primaryLabel' => $ctaPrimaryLabel,
        'primaryUrl' => route('contact'),
        'secondaryLabel' => $ctaSecondaryLabel,
        'secondaryUrl' => $ctaSecondaryUrl,
        'title' => $ctaTitle,
    ])
@endsection
