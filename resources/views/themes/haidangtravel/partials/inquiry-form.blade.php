@php
    $errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag);
    $errors = $errors instanceof \Illuminate\Support\ViewErrorBag
        ? $errors
        : new \Illuminate\Support\ViewErrorBag;
    $title = $title ?? 'Gửi yêu cầu tư vấn';
    $description = $description ?? 'Điền form thông tin đặt tour để Hải Đăng Travel tiếp nhận nhu cầu tour, dịch vụ hoặc các thông tin cần hỗ trợ khác ngay trên trang này.';
    $source = $source ?? 'general';
    $tourId = $tourId ?? null;
    $serviceId = $serviceId ?? null;
    $contextTitle = $contextTitle ?? 'Liên hệ chung';
    $subject = $subject ?? ($contextTitle ?: 'Tư vấn du lịch');
    $feedbackMode = session('travel_inquiry_feedback_mode');
    $trackedFields = ['form', 'inquiry_type', 'customer_name', 'customer_email', 'customer_phone', 'adult_guest_count', 'party_size', 'address', 'subject', 'message', 'g-recaptcha-response'];
    $feedbackMessages = collect($trackedFields)
        ->flatMap(fn (string $field) => $errors->get($field))
        ->flatten()
        ->filter()
        ->values();
    $inlineSubmissionAttempted = old('submission_mode') === 'inline' || $feedbackMode === 'inline';
    $formHasFeedback = $inlineSubmissionAttempted && (session('travel_inquiry_status') || $feedbackMessages->isNotEmpty());
@endphp

<div id="travel-inquiry-form" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.35)] sm:p-8">
    <div class="mb-6 max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-[0.26em] text-primary">Form thông tin đặt tour</p>
        <h2 class="frontsite-h2-compact mt-3">{{ $title }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $description }}</p>
    </div>

    <form action="{{ route('travel-inquiries.store') }}" method="POST" class="grid gap-5" data-frontsite-ajax-form data-frontsite-recaptcha-form data-frontsite-recaptcha-action="travel_inquiry" novalidate>
        @csrf
        @include('themes.haidangtravel.partials.travel-inquiry-form-fields', [
            'contextTitle' => $contextTitle,
            'feedbackId' => 'travel-inquiry-inline-feedback',
            'selectVariant' => 'form',
            'showFeedback' => $formHasFeedback,
            'submissionAttempted' => $inlineSubmissionAttempted,
            'submissionMode' => 'inline',
            'source' => $source,
            'subject' => $subject,
            'tourId' => $tourId,
            'serviceId' => $serviceId,
        ])
    </form>
</div>
