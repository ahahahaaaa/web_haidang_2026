@props([
    'gallery' => [],
])

@php
    $galleryEnabled = (bool) ($gallery['enabled'] ?? false);
    $galleryItems = collect($gallery['items'] ?? [])->filter(function (array $item): bool {
        return filled($item['image_url'] ?? null)
            || filled($item['mobile_image_url'] ?? null)
            || filled($item['inner_image_url'] ?? null)
            || filled($item['thumbnail_url'] ?? null)
            || filled($item['embed_url'] ?? null)
            || filled($item['video_url'] ?? null);
    })->values();
    $galleryTitle = trim((string) ($gallery['title'] ?? $gallery['eyebrow'] ?? ''));
    $galleryRootId = 'landing-gallery-'.(
        filled($gallery['uuid'] ?? null)
            ? \Illuminate\Support\Str::slug((string) $gallery['uuid'])
            : substr(md5($galleryTitle.'|'.$galleryItems->count()), 0, 10)
    );
    $galleryHeadingClasses = 'frontsite-text-reveal frontsite-h2';
    $destinationGridAreas = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];
    $useDestinationGrid = $galleryItems->count() >= count($destinationGridAreas);
    $primaryGalleryItems = $useDestinationGrid
        ? $galleryItems->take(count($destinationGridAreas))
        : $galleryItems;
    $overflowGalleryItems = $useDestinationGrid
        ? $galleryItems->slice(count($destinationGridAreas))->values()
        : collect();
@endphp

@if ($galleryEnabled && $galleryItems->isNotEmpty())
    <section class="block-home-destinationpoint bg-white px-4 py-8 sm:px-6 lg:px-8 lg:py-10" id="{{ $galleryRootId }}">
        <div class="mx-auto max-w-7xl space-y-4">
            @if ($galleryTitle !== '')
                <div class="max-w-3xl">
                    <h2 class="{{ $galleryHeadingClasses }}" data-reveal="title">{{ $galleryTitle }}</h2>
                </div>
            @endif

            <div
                class="md:hidden"
                data-card-carousel
                data-interval="4200"
                style="--mobile-card-width: 100%; --tablet-card-width: 100%;"
            >
                @if ($galleryItems->count() > 1)
                    <div class="frontsite-slider-nav mb-3 flex items-center gap-3">
                        <button
                            type="button"
                            data-card-carousel-prev
                            class="service-card-carousel-control frontsite-text-reveal"
                            data-reveal="meta"
                            aria-label="Xem ảnh trước"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <button
                            type="button"
                            data-card-carousel-next
                            class="service-card-carousel-control frontsite-text-reveal"
                            data-reveal="meta"
                            aria-label="Xem ảnh tiếp theo"
                        >
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                @endif

                <div class="service-card-carousel-track" data-card-carousel-track>
                    @foreach ($galleryItems as $item)
                        <div class="service-card-carousel-item" data-card-carousel-item>
                            @php
                                $cardClass = 'frontsite-text-reveal group relative isolate block w-full overflow-hidden rounded-[1.5rem] bg-slate-200 shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_30px_75px_-48px_rgba(15,23,42,0.42)]';
                            @endphp

                            @include('themes.haidangtravel.partials.landing-gallery-card', [
                                'item' => $item,
                                'cardClass' => $cardClass,
                                'delay' => number_format(($loop->index % 3) * 0.08, 2, '.', ''),
                                'intrinsicHeight' => true,
                                'tagLabel' => '',
                            ])
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="hidden md:block">
            @if ($useDestinationGrid)
                <div class="dest-grid frontsite-text-reveal" data-reveal="panel">
                    @foreach ($primaryGalleryItems as $item)
                        @php
                            $destinationArea = $destinationGridAreas[$loop->index] ?? 'a';
                            $cardClass = 'dest-grid-item--'.$destinationArea.' frontsite-text-reveal group relative isolate block h-full min-h-[14rem] overflow-hidden rounded-[1.75rem] bg-slate-200 shadow-[0_24px_70px_-50px_rgba(15,23,42,0.4)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_32px_85px_-50px_rgba(15,23,42,0.45)] lg:min-h-0';
                        @endphp

                        @include('themes.haidangtravel.partials.landing-gallery-card', [
                            'item' => $item,
                            'cardClass' => $cardClass,
                            'delay' => number_format(($loop->index % 3) * 0.08, 2, '.', ''),
                            'tagLabel' => '',
                        ])
                    @endforeach
                </div>

                @if ($overflowGalleryItems->isNotEmpty())
                    <div class="mt-2.5 grid items-start gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($overflowGalleryItems as $item)
                            @php
                                $cardClass = 'frontsite-text-reveal group relative isolate block overflow-hidden rounded-[1.5rem] bg-slate-200 shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_30px_75px_-48px_rgba(15,23,42,0.42)]';
                            @endphp

                            @include('themes.haidangtravel.partials.landing-gallery-card', [
                                'item' => $item,
                                'cardClass' => $cardClass,
                                'delay' => number_format(($loop->index % 3) * 0.08, 2, '.', ''),
                                'intrinsicHeight' => true,
                                'tagLabel' => '',
                            ])
                        @endforeach
                    </div>
                @endif
            @else
                <div class="grid items-start gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($galleryItems as $item)
                        @php
                            $cardClass = 'frontsite-text-reveal group relative isolate block overflow-hidden rounded-[1.5rem] bg-slate-200 shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_30px_75px_-48px_rgba(15,23,42,0.42)]';
                        @endphp

                        @include('themes.haidangtravel.partials.landing-gallery-card', [
                            'item' => $item,
                            'cardClass' => $cardClass,
                            'delay' => number_format(($loop->index % 3) * 0.08, 2, '.', ''),
                            'intrinsicHeight' => true,
                            'tagLabel' => '',
                        ])
                    @endforeach
                </div>
            @endif
            </div>
        </div>
    </section>
@endif
