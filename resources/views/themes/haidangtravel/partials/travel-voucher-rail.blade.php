@php
    $voucherItems = collect($items ?? [])->filter(fn ($item) => is_array($item)
        && filled($item['code'] ?? null)
        && filled($item['claim_url'] ?? null))->values();
    $sectionId = trim((string) ($sectionId ?? 'travel-voucher-rail'));
    $sectionTitle = trim((string) ($title ?? '')) ?: 'Mã ưu đãi tặng bạn';
    $sectionDescription = trim((string) ($description ?? ''));
    $showExpiry = (bool) ($showExpiry ?? true);
    $journeyIcons = [
        'fa-solid fa-earth-asia',
        'fa-solid fa-plane',
        'fa-solid fa-train-subway',
        'fa-solid fa-mountain-sun',
        'fa-solid fa-umbrella-beach',
    ];
@endphp

@if ($voucherItems->isNotEmpty())
    <section
        class="px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
        id="{{ $sectionId }}"
        data-travel-voucher-rail
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="relative isolate overflow-hidden rounded-[2rem] border border-[#efb45f]/65 bg-[radial-gradient(circle_at_top_right,_rgba(245,194,58,0.2),_transparent_28rem),linear-gradient(145deg,rgba(255,253,247,0.98),rgba(255,246,224,0.94))] p-5 pb-4 shadow-[0_28px_75px_-58px_rgba(196,106,22,0.58)] sm:p-7 sm:pb-4 lg:p-8 lg:pb-4"
                data-card-carousel
                data-autoplay="true"
                data-desktop-slider="true"
                data-interval="4800"
                style="--mobile-card-width: 80%; --tablet-card-width: calc((100% - 1rem) / 2.18); --desktop-card-width: calc((100% - 2rem) / 3); --desktop-columns: 3;"
            >
            <div class="pointer-events-none absolute inset-0 z-0 opacity-55 [background-image:radial-gradient(circle,_rgba(242,138,16,0.13)_1px,_transparent_1.5px)] [background-size:18px_18px] [mask-image:linear-gradient(180deg,#000_0%,transparent_82%)]" aria-hidden="true"></div>

            <img
                src="{{ asset('images/voucher/voucher-premium-plane.png') }}"
                alt=""
                class="pointer-events-none absolute -right-24 -top-24 w-[34rem] max-w-none rotate-[-8deg] opacity-[0.08]"
                width="833"
                height="433"
                loading="lazy"
                decoding="async"
                aria-hidden="true"
            >

            <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex max-w-4xl items-start gap-3 sm:gap-4">
                    <span class="mt-0.5 h-12 w-1 shrink-0 rounded-full bg-[linear-gradient(180deg,#f28a10,#f5c23a)]" aria-hidden="true"></span>
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#f27600,#ffad28)] text-white shadow-[0_14px_30px_-18px_rgba(242,118,0,0.8)] sm:size-12" aria-hidden="true">
                        <i class="fa-solid fa-crown text-lg sm:text-xl"></i>
                    </span>
                    <div>
                        <h2 class="frontsite-text-reveal font-heading text-2xl font-black tracking-tight text-[#9a5315] sm:text-3xl" data-reveal="title">
                            {{ $sectionTitle }}
                        </h2>

                        @if ($sectionDescription !== '')
                            <p class="frontsite-text-reveal mt-2 text-sm leading-6 text-slate-600 sm:text-base sm:leading-7" data-reveal="body">
                                {{ $sectionDescription }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="hidden items-center gap-1.5 md:flex" aria-hidden="true">
                    @foreach ($journeyIcons as $journeyIcon)
                        <span class="flex size-8 items-center justify-center rounded-full border border-[#efb45f]/55 bg-[#fff8e6] text-[#c66a16] shadow-sm">
                            <i class="{{ $journeyIcon }} text-xs"></i>
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="frontsite-slider-stage relative z-10 mt-6">
                @if ($voucherItems->count() > 1)
                    <div class="frontsite-slider-nav voucher-rail-nav">
                        <button
                            type="button"
                            data-card-carousel-prev
                            class="service-card-carousel-control voucher-rail-carousel-control"
                            aria-label="Xem mã ưu đãi trước"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <button
                            type="button"
                            data-card-carousel-next
                            class="service-card-carousel-control voucher-rail-carousel-control"
                            aria-label="Xem mã ưu đãi tiếp theo"
                        >
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                @endif
                <div class="service-card-carousel-track" data-card-carousel-track aria-label="Danh sách mã ưu đãi du lịch">
                    @foreach ($voucherItems as $voucher)
                        @php
                            $voucherCode = trim((string) ($voucher['code'] ?? ''));
                            $voucherTitle = trim((string) ($voucher['title'] ?? '')) ?: 'Voucher du lịch';
                            $voucherDescription = trim((string) ($voucher['description'] ?? ''));
                            $voucherTerms = trim((string) ($voucher['terms'] ?? ''));
                            $validUntilLabel = trim((string) ($voucher['valid_until_label'] ?? ''));
                            $claimUrl = trim((string) ($voucher['claim_url'] ?? ''));
                            $offerValue = '';

                            foreach ([$voucherTitle, $voucherDescription] as $offerSource) {
                                if (preg_match('/(\d+(?:[.,]\d+)*\s*(?:%|k|triệu|đ))/iu', $offerSource, $offerMatches) === 1) {
                                    $offerValue = trim((string) ($offerMatches[1] ?? ''));
                                    break;
                                }
                            }

                            $offerValue = $offerValue !== '' ? $offerValue : 'Ưu đãi';
                            $offerContext = trim((string) preg_replace('/\d+(?:[.,]\d+)*\s*(?:%|k|triệu|đ)/iu', '', $voucherTitle), " -");
                            $offerContext = $offerContext !== '' ? $offerContext : 'Dành cho hành trình của bạn';
                            $voucherTone = [
                                'background: linear-gradient(145deg, #f05a28 0%, #ff7a1a 100%);',
                                'background: linear-gradient(145deg, #f98a1f 0%, #fdbb2d 100%);',
                                'background: linear-gradient(145deg, #ef5b35 0%, #ff8a3d 100%);',
                            ][$loop->index % 3];
                        @endphp

                        <div class="service-card-carousel-item" data-card-carousel-item>
                            <article
                                class="frontsite-text-reveal relative flex h-full flex-col overflow-hidden rounded-[1.15rem] border border-orange-100 bg-white shadow-[0_18px_42px_-32px_rgba(201,91,18,0.45)] transition duration-200 hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-[0_24px_48px_-30px_rgba(201,91,18,0.52)] xl:grid xl:grid-cols-[minmax(0,0.88fr)_minmax(0,1.12fr)]"
                                data-voucher-card-layout="split"
                                data-voucher-offer-value="{{ $offerValue }}"
                                data-reveal="card"
                                data-reveal-delay="{{ number_format(($loop->index % 3) * 0.08, 2, '.', '') }}"
                            >
                                <div class="relative flex min-h-[8.75rem] flex-col justify-between p-4 text-white xl:min-h-full xl:p-5" style="{{ $voucherTone }}">
                                    <i class="fa-solid fa-plane-departure pointer-events-none absolute -bottom-4 -right-3 rotate-[-10deg] text-[5.5rem] text-white/10" aria-hidden="true"></i>

                                    <span class="relative w-fit rounded-full border border-white/25 bg-white/12 px-2.5 py-1 text-[0.62rem] font-bold uppercase tracking-[0.08em] text-white">
                                        Voucher ưu đãi
                                    </span>

                                    <h3 class="relative mt-4 min-w-0 font-heading">
                                        <span class="block text-2xl font-black leading-none tracking-tight sm:text-[1.7rem]">
                                            {{ $offerValue }}
                                        </span>
                                        <span class="mt-2 block line-clamp-2 text-xs font-semibold leading-4 text-white/90 sm:text-sm xl:text-xs 2xl:text-sm">
                                            {{ $offerContext }}
                                        </span>
                                    </h3>

                                    <span class="pointer-events-none absolute -bottom-2.5 -left-2.5 size-5 rounded-full bg-white xl:-right-2.5 xl:-top-2.5 xl:bottom-auto xl:left-auto" aria-hidden="true"></span>
                                    <span class="pointer-events-none absolute -bottom-2.5 -right-2.5 size-5 rounded-full bg-white xl:-bottom-2.5 xl:-right-2.5" aria-hidden="true"></span>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col bg-white">
                                    <div class="flex flex-1 flex-col gap-3 px-4 py-4 xl:gap-2.5 xl:px-3.5 xl:py-3.5 2xl:px-4 2xl:py-4">
                                        @if ($voucherTerms !== '')
                                            <div class="flex min-w-0 items-start gap-2.5">
                                                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-[#f27600]" aria-hidden="true">
                                                    <i class="fa-solid fa-map-location-dot text-xs"></i>
                                                </span>
                                                <p class="line-clamp-2 text-xs font-medium leading-5 text-slate-700 xl:text-[0.7rem] xl:leading-4 2xl:text-xs 2xl:leading-5">
                                                    {{ $voucherTerms }}
                                                </p>
                                            </div>
                                        @endif

                                        @if ($voucherDescription !== '')
                                            <div class="flex min-w-0 items-start gap-2.5">
                                                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-[#f27600]" aria-hidden="true">
                                                    <i class="fa-solid fa-circle-info text-xs"></i>
                                                </span>
                                                <p class="line-clamp-2 text-xs leading-5 text-slate-600 xl:text-[0.7rem] xl:leading-4 2xl:text-xs 2xl:leading-5">
                                                    {{ $voucherDescription }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex min-w-0 items-center justify-between gap-2 border-t border-orange-100 px-4 py-3 xl:px-3.5 2xl:px-4">
                                        <div class="min-w-0">
                                            @if ($showExpiry && $validUntilLabel !== '')
                                                <p class="text-[0.62rem] font-medium uppercase tracking-[0.04em] text-slate-500">
                                                    HSD: {{ $validUntilLabel }}
                                                </p>
                                            @endif
                                            <p class="mt-0.5 truncate font-mono text-[0.68rem] font-bold text-[#9a5315]" title="{{ $voucherCode }}">
                                                {{ $voucherCode }}
                                            </p>
                                        </div>

                                        <a
                                            href="{{ $claimUrl }}"
                                            class="inline-flex min-h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-[#f2a650] bg-white px-2.5 py-2 text-[0.68rem] font-bold text-[#b35b0e] transition hover:border-[#f27600] hover:bg-orange-50 active:translate-y-px focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#f28a10] focus-visible:ring-offset-2"
                                            data-voucher-claim-link
                                            aria-label="Nhận voucher {{ $voucherTitle }}"
                                        >
                                            <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                                            <span>Nhận voucher</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
            </div>
        </div>
    </section>
@endif
