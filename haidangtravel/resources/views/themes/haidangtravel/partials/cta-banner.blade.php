@php
    $normalizeUrl = static function (?string $url): ?string {
        if (! $url) {
            return null;
        }

        foreach (['http://', 'https://', 'mailto:', 'tel:', '#'] as $prefix) {
            if (str_starts_with($url, $prefix)) {
                return $url;
            }
        }

        return url($url);
    };
    $contactPhone = $siteSettings->hotline ?: $siteSettings->phone;
    $contactEmail = $siteSettings->primary_email ?: $siteSettings->email;
    $phoneLink = 'tel:'.preg_replace('/\s+/', '', $contactPhone);
    $secondaryLabel = $secondaryLabel ?? 'Xem thêm';
    $secondaryUrl = $normalizeUrl($secondaryUrl ?? route('blog.index'));
    $feedbackMode = session('travel_inquiry_feedback_mode');
    $compactSubmissionAttempted = old('submission_mode') === 'compact' || $feedbackMode === 'compact';
    $compactFields = ['customer_name', 'customer_phone', 'message'];
    $compactErrors = collect($compactFields)
        ->flatMap(fn (string $field) => $errors->get($field))
        ->flatten()
        ->filter()
        ->values();
    $compactHasFeedback = $compactSubmissionAttempted && (session('travel_inquiry_status') || $compactErrors->isNotEmpty());
@endphp

<section class="px-4 pb-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="overflow-hidden rounded-[1.75rem] bg-secondary px-6 py-6 text-white shadow-2xl shadow-slate-900/20 sm:px-10 lg:px-14 lg:py-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <div class="frontsite-text-reveal max-w-3xl space-y-3" data-reveal="title">
                    @if (filled($title ?? null))
                        <h2 class="frontsite-h2 frontsite-h2-inverse">{{ $title }}</h2>
                    @endif

                    @if (filled($description ?? null))
                        <p class="text-sm leading-7 text-slate-100/88 sm:text-base">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                <div class="frontsite-text-reveal grid gap-4 md:grid-cols-2" data-reveal="panel">
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/8 p-5 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.55)]">
                        <h3 class="text-sm font-semibold text-orange-100">Hotline</h3>
                        <a href="{{ $phoneLink }}" class="mt-3 block font-heading text-2xl font-bold text-white sm:text-[1.85rem]">{{ $contactPhone }}</a>
                        <p class="mt-2 text-sm leading-6 text-slate-200/82">Hỗ trợ tư vấn nhanh theo ngày đi, số lượng khách và nhu cầu hành trình.</p>
                    </div>

                    <div class="rounded-[1.35rem] border border-white/10 bg-white/8 p-5 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.55)]">
                        <h3 class="text-sm font-semibold text-orange-100">Email</h3>
                        <a href="mailto:{{ $contactEmail }}" class="mt-3 block break-all text-lg font-semibold text-white sm:text-xl">{{ $contactEmail }}</a>
                        <p class="mt-2 text-sm leading-6 text-slate-200/82">Phù hợp khi bạn cần gửi yêu cầu chi tiết hoặc để lại thêm thông tin cho đội ngũ xử lý.</p>
                    </div>
                </div>

                <form
                    action="{{ route('travel-inquiries.store') }}"
                    method="POST"
                    class="frontsite-text-reveal space-y-5 rounded-[1.5rem] border border-white/10 bg-white/6 p-5 sm:p-6"
                    data-frontsite-ajax-form
                    data-reveal="panel"
                    novalidate
                >
                    @csrf
                    <input type="hidden" name="submission_mode" value="compact">
                    <input type="hidden" name="source" value="{{ old('source', 'general') }}">
                    <input type="hidden" name="inquiry_type" value="{{ old('inquiry_type', 'travel') }}">
                    <input type="hidden" name="context_title" value="{{ old('context_title', $title) }}">
                    <input type="hidden" name="subject" value="{{ old('subject', $title) }}">
                    <input type="hidden" name="page_url" value="{{ old('page_url', url()->current()) }}">

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

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-100">Họ tên</span>
                            <input
                                type="text"
                                name="customer_name"
                                aria-invalid="{{ $compactSubmissionAttempted && $errors->has('customer_name') ? 'true' : 'false' }}"
                                value="{{ old('customer_name') }}"
                                placeholder="Nhập họ tên"
                                autocomplete="name"
                                class="frontsite-form-control min-h-12 w-full rounded-[1rem] border px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-300 {{ $compactSubmissionAttempted && $errors->has('customer_name') ? 'is-invalid border-orange-200 bg-white/14' : 'border-white/12 bg-white/10 focus:border-orange-200 focus:bg-white/14' }}"
                            >
                            <p class="frontsite-form-field-error {{ old('submission_mode') === 'compact' && $errors->has('customer_name') ? 'text-orange-100' : 'hidden text-orange-100' }}" data-frontsite-field-error="customer_name">
                                @if (old('submission_mode') === 'compact')
                                    {{ $errors->first('customer_name') }}
                                @endif
                            </p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-100">Số điện thoại</span>
                            <input
                                type="text"
                                name="customer_phone"
                                aria-invalid="{{ $compactSubmissionAttempted && $errors->has('customer_phone') ? 'true' : 'false' }}"
                                value="{{ old('customer_phone') }}"
                                placeholder="Nhập số điện thoại"
                                autocomplete="tel"
                                class="frontsite-form-control min-h-12 w-full rounded-[1rem] border px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-300 {{ $compactSubmissionAttempted && $errors->has('customer_phone') ? 'is-invalid border-orange-200 bg-white/14' : 'border-white/12 bg-white/10 focus:border-orange-200 focus:bg-white/14' }}"
                            >
                            <p class="frontsite-form-field-error {{ old('submission_mode') === 'compact' && $errors->has('customer_phone') ? 'text-orange-100' : 'hidden text-orange-100' }}" data-frontsite-field-error="customer_phone">
                                @if (old('submission_mode') === 'compact')
                                    {{ $errors->first('customer_phone') }}
                                @endif
                            </p>
                        </label>
                    </div>

                    <label class="space-y-2">
                        <span class="text-sm font-semibold text-slate-100">Nội dung</span>
                        <textarea
                            name="message"
                            rows="4"
                            aria-invalid="{{ $compactSubmissionAttempted && $errors->has('message') ? 'true' : 'false' }}"
                            placeholder="Mô tả ngắn nhu cầu tour, ngày đi hoặc số lượng khách"
                            class="frontsite-form-control w-full rounded-[1rem] border px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-300 {{ $compactSubmissionAttempted && $errors->has('message') ? 'is-invalid border-orange-200 bg-white/14' : 'border-white/12 bg-white/10 focus:border-orange-200 focus:bg-white/14' }}"
                        >{{ old('message') }}</textarea>
                        <p class="frontsite-form-field-error {{ old('submission_mode') === 'compact' && $errors->has('message') ? 'text-orange-100' : 'hidden text-orange-100' }}" data-frontsite-field-error="message">
                            @if (old('submission_mode') === 'compact')
                                {{ $errors->first('message') }}
                            @endif
                        </p>
                    </label>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-[1rem] bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover">
                            Gửi yêu cầu
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        @if ($secondaryUrl && $secondaryLabel)
                            <a href="{{ $secondaryUrl }}" class="inline-flex items-center gap-2 rounded-[1rem] border border-white/15 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                                {{ $secondaryLabel }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
