@php
    $normalizeUrl = static function (?string $url): ?string {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        return \App\Support\FrontsiteUrls::cleanInternalUrl($url, true) ?? $url;
    };
    $contactPhone = $siteSettings->hotline ?: $siteSettings->phone;
    $contactEmail = $siteSettings->primary_email ?: $siteSettings->email;
    $phoneLink = 'tel:'.preg_replace('/\s+/', '', $contactPhone);
    $secondaryLabel = $secondaryLabel ?? 'Xem thêm';
    $secondaryUrl = $normalizeUrl($secondaryUrl ?? route('blog.index'));
    $feedbackMode = session('travel_inquiry_feedback_mode');
    $voucherCampaignSlug = $voucherCampaignSlug ?? null;
    $inquirySubject = filled($title ?? null) ? $title : 'Nhận gợi ý tour và ưu đãi phù hợp';
    $formId = 'tour-offer-'.\Illuminate\Support\Str::uuid();
    $compactSubmissionAttempted = old('submission_mode') === 'compact' || $feedbackMode === 'compact';
    $compactFields = ['customer_name', 'customer_phone', 'message', 'g-recaptcha-response', 'form'];
    $compactErrors = collect($compactFields)
        ->flatMap(fn (string $field) => $errors->get($field))
        ->flatten()
        ->filter()
        ->values();
    $compactHasFeedback = $compactSubmissionAttempted && (session('travel_inquiry_status') || $compactErrors->isNotEmpty());
@endphp

