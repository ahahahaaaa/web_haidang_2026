@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $cover = \App\Support\FrontsiteMedia::modelUrl($tour, 'cover', \App\Support\FrontsiteMedia::SIZE_FULL, 'cover_image_url');
        $reviewItems = collect($reviewItems ?? [])->filter(fn ($item) => is_array($item))->values();
        $reviewSummary = is_array($reviewSummary ?? null) ? $reviewSummary : null;
        $reviewBatch = $reviewBatch ?? null;
        $batchDepartureDate = $reviewBatch?->departure_date?->format('d/m/Y');
        $averageLabel = filled(data_get($reviewSummary, 'average_value'))
            ? number_format((float) data_get($reviewSummary, 'average_value'), 1, ',', '.')
            : null;
        $reviewCount = is_numeric(data_get($reviewSummary, 'count')) ? (int) data_get($reviewSummary, 'count') : 0;
        $statusMessage = session('tour_review_status');
        $reviewFields = ['author_name', 'author_email', 'author_phone', 'author_title', 'title', 'rating_value', 'content', 'g-recaptcha-response'];
        $reviewFeedbackMessages = collect($reviewFields)->flatMap(fn (string $field) => $errors->get($field))->filter()->values();
        $unlockFeedbackMessages = collect(['review_password'])->flatMap(fn (string $field) => $errors->get($field))->filter()->values();
    @endphp

    <section class="relative overflow-hidden bg-[#0d1730] text-white">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $tour->cover_alt ?: $tour->title }}" class="absolute inset-0 h-full w-full object-cover opacity-35" loading="eager" fetchpriority="high">
        @endif
        <div class="absolute inset-0 bg-slate-950/60"></div>

        <div class="relative mx-auto grid min-h-[54vh] max-w-7xl items-end gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[1fr,22rem] lg:px-8">
            <div class="max-w-3xl">
                <a href="{{ route('tours.show', $tour) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-100 transition hover:text-white">
                    <i class="fa-solid fa-arrow-left"></i>
                    Về trang tour
                </a>
                <h1 class="mt-5 font-heading text-4xl font-bold leading-tight sm:text-5xl">
                    Đánh giá tour {{ $tour->title }}
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-100">
                    Cảm ơn bạn đã đồng hành cùng Hải Đăng Travel{{ $batchDepartureDate ? ' trong chuyến khởi hành '.$batchDepartureDate : '' }}. Điểm và nhận xét của bạn giúp đội ngũ cải thiện dịch vụ và hỗ trợ khách sau tốt hơn.
                </p>
            </div>

            <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-orange-100">Kết quả hiện tại</p>
                @if ($averageLabel)
                    <p class="mt-4 font-heading text-5xl font-bold leading-none">{{ $averageLabel }}/5</p>
                    <p class="mt-3 text-sm leading-7 text-slate-100">{{ $reviewCount }} đánh giá đã được duyệt và hiển thị.</p>
                @else
                    <p class="mt-4 text-sm leading-7 text-slate-100">Tour này đang chờ những đánh giá đầu tiên từ khách hàng sau chuyến đi.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-[color:var(--color-bg)] py-10">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr),24rem] lg:px-8">
            <div class="space-y-8">
                @if ($reviewItems->isNotEmpty())
                    @include('themes.haidangtravel.partials.review-grid', [
                        'description' => 'Một số đánh giá đã được đội ngũ kiểm duyệt và công khai trên trang tour.',
                        'items' => $reviewItems,
                        'sectionId' => 'tour-review-results',
                        'summary' => $reviewSummary,
                        'title' => 'Đánh giá đã hiển thị',
                    ])
                @endif

                <section id="tour-review-form" class="theme-panel p-5 sm:p-7">
                    @if ($reviewUnlocked)
                        <div class="mb-6">
                            <h2 class="frontsite-h2-compact">Gửi đánh giá của bạn</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Đánh giá mới sẽ được lưu ở trạng thái chờ duyệt trước khi hiển thị ngoài trang tour.</p>
                        </div>

                        <form action="{{ route('tour-reviews.public.store', ['tour' => $tour, 'token' => $token]) }}" method="POST" class="grid gap-5" data-frontsite-ajax-form data-frontsite-recaptcha-form data-frontsite-recaptcha-action="tour_review" novalidate>
                            @csrf
                            @include('themes.haidangtravel.partials.recaptcha-v3-field')

                            <div
                                class="frontsite-form-feedback {{ $statusMessage || $reviewFeedbackMessages->isNotEmpty() ? ($reviewFeedbackMessages->isNotEmpty() ? 'is-error' : 'is-success') : 'hidden' }}"
                                data-frontsite-form-feedback
                                role="status"
                                tabindex="-1"
                            >
                                <p class="font-semibold" data-frontsite-form-feedback-message>
                                    {{ $reviewFeedbackMessages->isNotEmpty() ? 'Vui lòng kiểm tra lại thông tin đánh giá.' : $statusMessage }}
                                </p>
                                <ul class="frontsite-form-feedback-list {{ $reviewFeedbackMessages->isNotEmpty() ? '' : 'hidden' }}" data-frontsite-form-feedback-list>
                                    @foreach ($reviewFeedbackMessages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Họ tên</span>
                                    <input type="text" name="author_name" value="{{ old('author_name') }}" autocomplete="name" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>
                                    <p class="frontsite-form-field-error {{ $errors->has('author_name') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_name">{{ $errors->first('author_name') }}</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Số điện thoại</span>
                                    <input type="text" name="author_phone" value="{{ old('author_phone') }}" autocomplete="tel" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>
                                    <p class="frontsite-form-field-error {{ $errors->has('author_phone') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_phone">{{ $errors->first('author_phone') }}</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Email</span>
                                    <input type="email" name="author_email" value="{{ old('author_email') }}" autocomplete="email" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                    <p class="frontsite-form-field-error {{ $errors->has('author_email') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_email">{{ $errors->first('author_email') }}</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-semibold text-slate-900">Ngữ cảnh chuyến đi</span>
                                    <input type="text" name="author_title" value="{{ old('author_title') }}" placeholder="Gia đình, đoàn công ty, nhóm bạn..." class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                    <p class="frontsite-form-field-error {{ $errors->has('author_title') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="author_title">{{ $errors->first('author_title') }}</p>
                                </label>
                            </div>

                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-900">Tiêu đề ngắn</span>
                                <input type="text" name="title" value="{{ old('title') }}" placeholder="Ví dụ: Lịch trình rõ ràng, tư vấn chu đáo" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary">
                                <p class="frontsite-form-field-error {{ $errors->has('title') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="title">{{ $errors->first('title') }}</p>
                            </label>

                            <fieldset class="space-y-3">
                                <legend class="text-sm font-semibold text-slate-900">Điểm đánh giá</legend>
                                <div class="grid grid-cols-5 gap-2">
                                    @for ($rating = 1; $rating <= 5; $rating++)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating_value" value="{{ $rating }}" class="peer sr-only" @checked((string) old('rating_value', '5') === (string) $rating)>
                                            <span class="block rounded-2xl border border-orange-100 bg-orange-50 px-3 py-3 text-center text-sm font-semibold text-primary transition hover:border-orange-300 peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">
                                                <span class="block text-lg"><i class="fa-solid fa-star text-[color:var(--color-warning)]"></i></span>
                                                {{ $rating }}/5
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                                <p class="frontsite-form-field-error {{ $errors->has('rating_value') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="rating_value">{{ $errors->first('rating_value') }}</p>
                            </fieldset>

                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-900">Nội dung đánh giá</span>
                                <textarea name="content" rows="6" class="frontsite-form-control w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>{{ old('content') }}</textarea>
                                <p class="frontsite-form-field-error {{ $errors->has('content') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="content">{{ $errors->first('content') }}</p>
                            </label>

                            <p class="frontsite-form-field-error {{ $errors->has('g-recaptcha-response') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="g-recaptcha-response">{{ $errors->first('g-recaptcha-response') }}</p>

                            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_45px_-24px_rgba(255,106,0,0.78)] transition hover:bg-primary-hover">
                                <i class="fa-solid fa-paper-plane"></i>
                                Gửi đánh giá
                            </button>
                        </form>
                    @else
                        <div class="mb-6">
                            <h2 class="frontsite-h2-compact">Nhập mật khẩu đánh giá</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Mật khẩu này được Hải Đăng Travel cung cấp riêng cho khách của tour.</p>
                        </div>

                        <form action="{{ route('tour-reviews.public.unlock', ['tour' => $tour, 'token' => $token]) }}" method="POST" class="grid gap-5" data-frontsite-ajax-form novalidate>
                            @csrf

                            <div
                                class="frontsite-form-feedback {{ $statusMessage || $unlockFeedbackMessages->isNotEmpty() ? ($unlockFeedbackMessages->isNotEmpty() ? 'is-error' : 'is-success') : 'hidden' }}"
                                data-frontsite-form-feedback
                                role="status"
                                tabindex="-1"
                            >
                                <p class="font-semibold" data-frontsite-form-feedback-message>
                                    {{ $unlockFeedbackMessages->isNotEmpty() ? 'Vui lòng kiểm tra mật khẩu đánh giá.' : $statusMessage }}
                                </p>
                                <ul class="frontsite-form-feedback-list {{ $unlockFeedbackMessages->isNotEmpty() ? '' : 'hidden' }}" data-frontsite-form-feedback-list>
                                    @foreach ($unlockFeedbackMessages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-900">Mật khẩu đánh giá</span>
                                <input type="password" name="review_password" autocomplete="one-time-code" class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary" required>
                                <p class="frontsite-form-field-error {{ $errors->has('review_password') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="review_password">{{ $errors->first('review_password') }}</p>
                            </label>

                            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_45px_-24px_rgba(255,106,0,0.78)] transition hover:bg-primary-hover">
                                <i class="fa-solid fa-unlock-keyhole"></i>
                                Mở form đánh giá
                            </button>
                        </form>
                    @endif
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="theme-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tour đang đánh giá</p>
                    <h2 class="mt-3 font-heading text-xl font-bold text-slate-950">{{ $tour->title }}</h2>
                    <dl class="mt-5 grid gap-3 text-sm">
                        @foreach ([
                            'Chủ đề' => $tour->primaryCategory?->name,
                            'Điểm đến' => $tour->destination?->name,
                            'Khởi hành' => $tour->departure_location,
                            'Lượt đánh giá' => collect([$reviewBatch?->label, $batchDepartureDate])->filter()->implode(' - '),
                            'Thời lượng' => trim(collect([
                                filled($tour->duration_days) ? $tour->duration_days.' ngày' : null,
                                filled($tour->duration_nights) ? $tour->duration_nights.' đêm' : null,
                            ])->filter()->implode(' ')),
                        ] as $label => $value)
                            @if (filled($value))
                                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 last:border-b-0 last:pb-0">
                                    <dt class="text-slate-500">{{ $label }}</dt>
                                    <dd class="text-right font-semibold text-slate-900">{{ $value }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            </aside>
        </div>
    </section>
@endsection
