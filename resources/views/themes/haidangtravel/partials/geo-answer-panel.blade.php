@php
    $geo = is_array($geo ?? null) ? $geo : [];
    $shouldRender = (bool) ($geo['is_enabled'] ?? false);
    $wrap = (bool) ($wrap ?? true);
    $sectionClasses = $sectionClasses ?? 'px-4 py-8 sm:px-6 lg:px-8';
    $containerClasses = $containerClasses ?? 'mx-auto max-w-7xl';
    $panelClasses = $panelClasses ?? 'theme-panel frontsite-text-reveal overflow-hidden p-0';
    $facts = collect($geo['facts'] ?? [])->filter(fn ($fact) => filled(data_get($fact, 'label')) && filled(data_get($fact, 'value')))->values();
    $notes = collect($geo['decision_notes'] ?? [])->filter()->values();
    $links = collect($geo['links'] ?? [])->filter(fn ($link) => filled(data_get($link, 'label')) && filled(data_get($link, 'url')))->values();
    $cta = is_array($geo['cta'] ?? null) ? $geo['cta'] : [];
@endphp

@if ($shouldRender)
    @if ($wrap)
        <section class="{{ $sectionClasses }}">
            <div class="{{ $containerClasses }}">
    @endif

    <article class="{{ $panelClasses }}" data-geo-answer-panel data-ai-summary data-reveal="panel">
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.9fr)]">
            <div class="space-y-5 p-6 sm:p-8">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-[color:var(--color-primary-soft)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-primary">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        GEO / AI Search
                    </span>
                    @if (filled($geo['updated_label'] ?? null))
                        <span class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $geo['updated_label'] }}</span>
                    @endif
                </div>

                <div class="space-y-3">
                    <h2 class="frontsite-h2-compact">{{ $geo['title'] ?? 'Tóm tắt nhanh' }}</h2>
                    @if (filled($geo['summary'] ?? null))
                        <p class="text-sm leading-7 text-slate-600 sm:text-base sm:leading-8">{{ $geo['summary'] }}</p>
                    @endif
                </div>

                @if ($notes->isNotEmpty())
                    <ul class="grid gap-3 text-sm leading-7 text-slate-600">
                        @foreach ($notes as $note)
                            <li class="flex gap-3">
                                <span class="mt-2 inline-flex size-2 shrink-0 rounded-full bg-primary"></span>
                                <span>{{ $note }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($links->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($links as $link)
                            <a href="{{ $link['url'] }}" class="inline-flex items-center gap-2 rounded-sm border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)] hover:text-primary">
                                {{ $link['label'] }}
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($facts->isNotEmpty() || filled($cta['label'] ?? null))
                <div class="border-t border-slate-100 bg-slate-50/80 p-6 sm:p-8 lg:border-l lg:border-t-0">
                    @if ($facts->isNotEmpty())
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            @foreach ($facts as $fact)
                                <div class="rounded-sm bg-white px-5 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $fact['label'] }}</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-900">{{ $fact['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (filled($cta['label'] ?? null))
                        @if ((bool) ($cta['modal'] ?? false))
                            <button
                                type="button"
                                class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover"
                                data-travel-inquiry-open
                                data-travel-inquiry-source="{{ $cta['source'] ?? 'geo' }}"
                                @if (filled($cta['tour_id'] ?? null)) data-travel-inquiry-tour-id="{{ $cta['tour_id'] }}" @endif
                                @if (filled($cta['service_id'] ?? null)) data-travel-inquiry-service-id="{{ $cta['service_id'] }}" @endif
                                data-travel-inquiry-context="{{ $cta['context'] ?? ($geo['title'] ?? '') }}"
                                data-travel-inquiry-subject="{{ $cta['subject'] ?? ($geo['title'] ?? '') }}"
                                data-travel-inquiry-modal-title="Thông tin đặt tour"
                                data-travel-inquiry-modal-description="Điền nhanh thông tin để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn."
                            >
                                {{ $cta['label'] }}
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        @else
                            <a href="{{ $cta['url'] ?? route('contact') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover">
                                {{ $cta['label'] }}
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </article>

    @if ($wrap)
            </div>
        </section>
    @endif
@endif
