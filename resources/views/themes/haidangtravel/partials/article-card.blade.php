@php
    $card = \App\Support\FrontsiteCardData::blog($post, $frontsiteHeaderTaxonomies['serviceCategories'] ?? collect());
    $cover = $card['image_url'];
    $revealDelay = $revealDelay ?? null;
    $authorLabel = $card['author_label'] ?: $siteSettings->site_name;
    $authorInitials = collect(preg_split('/\s+/', trim((string) $authorLabel)) ?: [])
        ->filter(fn ($part) => $part !== '')
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
    $authorInitials = $authorInitials !== '' ? $authorInitials : 'HD';
    $metaItems = array_values(array_filter([
        $card['published_label'] ?? null,
        $authorLabel,
        $card['category_label'] ?? null,
        $card['geo_label'] ?? null,
    ]));
@endphp

<article class="theme-panel frontsite-spotlight-card group overflow-hidden frontsite-text-reveal" data-reveal="card" @if ($revealDelay !== null) data-reveal-delay="{{ $revealDelay }}" @endif>
    <a
        href="{{ $card['detail_url'] }}"
        class="frontsite-media-panel relative block aspect-[16/9] overflow-hidden bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/35"
    >
        @if ($cover)
            <img
                src="{{ $cover }}"
                alt="{{ $card['image_alt'] }}"
                class="frontsite-media-asset h-full w-full object-cover"
                width="960"
                height="600"
                loading="lazy"
                decoding="async"
            >
        @else
            <div class="theme-grid-pattern flex h-full w-full items-end bg-[linear-gradient(145deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] p-4">
                <div class="space-y-1 text-white">
                    <p class="text-[10px] font-semibold uppercase text-white/72">{{ $card['intent_label'] }}</p>
                    <p class="max-w-[16rem] text-lg font-semibold leading-tight">{{ $card['category_label'] }}</p>
                </div>
            </div>
        @endif

        <div class="frontsite-media-content absolute inset-x-3 top-3 flex items-start justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-white/14 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                <i class="fa-regular fa-newspaper text-orange-200"></i>
                {{ $card['intent_label'] }}
            </span>

            @if ($card['reading_time_label'])
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/16 bg-slate-950/24 px-2.5 py-0.5 text-[9px] font-semibold uppercase text-white shadow-[0_16px_38px_-28px_rgba(15,23,42,0.68)] backdrop-blur-sm">
                    <i class="fa-regular fa-clock text-orange-200"></i>
                    {{ $card['reading_time_label'] }}
                </span>
            @endif
        </div>
    </a>

    <div class="flex items-center gap-2.5 border-b border-slate-200 px-4 py-2.5">
        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-[linear-gradient(135deg,#08284f_0%,#004A99_52%,#FF8C00_100%)] text-[11px] font-bold text-white">
            {{ $authorInitials }}
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[11px] leading-4 text-slate-500">
                @foreach ($metaItems as $item)
                    <span class="truncate @if ($loop->first) max-w-[7.5rem] sm:max-w-none @endif">{{ $item }}</span>
                    @if (! $loop->last)
                        <span class="text-slate-300">|</span>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-2.5 p-4 pt-3">
        <h3 class="font-heading text-[14px] font-bold leading-[1.3] text-slate-950">
            <a href="{{ $card['detail_url'] }}" class="line-clamp-2 transition hover:text-primary focus-visible:outline-none focus-visible:text-primary">
                {{ $card['title'] }}
            </a>
        </h3>

        <p class="line-clamp-2 text-[12px] leading-5 text-slate-600">{{ $card['summary'] }}</p>

        <div class="flex items-center justify-end border-t border-slate-200 pt-2.5">
            <a href="{{ $card['detail_url'] }}" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-primary transition group-hover:gap-2">
                {{ $card['detail_label'] }}
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</article>
