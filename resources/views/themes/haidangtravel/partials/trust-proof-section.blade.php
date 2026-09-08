@php
    $displayTitle = trim((string) ($title ?? ''));
    $displayDescription = trim((string) ($description ?? ''));
    $displaySectionId = trim((string) ($sectionId ?? ''));
    $sectionClasses = $sectionClasses ?? 'relative overflow-hidden bg-[linear-gradient(180deg,#fffaf5_0%,#eef5ff_100%)] px-4 py-7 sm:px-6 lg:px-8 lg:py-10';
    $cardIconFallbacks = [
        'fa-solid fa-route',
        'fa-solid fa-calendar-check',
        'fa-solid fa-shield-heart',
        'fa-solid fa-headset',
        'fa-solid fa-earth-asia',
        'fa-solid fa-bolt',
    ];
    $cardBackgroundIconFallbacks = [
        'fa-solid fa-earth-asia',
        'fa-solid fa-bolt',
        'fa-solid fa-headset',
        'fa-solid fa-route',
        'fa-solid fa-shield-heart',
        'fa-solid fa-calendar-check',
    ];
    $statIconFallbacks = [
        'fa-solid fa-award',
        'fa-solid fa-users',
        'fa-solid fa-user-tie',
        'fa-solid fa-file-shield',
        'fa-solid fa-earth-asia',
        'fa-solid fa-headset',
    ];
    $cards = collect($cards ?? [])
        ->filter(fn ($card) => is_array($card))
        ->values()
        ->map(fn (array $card, int $index) => [
            'background_icon' => trim((string) ($card['background_icon'] ?? '')) ?: $cardBackgroundIconFallbacks[$index % count($cardBackgroundIconFallbacks)],
            'highlight' => trim((string) ($card['highlight'] ?? '')),
            'icon' => trim((string) ($card['icon'] ?? '')) ?: $cardIconFallbacks[$index % count($cardIconFallbacks)],
            'text' => trim((string) ($card['text'] ?? $card['description'] ?? '')),
            'title' => trim((string) ($card['title'] ?? '')),
        ])
        ->filter(fn (array $card) => $card['title'] !== '' && $card['text'] !== '')
        ->values();
    $featuredCard = $cards->first();
    $supportCards = $cards->slice(1)->take(2)->values();
    $proofGridClasses = $supportCards->isNotEmpty()
        ? 'grid gap-4 lg:grid-cols-[1.05fr_0.95fr]'
        : 'grid gap-4';
    $stats = collect($stats ?? [])
        ->filter(fn ($stat) => is_array($stat))
        ->values()
        ->map(fn (array $stat, int $index) => [
            'icon' => trim((string) ($stat['icon'] ?? '')) ?: $statIconFallbacks[$index % count($statIconFallbacks)],
            'label' => trim((string) ($stat['label'] ?? '')),
            'value' => trim((string) ($stat['value'] ?? '')),
        ])
        ->filter(fn (array $stat) => $stat['value'] !== '' && $stat['label'] !== '')
        ->take(6)
        ->values();
@endphp

