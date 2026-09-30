@php
    $title = trim((string) ($title ?? ''));
    $titleParts = preg_split('/\s+[–—-]\s+/u', $title, 2) ?: [];
    $brandTitle = trim((string) ($titleParts[0] ?? ''));
    $editorialTitle = trim((string) ($titleParts[1] ?? ''));
    $logoUrl = trim((string) ($logoUrl ?? ''));
    $logoSmallUrl = trim((string) ($logoSmallUrl ?? '')) ?: $logoUrl;
    $hasComposedTitle = $brandTitle !== '' && $editorialTitle !== '';
    $subtitle = trim((string) ($subtitle ?? ''));
    $description = trim((string) ($description ?? ''));
    $stats = collect($stats ?? [])
        ->filter(fn ($stat) => is_array($stat))
        ->map(fn (array $stat) => [
            'value' => trim((string) ($stat['value'] ?? '')),
            'label' => trim((string) ($stat['label'] ?? '')),
        ])
        ->filter(fn (array $stat) => $stat['value'] !== '' && $stat['label'] !== '')
        ->take(3)
        ->values();
    $awards = collect($awards ?? [])
        ->filter(fn ($award) => is_array($award))
        ->map(fn (array $award) => [
            'title' => trim((string) ($award['title'] ?? '')),
            'description' => trim((string) ($award['description'] ?? '')),
            'image_url' => trim((string) ($award['image_url'] ?? '')),
            'image_alt' => trim((string) ($award['image_alt'] ?? '')),
        ])
        ->filter(fn (array $award) => $award['image_url'] !== '')
        ->values();
    $sectionClasses = trim((string) ($sectionClasses ?? 'bg-slate-50 px-4 py-8 sm:px-6 lg:px-8 lg:py-12'));
    $sectionId = trim((string) ($sectionId ?? 'trust-and-proof'));
@endphp