<section class="tour-offer-section px-4 pb-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="tour-offer-shell">
            <div class="tour-offer-layout grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
                <div class="tour-offer-copy flex min-w-0 flex-col gap-6">
                    <div class="frontsite-text-reveal flex flex-col gap-3" data-reveal="title">
                        @if (filled($title ?? null))
                            <h2 class="frontsite-h2 tour-offer-title">{{ $title }}</h2>
                        @endif

                        @if (filled($description ?? null))
                            <p class="text-base leading-7 text-slate-700">
                                {{ $description }}
                            </p>
                        @endif
                    </div>

                    <ul class="tour-offer-benefits frontsite-text-reveal flex flex-col gap-5" data-reveal="body">
                        <li class="flex items-start gap-3">
                            <span class="tour-offer-icon"><i class="fa-solid fa-compass" aria-hidden="true"></i></span>
                            <div class="min-w-0"><h3 class="font-bold text-slate-800">Tour hấp dẫn, đúng gu của bạn</h3><p class="mt-1 text-sm leading-6 text-slate-600">Gợi ý hành trình theo sở thích, lịch đi và ngân sách.</p></div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="tour-offer-icon"><i class="fa-solid fa-gift" aria-hidden="true"></i></span>
                            <div class="min-w-0"><h3 class="font-bold text-slate-800">Khám phá ưu đãi phù hợp</h3><p class="mt-1 text-sm leading-6 text-slate-600">Được tư vấn chương trình khuyến mãi đang áp dụng cho chuyến đi của bạn.</p></div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="tour-offer-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
                            <div class="min-w-0"><h3 class="font-bold text-slate-800">Chưa chốt ngày đi? Vẫn có thể hỏi!</h3><p class="mt-1 text-sm leading-6 text-slate-600">Cùng Hải Đăng Travel tìm lịch khởi hành và phương án phù hợp.</p></div>
                        </li>
                    </ul>

                    <div class="tour-offer-contact frontsite-text-reveal flex flex-col gap-2" data-reveal="body">
                        <p class="text-sm text-slate-600">Muốn trao đổi trực tiếp?</p>
                        @if (filled($contactPhone))
                            <a href="{{ $phoneLink }}" class="inline-flex min-h-11 w-fit items-center gap-2 text-lg font-bold"><i class="fa-solid fa-phone-volume" aria-hidden="true"></i>{{ $contactPhone }}</a>
                        @endif
                        @if (filled($contactEmail))
                            <a href="mailto:{{ $contactEmail }}" class="inline-flex min-h-11 w-fit items-center gap-2 text-sm"><i class="fa-regular fa-envelope" aria-hidden="true"></i><span class="break-all">{{ $contactEmail }}</span></a>
                        @endif
                    </div>
                </div>

                <form
                    action="{{ route('travel-inquiries.store') }}"
                    method="POST"
                    class="tour-offer-form frontsite-text-reveal flex min-w-0 flex-col gap-5"
                    aria-labelledby="{{ $formId }}-title"
                    data-frontsite-ajax-form
                    data-frontsite-recaptcha-form
                    data-frontsite-recaptcha-action="travel_inquiry"
                    data-reveal="panel"
                    data-reveal-delay="0.18"
                    novalidate
                >
                    @csrf
                    <input type="hidden" name="submission_mode" value="compact">
                    <input type="hidden" name="source" value="{{ old('source', 'general') }}">
                    <input type="hidden" name="inquiry_type" value="{{ old('inquiry_type', 'travel') }}">
                    <input type="hidden" name="context_title" value="{{ old('context_title', $inquirySubject) }}">
                    <input type="hidden" name="subject" value="{{ old('subject', $inquirySubject) }}">
                    <input type="hidden" name="page_url" value="{{ old('page_url', url()->current()) }}">
                    <input type="hidden" name="voucher_campaign_slug" value="{{ old('voucher_campaign_slug', $voucherCampaignSlug) }}">
                    @include('themes.haidangtravel.partials.recaptcha-v3-field')

                    <div class="flex flex-col gap-2">
                        <h3 id="{{ $formId }}-title" class="font-heading text-xl font-bold text-slate-800">Nhận gợi ý cho chuyến đi của bạn</h3>
                        <p class="text-sm leading-6 text-slate-600">Để lại tên và số điện thoại, Hải Đăng Travel sẽ liên hệ tư vấn tour cùng ưu đãi phù hợp.</p>
                    </div>

                    <div
                        class="frontsite-form-feedback {{ $compactHasFeedback ? '' : 'hidden' }} {{ $compactErrors->isNotEmpty() ? 'is-error' : 'is-success' }}"
                        data-frontsite-form-feedback
                        role="status"
                        aria-live="polite"
                    >
                        <p class="font-semibold" data-frontsite-form-feedback-message>
                            {{ session('travel_inquiry_status') ?: ($compactErrors->isNotEmpty() ? 'Vui lòng kiểm tra lại các thông tin đã nhập.' : '') }}
                        </p>
                        <ul class="frontsite-form-feedback-list {{ $compactErrors->isNotEmpty() ? '' : 'hidden' }}" data-frontsite-form-feedback-list>
                            @foreach ($compactErrors->take(5) as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex min-w-0 flex-col gap-2">
                            <span class="text-sm font-semibold text-slate-700">Họ tên <span class="text-orange-700" aria-hidden="true">*</span></span>
                            <input
                                type="text"
                                name="customer_name"
                                aria-invalid="{{ $compactSubmissionAttempted && $errors->has('customer_name') ? 'true' : 'false' }}"
                                value="{{ old('customer_name') }}"
                                placeholder="Tên của bạn"
                                autocomplete="name"
                                required
                                maxlength="255"
                                aria-describedby="{{ $formId }}-name-error"
                                class="tour-offer-input frontsite-form-control {{ $compactSubmissionAttempted && $errors->has('customer_name') ? 'is-invalid' : '' }}"
                            >
                            <span id="{{ $formId }}-name-error" class="frontsite-form-field-error {{ old('submission_mode') === 'compact' && $errors->has('customer_name') ? '' : 'hidden' }}" data-frontsite-field-error="customer_name">
                                @if (old('submission_mode') === 'compact')
                                    {{ $errors->first('customer_name') }}
                                @endif
                            </span>
                        </label>

                        <label class="flex min-w-0 flex-col gap-2">
                            <span class="text-sm font-semibold text-slate-700">Số điện thoại <span class="text-orange-700" aria-hidden="true">*</span></span>
                            <input
                                type="tel"
                                inputmode="tel"
                                name="customer_phone"
                                aria-invalid="{{ $compactSubmissionAttempted && $errors->has('customer_phone') ? 'true' : 'false' }}"
                                value="{{ old('customer_phone') }}"
                                placeholder="Số điện thoại liên hệ"
                                autocomplete="tel"
                                required
                                maxlength="50"
                                aria-describedby="{{ $formId }}-phone-error"
                                class="tour-offer-input frontsite-form-control {{ $compactSubmissionAttempted && $errors->has('customer_phone') ? 'is-invalid' : '' }}"
                            >
                            <span id="{{ $formId }}-phone-error" class="frontsite-form-field-error {{ old('submission_mode') === 'compact' && $errors->has('customer_phone') ? '' : 'hidden' }}" data-frontsite-field-error="customer_phone">
                                @if (old('submission_mode') === 'compact')
                                    {{ $errors->first('customer_phone') }}
                                @endif
                            </span>
                        </label>
                    </div>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-slate-700">Bạn muốn đi đâu? <span class="font-normal text-slate-500">(Không bắt buộc)</span></span>
                        <textarea
                            name="message"
                            rows="3"
                            aria-invalid="{{ $compactSubmissionAttempted && $errors->has('message') ? 'true' : 'false' }}"
                            placeholder="Ví dụ: Phú Quốc, 2 người, đi tháng 10, khoảng 5 triệu/người..."
                            maxlength="5000"
                            aria-describedby="{{ $formId }}-message-error"
                            class="tour-offer-input frontsite-form-control {{ $compactSubmissionAttempted && $errors->has('message') ? 'is-invalid' : '' }}"
                        >{{ old('message') }}</textarea>
                        <span id="{{ $formId }}-message-error" class="frontsite-form-field-error {{ old('submission_mode') === 'compact' && $errors->has('message') ? '' : 'hidden' }}" data-frontsite-field-error="message">
                            @if (old('submission_mode') === 'compact')
                                {{ $errors->first('message') }}
                            @endif
                        </span>
                    </label>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="tour-offer-submit inline-flex min-h-12 w-full items-center justify-center gap-3">
                            <span class="tour-offer-submit-ready">Nhận gợi ý tour & ưu đãi <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                            <span class="tour-offer-submit-busy" role="status">Đang gửi yêu cầu…</span>
                        </button>

                        <p class="text-center text-sm leading-6 text-slate-600">Gửi yêu cầu để được tư vấn, chưa phải đặt tour.<br>Ưu đãi tùy tour và thời điểm áp dụng.</p>

                        @if ($secondaryUrl && $secondaryLabel)
                            <a href="{{ $secondaryUrl }}" class="tour-offer-secondary inline-flex min-h-11 items-center justify-center gap-2 text-sm font-semibold">
                                {{ $secondaryLabel }}
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
