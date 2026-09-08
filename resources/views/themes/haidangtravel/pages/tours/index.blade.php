@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $heroTour = $tours->first();
        $heroImageMedia = $heroTour
            ? \App\Support\FrontsiteMedia::responsiveUrls($heroTour, 'cover', 'cover_image_url')
            : [
                \App\Support\FrontsiteMedia::SIZE_SMALL => null,
                \App\Support\FrontsiteMedia::SIZE_MEDIUM => null,
                \App\Support\FrontsiteMedia::SIZE_FULL => null,
            ];
        $heroImage = $heroImageMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? null;
        $heroImageMedium = $heroImageMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $heroImage;
        $destinations = $frontsiteHeaderTaxonomies['tourDestinations'] ?? collect();
    @endphp

    <section class="relative overflow-hidden bg-secondary">
        @if ($heroImage)
            <picture class="absolute inset-0 block h-full w-full">
                @if ($heroImageMedium)
                    <source media="(max-width: 767px)" srcset="{{ $heroImageMedium }}">
                @endif
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $heroTour->cover_alt ?: $heroTour->title }}"
                    class="absolute inset-0 h-full w-full object-cover"
                    width="1600"
                    height="900"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                >
            </picture>
            <div class="absolute inset-0 bg-slate-950/65"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,106,0,0.22),_transparent_28%),linear-gradient(135deg,_#004A99_0%,_#0c3569_50%,_#002d5f_100%)]"></div>
        @endif

        <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
            <div class="max-w-3xl space-y-6">
                @include('themes.haidangtravel.partials.breadcrumbs', [
                    'items' => [
                        ['label' => 'Trang chủ', 'url' => route('home')],
                        ['label' => $landing?->title ?: $scope->label()],
                    ],
                ])

                <h1 class="frontsite-text-reveal font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" data-reveal="title">
                    {{ $landing?->hero_title ?: $landing?->title ?: $scope->label() }}
                </h1>
                <p class="frontsite-text-reveal text-lg leading-8 text-slate-200" data-reveal="body">
                    {{ $landing?->hero_excerpt ?: $landing?->intro_excerpt ?: $siteSettings->site_description }}
                </p>
            </div>
        </div>
    </section>

    <section class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel">
                <form action="{{ route($scope->routeName()) }}" method="GET" class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr_auto]">
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Phạm vi</span>
                        <input type="text" value="{{ $scope->label() }}" disabled class="w-full rounded-sm border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500 outline-none">
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Miền / khu vực</span>
                        <select name="region" class="w-full rounded-sm border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-primary focus:bg-white" data-frontsite-select data-frontsite-select-max-width="13rem">
                            <option value="">Tất cả khu vực</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->slug }}" @selected($selectedRegion === $region->slug)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex items-end gap-3">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover">
                            <i class="fa-solid fa-filter"></i>
                            Lọc
                        </button>
                        <a href="{{ route($scope->routeName()) }}" class="inline-flex items-center justify-center gap-2 rounded-sm border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-orange-200 hover:text-primary">
                            Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="px-4 pb-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="space-y-6">
                @include('themes.haidangtravel.partials.section-heading', [
                    'eyebrow' => $landing?->intro_title ?: 'Nhóm tour',
                    'title' => $landing?->title ?: $scope->label(),
                    'description' => $landing?->intro_excerpt ?: 'Danh mục tour đang dùng nhịp layout của trang dịch vụ cũ, nhưng toàn bộ dữ liệu đã chuyển sang travel.',
                ])

                <div class="theme-panel frontsite-text-reveal p-6" data-reveal="panel">
                    <h2 class="frontsite-h2-compact">Khu vực</h2>
                    <div class="mt-5 grid gap-4">
                        <a href="{{ route($scope->routeName()) }}" class="flex items-center justify-between rounded-sm border px-4 py-4 transition {{ $selectedRegion === '' ? 'border-orange-200 bg-[color:var(--color-primary-soft)]' : 'border-slate-200 bg-slate-50 hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)]' }}">
                            <div>
                                <p class="font-heading text-lg font-semibold text-slate-950">Tất cả khu vực</p>
                                <p class="text-sm text-slate-500">Xem toàn bộ tour thuộc nhóm {{ mb_strtolower($scope->label()) }}.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right text-primary"></i>
                        </a>
                        @foreach ($regions as $region)
                            <a href="{{ route($scope->routeName(), ['region' => $region->slug]) }}" class="flex items-center justify-between rounded-sm border px-4 py-4 transition {{ $selectedRegion === $region->slug ? 'border-orange-200 bg-[color:var(--color-primary-soft)]' : 'border-slate-200 bg-slate-50 hover:border-orange-200 hover:bg-[color:var(--color-primary-soft)]' }}">
                                <div>
                                    <p class="font-heading text-lg font-semibold text-slate-950">{{ $region->name }}</p>
                                    <p class="text-sm text-slate-500">Lọc tour theo khu vực {{ $region->name }}.</p>
                                </div>
                                <i class="fa-solid fa-arrow-right text-primary"></i>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($destinations->isNotEmpty())
                    <div class="theme-panel frontsite-text-reveal p-6" data-reveal="panel">
                        <h2 class="frontsite-h2-compact">Điểm đến</h2>
                        <div class="mt-5 flex flex-wrap gap-3">
                            @foreach ($destinations as $destination)
                                <a href="{{ route('destinations.show', ['destination' => $destination->slug]) }}" class="inline-flex items-center rounded-sm border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-orange-200 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    {{ $destination->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($landing?->body)
                    <div class="theme-panel frontsite-text-reveal p-6" data-reveal="copy">
                        <div class="theme-copy mt-4 text-sm leading-7 text-slate-600">
                            {!! \App\Support\RichText::render($landing->body) !!}
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="frontsite-text-reveal flex items-center justify-between gap-3" data-reveal="meta">
                    <p class="text-sm font-medium text-slate-500">Hiện có {{ $tours->total() }} tour</p>
                    <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->hotline ?: $siteSettings->phone) }}" class="inline-flex items-center gap-2 rounded-sm bg-[color:var(--color-primary-soft)] px-4 py-2 text-sm font-semibold text-primary">
                        <i class="fa-solid fa-phone-volume"></i>
                        {{ $siteSettings->hotline ?: $siteSettings->phone }}
                    </a>
                </div>

                @php
                    $tourCardCount = $tours->count();
                    $tourCardGridClasses = \App\Support\FrontsiteCardGrid::classes($tourCardCount);
                    $tourCardVariant = \App\Support\FrontsiteCardGrid::tourVariant($tourCardCount);
                @endphp

                <div class="{{ $tourCardGridClasses }}">
                    @forelse ($tours as $tour)
                        @include('themes.haidangtravel.partials.tour-card', [
                            'tour' => $tour,
                            'variant' => $tourCardVariant,
                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                        ])
                    @empty
                        <div class="theme-panel frontsite-text-reveal col-span-full p-10 text-center text-slate-500" data-reveal="panel">
                            Không tìm thấy tour phù hợp bộ lọc hiện tại.
                        </div>
                    @endforelse
                </div>

                @if ($tours->hasPages())
                    <div class="theme-panel frontsite-text-reveal p-4" data-reveal="panel">
                        {{ $tours->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('themes.haidangtravel.partials.cta-banner', [
        'description' => $landing?->cta_excerpt ?: 'Cần gợi ý nhanh theo ngày đi, ngân sách hoặc quy mô đoàn?',
        'primaryLabel' => $landing?->cta_primary_label ?: 'Gửi yêu cầu',
        'primaryUrl' => $landing?->cta_primary_url ?: route('contact'),
        'secondaryLabel' => $landing?->cta_secondary_label ?: 'Xem dịch vụ',
        'secondaryUrl' => $landing?->cta_secondary_url ?: route('services.index'),
        'title' => $landing?->cta_title ?: 'Chưa chắc nên chọn tour nào cho nhu cầu hiện tại?',
    ])
@endsection
