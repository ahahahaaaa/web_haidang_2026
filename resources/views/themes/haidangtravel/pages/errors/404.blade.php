@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $hotTours = collect($hotTours ?? []);
        $latestTours = collect($latestTours ?? []);
        $phone = trim((string) ($siteSettings->hotline ?: $siteSettings->phone));
        $phoneHref = $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : route('contact');
        $suggestionGridClasses = 'grid gap-2 md:grid-cols-2 xl:grid-cols-4';
    @endphp

    <section class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_12%_18%,rgba(255,106,0,0.12),transparent_32%),radial-gradient(circle_at_90%_8%,rgba(0,74,153,0.12),transparent_30%)]"></div>

        <div class="mx-auto max-w-7xl space-y-5">
            <div class="theme-panel frontsite-text-reveal flex min-h-[24rem] flex-col items-center justify-center overflow-hidden p-6 text-center sm:p-8 lg:min-h-[22rem] lg:flex-row lg:justify-start lg:gap-8 lg:p-10 lg:text-left" data-reveal="panel">
                <p class="font-heading text-[5.5rem] font-extrabold leading-none text-primary/15 sm:text-[7rem] lg:w-[13rem] lg:shrink-0 lg:text-[8rem] lg:text-center">404</p>
                <div class="relative -mt-4 space-y-5 lg:mt-0">
                    <h1 class="font-heading text-3xl font-extrabold leading-tight text-slate-950 sm:text-4xl lg:text-5xl">
                        Không tìm thấy trang bạn đang tìm
                    </h1>
                    <p class="mx-auto max-w-2xl text-base leading-8 text-slate-600 sm:text-lg lg:mx-0">
                        Đường dẫn này có thể đã đổi hoặc không còn được sử dụng. Bạn có thể quay lại trang chủ, tìm nhanh một hành trình khác, hoặc để Hải Đăng Travel tư vấn theo ngày đi và ngân sách của mình.
                    </p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-center lg:justify-start">
                        <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-bold text-white shadow-[0_20px_50px_-30px_rgba(255,106,0,0.9)] transition hover:bg-[color:var(--color-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                            <i class="fa-solid fa-house"></i>
                            Về trang chủ
                        </a>
                        <a href="{{ route('tours.search') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-800 transition hover:border-orange-200 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Tìm tour
                        </a>
                        <button
                            type="button"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-orange-200 bg-orange-50 px-5 py-3 text-sm font-bold text-primary transition hover:bg-orange-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="general"
                            data-travel-inquiry-context="Trang 404"
                            data-travel-inquiry-subject="Cần tư vấn sau khi gặp trang 404"
                            data-travel-inquiry-modal-title="Thông tin đặt tour"
                            data-travel-inquiry-modal-description="Điền nhanh thông tin để Hải Đăng Travel gợi ý hành trình phù hợp thay cho trang bạn vừa tìm."
                        >
                            <i class="fa-regular fa-paper-plane"></i>
                            Nhận tư vấn
                        </button>
                    </div>
                </div>
            </div>

            @include('themes.haidangtravel.partials.shared-search-bar', [
                'action' => route('tours.search'),
                'containerClasses' => 'max-w-none',
                'inputValue' => '',
                'placeholder' => 'Bạn muốn tìm tour nào?',
                'sectionClasses' => 'px-0 py-0',
            ])

            @if ($latestTours->isNotEmpty())
                <div class="space-y-5">
                    @include('themes.haidangtravel.partials.section-heading', [
                        'title' => 'Tour mới cập nhật',
                        'description' => 'Danh sách mới nhất từ hệ thống tour đang mở bán hoặc nhận tư vấn, phù hợp khi bạn muốn bắt đầu lại từ các lựa chọn còn mới.',
                        'width' => 'max-w-none',
                    ])

                    <div class="{{ $suggestionGridClasses }}">
                        @foreach ($latestTours as $tour)
                            @include('themes.haidangtravel.partials.tour-card', [
                                'tour' => $tour,
                                'variant' => 'default',
                                'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                            ])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($hotTours->isNotEmpty() || ($hotTours->isEmpty() && $latestTours->isEmpty()))
        <section class="px-4 pb-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                @if ($hotTours->isNotEmpty())
                    <div class="space-y-5">
                        @include('themes.haidangtravel.partials.section-heading', [
                            'title' => 'Tour đang nổi bật',
                            'description' => 'Những hành trình được ưu tiên hiển thị để bạn tiếp tục so sánh nhanh điểm đến, lịch khởi hành và mức giá.',
                            'width' => 'max-w-none',
                        ])

                        <div class="{{ $suggestionGridClasses }}">
                            @foreach ($hotTours as $tour)
                                @include('themes.haidangtravel.partials.tour-card', [
                                    'tour' => $tour,
                                    'variant' => 'default',
                                    'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                                ])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($hotTours->isEmpty() && $latestTours->isEmpty())
                    <div class="theme-panel frontsite-text-reveal p-6 text-center sm:p-8" data-reveal="panel">
                        <h2 class="font-heading text-2xl font-bold text-slate-950">Chưa có tour gợi ý để hiển thị</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Bạn vẫn có thể gửi yêu cầu để đội ngũ tư vấn lọc hành trình phù hợp theo điểm đến, số khách và thời gian dự kiến.
                        </p>
                        <button
                            type="button"
                            class="mt-5 inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-primary px-5 py-3 text-sm font-bold text-white transition hover:bg-[color:var(--color-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                            data-travel-inquiry-open
                            data-travel-inquiry-source="general"
                            data-travel-inquiry-context="Trang 404"
                            data-travel-inquiry-subject="Cần tư vấn tour phù hợp"
                            data-travel-inquiry-modal-title="Thông tin đặt tour"
                            data-travel-inquiry-modal-description="Điền nhanh thông tin để Hải Đăng Travel lọc hành trình phù hợp với nhu cầu của bạn."
                        >
                            <i class="fa-regular fa-paper-plane"></i>
                            Gửi yêu cầu tư vấn
                        </button>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="px-4 pb-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:items-center">
                    <div class="space-y-3">
                        <h2 class="font-heading text-2xl font-bold text-slate-950">Đi nhanh đến nhóm tour chính</h2>
                        <p class="text-sm leading-7 text-slate-600">Các lối đi này vẫn là sitemap travel chính của website.</p>
                    </div>

                    <div class="grid gap-2 md:grid-cols-3">
                        <a href="{{ route('tours.domestic') }}" class="group flex items-center justify-between rounded-sm bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-orange-50 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25">
                            <span><i class="fa-solid fa-location-dot mr-2 text-secondary"></i>Tour trong nước</span>
                            <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1"></i>
                        </a>
                        <a href="{{ route('tours.international') }}" class="group flex items-center justify-between rounded-sm bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-orange-50 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25">
                            <span><i class="fa-solid fa-plane-departure mr-2 text-secondary"></i>Tour nước ngoài</span>
                            <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1"></i>
                        </a>
                        <a href="{{ route('tours.group') }}" class="group flex items-center justify-between rounded-sm bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-orange-50 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25">
                            <span><i class="fa-solid fa-people-group mr-2 text-secondary"></i>Tour đoàn</span>
                            <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1"></i>
                        </a>
                    </div>
                </div>

                @if ($phone !== '')
                    <a href="{{ $phoneHref }}" class="mt-5 flex items-center gap-3 rounded-sm bg-[color:var(--color-primary-soft)] p-4 text-primary transition hover:bg-orange-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 sm:inline-flex">
                        <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-primary">
                            <i class="fa-solid fa-phone-volume"></i>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase text-slate-500">Hotline hỗ trợ</span>
                            <span class="block font-heading text-xl font-bold">{{ $phone }}</span>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    </section>
@endsection