@if ($displayTitle !== '' || $displayDescription !== '' || $cards->isNotEmpty() || $stats->isNotEmpty())
    <section class="{{ $sectionClasses }}" @if ($displaySectionId !== '') id="{{ $displaySectionId }}" @endif>
        <div class="relative mx-auto max-w-7xl">
            @if ($displayTitle !== '' || $displayDescription !== '')
                <div class="max-w-3xl">
                    @if ($displayTitle !== '')
                        <h2 class="frontsite-text-reveal frontsite-h2" data-reveal="title">
                            {{ $displayTitle }}
                        </h2>
                    @endif

                    @if ($displayDescription !== '')
                        <p class="frontsite-text-reveal mt-3 max-w-3xl text-sm leading-6 text-slate-600 sm:text-base" data-reveal="body">
                            {{ $displayDescription }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($featuredCard)
                <div class="{{ $displayTitle !== '' || $displayDescription !== '' ? 'mt-6 ' : '' }}{{ $proofGridClasses }}">
                    <article class="frontsite-text-reveal group relative isolate overflow-hidden rounded-[1.45rem] bg-[linear-gradient(145deg,#032347_0%,#004A99_58%,#0f4c81_100%)] p-5 text-white shadow-[0_24px_70px_-42px_rgba(3,18,43,0.78)] sm:rounded-[1.6rem] sm:p-6" data-reveal="panel">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.15),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(255,140,0,0.35),_transparent_36%)]"></div>
                        <i class="{{ $featuredCard['background_icon'] }} pointer-events-none absolute -right-5 -top-5 hidden text-[8rem] text-white/10 transition duration-500 group-hover:scale-110 sm:block"></i>

                        <div class="relative">
                            <div class="flex items-start justify-between gap-4">
                                @if ($featuredCard['highlight'] !== '')
                                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-orange-100">
                                        {{ $featuredCard['highlight'] }}
                                    </span>
                                @endif

                                <span class="shrink-0 text-3xl font-black leading-none text-white/15">01</span>
                            </div>

                            <div class="mt-5 flex items-start gap-4">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-lg sm:size-12 sm:text-xl">
                                    <i class="{{ $featuredCard['icon'] }}"></i>
                                </div>

                                <div class="min-w-0">
                                    <h3 class="font-heading text-[1.35rem] font-black leading-tight sm:text-3xl">
                                        {{ $featuredCard['title'] }}
                                    </h3>

                                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-100/90 sm:text-base">
                                        {{ $featuredCard['text'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>

                    @if ($supportCards->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            @foreach ($supportCards as $card)
                                @php
                                    $isBlueTone = $loop->iteration % 2 === 0;
                                    $iconShellClass = $isBlueTone ? 'bg-blue-50 text-secondary' : 'bg-orange-50 text-orange-500';
                                    $backgroundIconClass = $isBlueTone ? 'text-blue-100' : 'text-orange-100';
                                @endphp

                                <article class="frontsite-text-reveal group relative overflow-hidden rounded-[1.35rem] border border-white/75 bg-white/95 p-4 shadow-[0_20px_55px_-45px_rgba(15,23,42,0.35)] transition hover:-translate-y-1 hover:border-orange-200 sm:rounded-[1.4rem] sm:p-5" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}">
                                    <i class="{{ $card['background_icon'] }} pointer-events-none absolute -right-3 -top-4 hidden text-[5.5rem] {{ $backgroundIconClass }} transition duration-500 group-hover:scale-110 sm:block"></i>

                                    <div class="relative flex items-start gap-3 sm:gap-4">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl {{ $iconShellClass }} sm:size-11">
                                            <i class="{{ $card['icon'] }}"></i>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-3">
                                                @if ($card['highlight'] !== '')
                                                    <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-primary sm:text-[11px] sm:tracking-[0.18em]">
                                                        {{ $card['highlight'] }}
                                                    </span>
                                                @endif
                                                <span class="shrink-0 text-sm font-black text-slate-200">
                                                    {{ str_pad((string) ($loop->iteration + 1), 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                            </div>

                                            <h3 class="mt-2 font-heading text-lg font-black leading-tight text-slate-950 sm:text-xl">
                                                {{ $card['title'] }}
                                            </h3>

                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                {{ $card['text'] }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($stats->isNotEmpty())
                <div class="{{ $featuredCard ? 'mt-4 ' : ($displayTitle !== '' || $displayDescription !== '' ? 'mt-6 ' : '') }}grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($stats as $stat)
                        @php
                            $isBlueTone = $loop->iteration % 2 === 1;
                            $iconShellClass = $isBlueTone ? 'bg-blue-50 text-secondary' : 'bg-orange-50 text-orange-500';
                        @endphp

                        <div class="frontsite-text-reveal flex items-center gap-3 rounded-2xl border border-white/75 bg-white/90 p-4 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.35)]" data-reveal="card" data-reveal-delay="{{ number_format($loop->index * 0.06, 2, '.', '') }}">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $iconShellClass }}">
                                <i class="{{ $stat['icon'] }}"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-heading text-2xl font-black leading-none text-secondary">
                                    {{ $stat['value'] }}
                                </p>
                                <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ $stat['label'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