@if ($title !== '' || $subtitle !== '' || $description !== '' || $stats->isNotEmpty() || $awards->isNotEmpty())
    <section class="{{ $sectionClasses }}" id="{{ $sectionId }}">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.85fr)] lg:items-center lg:gap-12">
            <div class="min-w-0 text-center">
                @if ($title !== '')
                    @if ($hasComposedTitle)
                        <h2 class="frontsite-text-reveal" data-reveal="title" data-composed-brand-heading>
                            <span class="sr-only">{{ $title }}</span>
                            <span class="flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-5" aria-hidden="true">
                                @if ($logoUrl !== '')
                                    <picture class="block shrink-0">
                                        @if ($logoSmallUrl !== '')
                                            <source media="(max-width: 767px)" srcset="{{ $logoSmallUrl }}">
                                        @endif
                                        <img
                                            src="{{ $logoUrl }}"
                                            alt=""
                                            class="h-14 w-auto max-w-[13rem] object-contain drop-shadow-[0_14px_28px_rgba(15,23,42,0.14)] sm:h-16 sm:max-w-[15rem] lg:h-[4.5rem] lg:max-w-[17rem]"
                                            width="272"
                                            height="72"
                                            loading="lazy"
                                            decoding="async"
                                            data-trust-brand-logo
                                        >
                                    </picture>
                                @else
                                    <span class="font-heading text-2xl font-black uppercase tracking-[-0.04em] text-primary sm:text-3xl lg:text-4xl">{{ $brandTitle }}</span>
                                @endif

                                <span class="h-px w-20 bg-gradient-to-r from-transparent via-primary/70 to-transparent sm:hidden" aria-hidden="true"></span>
                                <span class="hidden h-16 w-px bg-gradient-to-b from-transparent via-primary/70 to-transparent sm:block" aria-hidden="true"></span>

                                <span class="max-w-2xl font-editorial text-[2rem] font-bold uppercase leading-[0.94] tracking-[0.045em] text-slate-800 drop-shadow-[0_12px_24px_rgba(15,23,42,0.16)] sm:text-left sm:text-[2.45rem] lg:text-[3rem]">
                                    {{ $editorialTitle }}
                                </span>
                            </span>
                        </h2>
                    @else
                        <h2 class="frontsite-text-reveal text-2xl font-bold uppercase tracking-tight text-primary sm:text-3xl lg:text-4xl" data-reveal="title">{{ $title }}</h2>
                    @endif
                @endif

                @if ($subtitle !== '')
                    <p class="frontsite-text-reveal mt-3 text-base font-medium text-slate-600 sm:text-lg" data-reveal="body">{{ $subtitle }}</p>
                @endif

                @if ($description !== '')
                    <p class="frontsite-text-reveal mx-auto mt-4 max-w-4xl text-sm leading-7 text-slate-700 sm:text-base sm:leading-8" data-reveal="body">{{ $description }}</p>
                @endif

                @if ($stats->isNotEmpty())
                    <div class="relative mt-9">
                        <div class="pointer-events-none absolute -inset-x-6 -inset-y-8 bg-[radial-gradient(circle_at_18%_48%,_rgba(255,106,0,0.18),_transparent_28%),radial-gradient(circle_at_82%_52%,_rgba(251,146,60,0.16),_transparent_30%),linear-gradient(90deg,_transparent,_rgba(255,237,213,0.42),_transparent)] blur-2xl" aria-hidden="true"></div>

                        <div class="relative grid gap-4 sm:grid-cols-3">
                            @foreach ($stats as $stat)
                                <article class="frontsite-card-reveal group relative flex min-h-36 flex-col items-center justify-center overflow-hidden rounded-3xl border border-orange-200/70 bg-[linear-gradient(145deg,_rgba(255,255,255,0.96),_rgba(255,247,237,0.82))] px-4 py-6 shadow-[0_24px_55px_-38px_rgba(255,106,0,0.72)] ring-1 ring-white/80 transition duration-300 hover:-translate-y-1 hover:border-orange-300 hover:shadow-[0_30px_65px_-38px_rgba(255,106,0,0.9)]" data-reveal="card">
                                    <span class="pointer-events-none absolute -right-8 -top-10 size-28 rounded-full bg-orange-300/20 blur-2xl transition group-hover:bg-orange-300/30" aria-hidden="true"></span>
                                    <span class="pointer-events-none absolute -bottom-12 -left-8 size-28 rounded-full bg-amber-200/25 blur-2xl" aria-hidden="true"></span>
                                    <strong class="relative text-4xl font-black tracking-tight text-primary drop-shadow-[0_10px_18px_rgba(255,106,0,0.2)] sm:text-5xl xl:text-6xl">{{ $stat['value'] }}</strong>
                                    <span class="relative mt-2 text-sm font-semibold leading-5 text-slate-600">{{ $stat['label'] }}</span>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($awards->isNotEmpty())
                @php
                    $isAwardSlider = $awards->count() > 1;
                @endphp
                <div
                    class="frontsite-card-reveal frontsite-slider-stage relative isolate min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-[linear-gradient(145deg,_rgba(255,255,255,0.58),_rgba(255,247,237,0.18))] p-2 shadow-[0_30px_80px_-46px_rgba(255,106,0,0.78)] ring-1 ring-orange-100/80 backdrop-blur-xl before:pointer-events-none before:absolute before:inset-x-8 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white before:to-transparent"
                    data-reveal="card"
                    @if ($isAwardSlider)
                        data-card-carousel
                        data-autoplay="true"
                        data-desktop-slider="true"
                        data-interval="4800"
                        style="--mobile-card-width: 100%; --tablet-card-width: 100%; --desktop-card-width: 100%; --desktop-columns: 1;"
                    @endif
                >
                    <span class="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-orange-300/25 blur-3xl" aria-hidden="true"></span>
                    <span class="pointer-events-none absolute -bottom-20 -left-16 size-52 rounded-full bg-amber-200/30 blur-3xl" aria-hidden="true"></span>

                    @if ($isAwardSlider)
                        <div class="frontsite-slider-nav">
                            <button type="button" class="service-card-carousel-control" data-card-carousel-prev aria-label="Xem giải thưởng trước">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="service-card-carousel-control" data-card-carousel-next aria-label="Xem giải thưởng tiếp theo">
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif

                    <div class="relative z-[1] {{ $isAwardSlider ? 'service-card-carousel-track' : '' }}" @if ($isAwardSlider) data-card-carousel-track @endif>
                        @foreach ($awards as $award)
                            @if ($isAwardSlider)
                                <div class="service-card-carousel-item" data-card-carousel-item>
                            @endif

                            <figure class="flex h-full flex-col">
                                <div class="flex aspect-[4/3] items-center justify-center p-6 sm:p-8 lg:aspect-square">
                                    <img
                                        src="{{ $award['image_url'] }}"
                                        alt="{{ $award['image_alt'] !== '' ? $award['image_alt'] : ($award['title'] !== '' ? $award['title'] : 'Giải thưởng Hải Đăng Travel') }}"
                                        class="h-full w-full object-contain drop-shadow-[0_22px_30px_rgba(15,23,42,0.2)]"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>

                                @if ($award['title'] !== '' || $award['description'] !== '')
                                    <figcaption class="px-6 py-5 text-center">
                                        @if ($award['title'] !== '')
                                            <p class="font-bold text-slate-950">{{ $award['title'] }}</p>
                                        @endif
                                        @if ($award['description'] !== '')
                                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $award['description'] }}</p>
                                        @endif
                                    </figcaption>
                                @endif
                            </figure>

                            @if ($isAwardSlider)
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
