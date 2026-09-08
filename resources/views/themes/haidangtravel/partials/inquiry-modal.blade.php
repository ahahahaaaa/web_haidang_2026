@php
    $errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag);
    $errors = $errors instanceof \Illuminate\Support\ViewErrorBag
        ? $errors
        : new \Illuminate\Support\ViewErrorBag;
    $trackedFields = [
        'form',
        'inquiry_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'adult_guest_count',
        'party_size',
        'expected_destination',
        'expected_time',
        'address',
        'subject',
        'message',
        'g-recaptcha-response',
    ];
    $feedbackMessages = collect($trackedFields)
        ->flatMap(fn (string $field) => $errors->get($field))
        ->flatten()
        ->filter()
        ->values();
    $feedbackMode = session('travel_inquiry_feedback_mode');
    $modalSubmissionAttempted = old('submission_mode') === 'modal' || $feedbackMode === 'modal';
    $hasInquiryErrors = $modalSubmissionAttempted
        && collect($trackedFields)->contains(fn (string $field) => $errors->has($field));
    $modalHasFeedback = $modalSubmissionAttempted && (session('travel_inquiry_status') || $feedbackMessages->isNotEmpty());
    $openOnLoad = session('travel_inquiry_open_modal') || $hasInquiryErrors;
    $hasVoucherCampaign = filled($voucherCampaignSlug ?? null);
@endphp

<div
    data-travel-inquiry-modal
    class="{{ $openOnLoad ? '' : 'hidden' }} fixed inset-0 z-[80] flex items-start justify-center overflow-y-auto px-4 py-6 sm:px-6 sm:py-10"
    aria-hidden="{{ $openOnLoad ? 'false' : 'true' }}"
    data-open-on-load="{{ $openOnLoad ? 'true' : 'false' }}"
    data-default-source="general"
    data-default-context="Liên hệ chung"
    data-default-subject="{{ $hasVoucherCampaign ? 'Đăng ký nhận voucher du lịch 200.000đ' : 'Tư vấn du lịch' }}"
    data-default-voucher-campaign="{{ $voucherCampaignSlug ?? '' }}"
    data-default-title="{{ $hasVoucherCampaign ? 'Nhận voucher du lịch' : 'Thông tin đặt tour' }}"
    data-default-description="{{ $hasVoucherCampaign ? 'Để lại thông tin để Hải Đăng Travel giữ voucher và tư vấn tour phù hợp với nhu cầu của bạn.' : 'Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn.' }}"
    data-feedback-focus-on-load="{{ ($modalSubmissionAttempted && (session('travel_inquiry_status') || $feedbackMessages->isNotEmpty())) ? 'true' : 'false' }}"
>
    <button type="button" class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" data-travel-inquiry-backdrop aria-label="Đóng form liên hệ"></button>

    <div
        class="relative z-10 w-full max-w-5xl overscroll-contain rounded-[2rem] bg-white p-5 shadow-[0_40px_120px_-48px_rgba(15,23,42,0.58)] sm:p-8"
        data-travel-inquiry-dialog
        role="dialog"
        aria-modal="true"
        aria-labelledby="travel-inquiry-modal-title"
        aria-describedby="travel-inquiry-modal-description travel-inquiry-modal-feedback"
        tabindex="-1"
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <p id="travel-inquiry-modal-title" class="text-sm font-semibold uppercase tracking-[0.26em] text-primary" data-travel-inquiry-modal-title>
                    Thông tin đặt tour
                </p>
                <p id="travel-inquiry-modal-description" class="mt-3 max-w-2xl text-sm leading-7 text-slate-600" data-travel-inquiry-modal-description>
                    Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex aspect-square min-h-[46px] min-w-[46px] shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white p-[15px] leading-none text-slate-500 transition hover:border-orange-200 hover:text-primary"
                data-travel-inquiry-close
                aria-label="Đóng form liên hệ"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('travel-inquiries.store') }}" method="POST" class="mt-6 grid gap-5" data-frontsite-ajax-form data-frontsite-recaptcha-form data-frontsite-recaptcha-action="travel_inquiry" novalidate>
            @csrf
            @include('themes.haidangtravel.partials.travel-inquiry-form-fields', [
                'contextTitle' => 'Liên hệ chung',
                'feedbackId' => 'travel-inquiry-modal-feedback',
                'isTravelInquiryModal' => true,
                'selectVariant' => 'form',
                'showFeedback' => $modalHasFeedback,
                'submissionAttempted' => $modalSubmissionAttempted,
                'submissionMode' => 'modal',
                'source' => 'general',
                'subject' => $hasVoucherCampaign ? 'Đăng ký nhận voucher du lịch 200.000đ' : 'Tư vấn du lịch',
                'formVariant' => $hasVoucherCampaign ? 'voucher' : 'default',
                'submitLabel' => $hasVoucherCampaign ? 'Nhận Voucher' : 'Đặt tour',
                'voucherCampaignSlug' => $voucherCampaignSlug ?? null,
            ])
        </form>
    </div>
</div>
