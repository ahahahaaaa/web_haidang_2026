@php
    $campaign = is_array($campaign ?? null) ? $campaign : null;
    $offers = collect($campaign['offers'] ?? []);
    $showViewMore = (bool) ($showViewMore ?? true);
    $ctaLabel = trim((string) ($viewMoreLabel ?? '')) ?: trim((string) ($campaign['cta_label'] ?? '')) ?: 'Xem thêm';
    $ctaUrl = trim((string) ($viewMoreUrl ?? '')) ?: trim((string) ($campaign['cta_url'] ?? ''));
    $ctaUrl = \App\Support\TravelHomePageConfig::isSafeFeaturedTourPopularSearchUrl($ctaUrl) ? $ctaUrl : '';
    $isSlider = (bool) ($isSlider ?? false);
@endphp

@if ($campaign && $offers->isNotEmpty())
    <section class="px-4 py-8 sm:px-6 lg:px-8" aria-labelledby="flash-sale-heading-{{ $sectionId ?? 'default' }}">
        <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[1.75rem] bg-[radial-gradient(circle_at_50%_-30%,rgba(255,255,255,0.24),transparent_48%),linear-gradient(145deg,#E65F00_0%,#C2410C_58%,#9A3412_100%)] p-5 text-white shadow-[0_30px_90px_-55px_rgba(154,52,18,0.72)] sm:p-7">
            <div class="pointer-events-none absolute inset-0 opacity-20 [background:repeating-conic-gradient(from_260deg_at_50%_100%,transparent_0deg_11deg,rgba(255,255,255,.2)_12deg_13deg)]"></div>
            <div class="relative">
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <span class="mt-1 text-3xl text-amber-300"><i class="{{ $campaign['icon_class'] }}"></i></span>
                        <div>
                            <h2 id="flash-sale-heading-{{ $sectionId ?? 'default' }}" class="text-2xl font-extrabold sm:text-3xl">{{ $campaign['title'] }}</h2>
                            @if (filled($campaign['description']))
                                <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-white/90">{{ $campaign['description'] }}</p>
                            @endif
                        </div>
                    </div>
                    @if ($showViewMore && $ctaUrl !== '')
                        @include('themes.haidangtravel.partials.block-view-more-link', [
                            'label' => $ctaLabel,
                            'url' => $ctaUrl,
                        ])
                    @endif
                </div>

                <div
                    class="frontsite-slider-stage"
                    @if ($isSlider)
                        data-card-carousel
                        data-desktop-slider="true"
                        data-tour-card-slider="true"
                        style="--desktop-columns: 4; --mobile-card-width: calc(83.333% - 0.17rem); --desktop-card-width: calc((100% - 3rem) / 4);"
                    @endif
                >
                    @if ($isSlider && $offers->count() > 1)
                        <div class="frontsite-slider-nav">
                            <button type="button" class="service-card-carousel-control" data-card-carousel-prev aria-label="Xem tour ưu đãi trước"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                            <button type="button" class="service-card-carousel-control" data-card-carousel-next aria-label="Xem tour ưu đãi tiếp theo"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    @endif
                    <div class="{{ $isSlider ? 'service-card-carousel-track' : 'grid gap-3 sm:grid-cols-2 lg:grid-cols-4' }}" @if ($isSlider) data-card-carousel-track @endif>
                        @foreach ($offers as $offer)
                            @if ($isSlider)<div class="service-card-carousel-item" data-card-carousel-item>@endif
                                @include('themes.haidangtravel.partials.flash-sale-tour-card', ['offer' => $offer])
                            @if ($isSlider)</div>@endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
