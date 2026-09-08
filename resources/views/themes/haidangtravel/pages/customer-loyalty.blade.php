@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $lookup = $lookup ?? null;
        $customer = $lookup['customer'] ?? [];
        $points = (int) ($lookup['points'] ?? 0);
        $showHistorySections = (bool) ($showHistorySections ?? false);
        $ordersPage = $ordersPaginator ?? null;
        $orders = $showHistorySections ? collect($ordersPage ? $ordersPage->items() : ($lookup['orders'] ?? [])) : collect();
        $totalOrders = $showHistorySections ? ($ordersPage ? $ordersPage->total() : $orders->count()) : 0;
        $redemptions = $showHistorySections ? collect($lookup['redemptions'] ?? []) : collect();
        $sections = $lookup['sections'] ?? [];
        $giftCatalog = $giftCatalog ?? [];
        $giftSections = $giftCatalog['sections'] ?? ($lookup['sections'] ?? []);
        $giftCatalogError = $giftCatalog['error'] ?? null;
        $gifts = collect($giftCatalog['gifts'] ?? ($lookup['gifts'] ?? []));
        $featuredTours = collect($featuredTours ?? []);
        $formatNumber = fn (mixed $value): string => is_numeric($value) ? number_format((float) $value, 0, ',', '.') : (string) $value;
        $displayPhone = $phone ?: ($customer['phone'] ?? '');
        $canRedeemGifts = $lookup && filled($displayPhone);
    @endphp

    <section class="relative overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.28),transparent_34%),linear-gradient(135deg,#ff6a00_0%,#f04438_55%,#0f172a_140%)]">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[0.68fr_0.32fr] lg:px-8 lg:py-14">
            <div>
                <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-white/80" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="transition hover:text-white">Trang chủ</a>
                    <span aria-hidden="true">/</span>
                    <span class="font-semibold text-white">Điểm thưởng</span>
                </nav>

                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-orange-100">CRM khách hàng</p>
                <h1 class="mt-3 max-w-3xl font-heading text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                    Kiểm tra điểm và đổi quà Hải Đăng Travel
                </h1>
                <p class="mt-4 max-w-3xl text-base leading-8 text-orange-50">
                    @if ($showHistorySections)
                        Nhập số điện thoại đã dùng khi đặt tour để xem đơn hàng, tổng điểm hiện có, lịch sử đổi quà và gửi yêu cầu đổi quà. Nhân sự Hải Đăng Travel sẽ liên hệ xác nhận trước khi hoàn tất đổi quà.
                    @else
                        Nhập số điện thoại đã dùng khi đặt tour để xem tổng điểm hiện có và gửi yêu cầu đổi quà. Nhân sự Hải Đăng Travel sẽ liên hệ xác nhận trước khi hoàn tất đổi quà.
                    @endif
                </p>

                <form action="{{ route('customer-loyalty.index') }}" method="GET" class="mt-7 grid gap-3 rounded-[1.5rem] bg-white/95 p-3 shadow-2xl shadow-orange-950/20 sm:grid-cols-[1fr_auto]">
                    <label class="sr-only" for="customer-loyalty-phone">Số điện thoại</label>
                    <input
                        id="customer-loyalty-phone"
                        type="tel"
                        name="phone"
                        value="{{ old('phone', $displayPhone) }}"
                        placeholder="Nhập SĐT, ví dụ 0909794299"
                        required
                        class="min-h-13 rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary focus:ring-4 focus:ring-orange-100"
                    >
                    <button type="submit" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-[1rem] bg-slate-950 px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:bg-primary focus:outline-none focus:ring-4 focus:ring-orange-100">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Kiểm tra
                    </button>
                </form>

                @error('phone')
                    <p class="mt-3 rounded-2xl bg-white/90 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <aside data-customer-loyalty-points-card class="self-end rounded-[1.75rem] border border-white/30 bg-white/90 p-5 shadow-2xl shadow-orange-950/20 backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Số điểm</p>
                <p class="mt-2 text-4xl font-extrabold text-slate-950">{{ $lookup ? $formatNumber($points) : '---' }}</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    @if ($lookup)
                        {{ filled($customer['name'] ?? null) ? $customer['name'] : 'Khách hàng' }} · {{ $customer['phone'] ?? $displayPhone }}
                    @else
                        Điểm sẽ hiển thị sau khi tra cứu thành công.
                    @endif
                </p>
                @if ($lookup)
                    <a
                        href="#customer-loyalty-gifts"
                        class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-[1rem] bg-primary px-4 py-3 text-sm font-bold uppercase tracking-[0.14em] text-white transition hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100"
                    >
                        <i class="fa-solid fa-gift" aria-hidden="true"></i>
                        Đổi quà ngay
                    </a>
                @endif
            </aside>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('customer_loyalty_status'))
            <div class="mb-5 rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                {{ session('customer_loyalty_status') }}
            </div>
        @endif

        @if ($errors->has('redemption'))
            <div class="mb-5 rounded-[1.25rem] border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                {{ $errors->first('redemption') }}
            </div>
        @endif

        @if (! $apiConfigured)
            <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5 text-sm leading-7 text-amber-800">
                Cổng API quà chưa được cấu hình đầy đủ. Vui lòng kiểm tra mục API quà tặng CRM trong Theme Settings.
            </div>
        @elseif ($lookupError)
            <div class="rounded-[1.5rem] border border-red-200 bg-red-50 p-5 text-sm leading-7 text-red-700">
                {{ $lookupError }}
            </div>
        @elseif (! $lookup)
            <div class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="frontsite-h2-card">Tra cứu dữ liệu điểm thưởng</h2>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                    @if ($showHistorySections)
                        Dữ liệu đơn hàng, điểm thưởng, lịch sử đổi quà và danh sách quà được lấy trực tiếp từ cổng API đối tác theo số điện thoại khách hàng.
                    @else
                        Dữ liệu điểm thưởng và danh sách quà được lấy trực tiếp từ cổng API đối tác theo số điện thoại khách hàng.
                    @endif
                </p>
            </div>
        @endif

        @if ($lookup && $showHistorySections)
                <div class="grid gap-5 lg:grid-cols-[0.64fr_0.36fr]">
                    <section id="customer-loyalty-orders" data-customer-loyalty-orders class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm scroll-mt-24">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="frontsite-h2-card">Đơn hàng đã có</h2>
                                <p class="mt-2 text-sm text-slate-500">Các đơn hàng được API trả về theo số điện thoại đang tra cứu.</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $totalOrders }} đơn</span>
                        </div>

                        @if ($ordersPage && $ordersPage->total() > 0)
                            <p class="mt-3 text-xs font-medium text-slate-500">
                                Hiển thị {{ $ordersPage->firstItem() }}-{{ $ordersPage->lastItem() }} / {{ $ordersPage->total() }} đơn.
                            </p>
                        @endif

                        <div class="mt-5 overflow-x-auto rounded-[1.25rem] border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-[0.2em] text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3">Mã</th>
                                        <th class="px-4 py-3">Tour/Dịch vụ</th>
                                        <th class="px-4 py-3">Ngày</th>
                                        <th class="px-4 py-3">Điểm</th>
                                        <th class="px-4 py-3">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($orders as $order)
                                        <tr>
                                            <td class="px-4 py-4 font-semibold text-slate-900">{{ $order['code'] }}</td>
                                            <td class="px-4 py-4 text-slate-700">{{ $order['title'] }}</td>
                                            <td class="px-4 py-4 text-slate-500">{{ $order['date'] ?: 'Chưa có' }}</td>
                                            <td class="px-4 py-4 text-slate-700">{{ filled($order['points'] ?? null) ? $formatNumber($order['points']) : '---' }}</td>
                                            <td class="px-4 py-4 text-slate-500">{{ $order['status'] ?: 'Đang cập nhật' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                                {{ ($sections['orders'] ?? false) ? 'Chưa có đơn hàng được API trả về.' : 'API chưa trả về danh sách đơn hàng.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($ordersPage && $ordersPage->hasPages())
                            <div class="mt-4">
                                {{ $ordersPage->links() }}
                            </div>
                        @endif
                    </section>

                    <section data-customer-loyalty-redemptions class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="frontsite-h2-card">Lịch sử đổi quà</h2>
                        <div class="mt-5 space-y-3">
                            @forelse ($redemptions as $redemption)
                                <article class="rounded-[1.25rem] bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-slate-950">{{ $redemption['gift_name'] }}</h3>
                                            <p class="mt-1 text-xs text-slate-500">{{ $redemption['requested_at'] ?: 'Chưa có ngày' }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $redemption['status_label'] ?? ($redemption['status'] ?: 'Đang cập nhật') }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-600">{{ filled($redemption['points'] ?? null) ? $formatNumber($redemption['points']).' điểm' : 'Điểm đang cập nhật' }}</p>
                                    @if (filled($redemption['note'] ?? null))
                                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $redemption['note'] }}</p>
                                    @endif
                                </article>
                            @empty
                                <p class="rounded-[1.25rem] bg-slate-50 p-4 text-sm text-slate-500">
                                    {{ ($sections['redemptions'] ?? false) ? 'Chưa có lịch sử đổi quà.' : 'API chưa trả về lịch sử đổi quà.' }}
                                </p>
                            @endforelse
                        </div>
                    </section>
                </div>
        @endif

            <section id="customer-loyalty-gifts" data-customer-loyalty-gifts class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm scroll-mt-24">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="frontsite-h2-card">Danh sách quà có thể đổi</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-500">
                            @if ($canRedeemGifts)
                                Bấm đổi để gửi yêu cầu. Hải Đăng Travel sẽ liên hệ lại để xác nhận trước khi xử lý điểm.
                            @else
                                Danh sách quà hiển thị trước để tham khảo. Nhập số điện thoại để kiểm tra điểm và gửi yêu cầu đổi quà.
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-primary">
                        {{ $lookup ? $formatNumber($points).' điểm hiện có' : 'Tra cứu điểm để đổi quà' }}
                    </span>
                </div>

                @if ($giftCatalogError)
                    <div class="mt-5 rounded-[1.25rem] border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-800">
                        {{ $giftCatalogError }}
                    </div>
                @endif

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($gifts as $gift)
                        <article class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
                            <div class="aspect-[4/3] bg-slate-100">
                                @if (filled($gift['image_url'] ?? null))
                                    <img src="{{ $gift['image_url'] }}" alt="{{ $gift['title'] }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-5xl text-orange-200">
                                        <i class="fa-solid fa-gift"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="min-h-12 text-base font-semibold leading-6 text-slate-950">{{ $gift['title'] }}</h3>
                                @if (filled($gift['description'] ?? null))
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{{ $gift['description'] }}</p>
                                @endif

                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-3 py-1 text-sm font-bold text-primary">
                                        <i class="fa-solid fa-coins"></i>
                                        {{ $formatNumber($gift['required_points']) }}
                                    </span>

                                    @if ($canRedeemGifts)
                                        <form
                                            action="{{ route('customer-loyalty.redeem') }}"
                                            method="POST"
                                            data-frontsite-recaptcha-form
                                            data-frontsite-recaptcha-action="customer_loyalty_redeem"
                                            data-customer-loyalty-redemption-form
                                            data-customer-loyalty-gift-title="{{ $gift['title'] }}"
                                            data-customer-loyalty-phone="{{ $displayPhone }}"
                                        >
                                            @csrf
                                            @include('themes.haidangtravel.partials.recaptcha-v3-field')
                                            <input type="hidden" name="phone" value="{{ $displayPhone }}">
                                            <input type="hidden" name="gift_id" value="{{ $gift['id'] }}">
                                            <input type="hidden" name="gift_name" value="{{ $gift['title'] }}">
                                            <input type="hidden" name="customer_id" value="{{ $customer['id'] ?? '' }}">
                                            <input type="hidden" name="amount" value="1">
                                            <button
                                                type="submit"
                                                @disabled(! ($gift['can_redeem'] ?? false) || blank($gift['id'] ?? null))
                                                class="inline-flex items-center gap-2 rounded-[0.9rem] px-4 py-2 text-sm font-bold transition {{ ($gift['can_redeem'] ?? false) && filled($gift['id'] ?? null) ? 'bg-primary text-white hover:bg-orange-700' : 'cursor-not-allowed bg-slate-100 text-slate-400' }}"
                                            >
                                                Đổi
                                            </button>
                                        </form>
                                    @else
                                        <a
                                            href="#customer-loyalty-phone"
                                            class="inline-flex items-center gap-2 rounded-[0.9rem] bg-slate-100 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-orange-50 hover:text-primary"
                                        >
                                            Nhập SĐT để đổi
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.25rem] bg-slate-50 p-5 text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                            @if (! $apiConfigured)
                                Cổng API quà chưa được cấu hình nên chưa thể tải danh sách quà.
                            @elseif ($giftCatalogError)
                                Chưa tải được danh sách quà. Vui lòng thử lại sau.
                            @else
                                {{ ($giftSections['gifts'] ?? false) ? 'Chưa có quà phù hợp với số điểm hiện tại.' : 'API chưa trả về danh sách quà có thể đổi.' }}
                            @endif
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($canRedeemGifts)
            <div
                data-customer-loyalty-redemption-modal
                class="fixed inset-0 z-[90] hidden items-center justify-center px-4 py-6"
                aria-hidden="true"
            >
                <button
                    type="button"
                    data-customer-loyalty-redemption-backdrop
                    class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"
                    aria-label="Đóng hộp xác nhận"
                ></button>

                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="customer-loyalty-redemption-title"
                    class="relative w-full max-w-md rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-950/25"
                >
                    <button
                        type="button"
                        data-customer-loyalty-redemption-close
                        class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-orange-100"
                        aria-label="Đóng hộp xác nhận"
                    >
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>

                    <div class="flex items-start gap-3 pr-10">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-orange-50 text-primary">
                            <i class="fa-solid fa-gift" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Xác nhận đổi quà</p>
                            <h2 id="customer-loyalty-redemption-title" data-customer-loyalty-redemption-title class="mt-1 text-xl font-extrabold text-slate-950">
                                Gửi yêu cầu đổi quà
                            </h2>
                        </div>
                    </div>

                    <p data-customer-loyalty-redemption-message class="mt-4 text-sm leading-7 text-slate-600">
                        Chúng tôi sẽ liên hệ lại với bạn để xác nhận yêu cầu đổi quà này.
                    </p>

                    <div data-customer-loyalty-redemption-feedback class="mt-4 hidden rounded-[1rem] border px-4 py-3 text-sm font-semibold" role="status" aria-live="polite"></div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            data-customer-loyalty-redemption-close
                            class="inline-flex min-h-11 items-center justify-center rounded-[0.95rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                        >
                            Hủy
                        </button>
                        <button
                            type="button"
                            data-customer-loyalty-redemption-submit
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[0.95rem] bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100"
                        >
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            Gửi yêu cầu
                        </button>
                    </div>
                </div>
            </div>
            @endif

            @if ($featuredTours->isNotEmpty())
                <section class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div
                        data-card-carousel
                        data-autoplay="true"
                        data-desktop-slider="true"
                        data-interval="4600"
                        style="--desktop-columns: 4; --desktop-card-width: calc((100% - 3rem) / 4); --mobile-card-width: calc(83.333% - 0.17rem);"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-primary">Gợi ý hành trình</p>
                                <h2 class="frontsite-h2-card mt-2">Tour nổi bật</h2>
                                <p class="mt-2 text-sm leading-7 text-slate-500">Một số tour đang được ưu tiên để khách hàng tham khảo thêm sau khi kiểm tra điểm thưởng.</p>
                            </div>

                            @if ($featuredTours->count() > 1)
                                <div class="frontsite-slider-nav flex items-center gap-3">
                                    <button
                                        type="button"
                                        data-card-carousel-prev
                                        class="service-card-carousel-control"
                                        aria-label="Xem tour nổi bật trước"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                    </button>
                                    <button
                                        type="button"
                                        data-card-carousel-next
                                        class="service-card-carousel-control"
                                        aria-label="Xem tour nổi bật tiếp theo"
                                    >
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="mt-5">
                            <div class="service-card-carousel-track" data-card-carousel-track>
                                @foreach ($featuredTours as $tour)
                                    <div class="service-card-carousel-item" data-card-carousel-item>
                                        @include('themes.haidangtravel.partials.tour-card', [
                                            'tour' => $tour,
                                            'variant' => 'default',
                                            'showRating' => true,
                                            'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', ''),
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endif
    </section>
@endsection
