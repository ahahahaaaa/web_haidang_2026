@php
    $feedbackId = $feedbackId ?? null;
    $selectVariant = $selectVariant ?? 'form';
    $showFeedback = $showFeedback ?? false;
    $source = $source ?? 'general';
    $tourId = $tourId ?? null;
    $serviceId = $serviceId ?? null;
    $contextTitle = $contextTitle ?? 'Liên hệ chung';
    $subject = $subject ?? 'Tư vấn du lịch';
    $submissionAttempted = $submissionAttempted ?? false;
    $submissionMode = $submissionMode ?? 'modal';
    $isTravelInquiryModal = $isTravelInquiryModal ?? false;
    $submitLabel = $submitLabel ?? 'Gửi yêu cầu';
    $inquiryTypeOptions = [
        'travel' => 'Du lịch',
        'customer_care' => 'Chăm sóc khách hàng',
        'other' => 'Liên hệ thông tin khác',
    ];
    $trackedFields = [
        'form',
        'inquiry_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'adult_guest_count',
        'party_size',
        'address',
        'subject',
        'message',
    ];
    $feedbackMessages = collect($trackedFields)
        ->flatMap(fn (string $field) => $errors->get($field))
        ->flatten()
        ->filter()
        ->values();
@endphp

<input type="hidden" name="submission_mode" value="{{ $submissionMode }}">
<input type="hidden" name="source" value="{{ old('source', $source) }}" data-travel-inquiry-source-input>
<input type="hidden" name="tour_id" value="{{ old('tour_id', $tourId) }}" data-travel-inquiry-tour-id-input>
<input type="hidden" name="service_id" value="{{ old('service_id', $serviceId) }}" data-travel-inquiry-service-id-input>
<input type="hidden" name="context_title" value="{{ old('context_title', $contextTitle) }}" data-travel-inquiry-context-input>
<input type="hidden" name="page_url" value="{{ old('page_url', url()->current()) }}" data-travel-inquiry-page-url-input>

<div
    @if ($feedbackId)
        id="{{ $feedbackId }}"
    @endif
    class="frontsite-form-feedback {{ $showFeedback ? '' : 'hidden' }} {{ $feedbackMessages->isNotEmpty() ? 'is-error' : 'is-success' }}"
    data-frontsite-form-feedback
    @if ($isTravelInquiryModal)
        data-travel-inquiry-feedback
    @endif
    tabindex="-1"
    role="status"
    aria-live="polite"
>
    <p class="font-semibold" data-frontsite-form-feedback-message>
        {{ session('travel_inquiry_status') ?: ($feedbackMessages->isNotEmpty() ? 'Vui lòng kiểm tra lại các thông tin đã nhập.' : '') }}
    </p>
    <ul class="frontsite-form-feedback-list {{ $feedbackMessages->isNotEmpty() ? '' : 'hidden' }}" data-frontsite-form-feedback-list>
        @foreach ($feedbackMessages->take(5) as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
</div>

<div class="grid gap-5 md:grid-cols-3">
    <label class="space-y-2">
        <span class="text-sm font-semibold text-slate-900">Loại thông tin <span class="text-danger">*</span></span>
        <select
            name="inquiry_type"
            aria-invalid="{{ $submissionAttempted && $errors->has('inquiry_type') ? 'true' : 'false' }}"
            class="frontsite-form-select w-full {{ $submissionAttempted && $errors->has('inquiry_type') ? 'is-invalid' : '' }}"
            data-frontsite-select
            data-frontsite-select-search="false"
            data-frontsite-select-variant="{{ $selectVariant }}"
        >
            @foreach ($inquiryTypeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('inquiry_type', 'travel') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('inquiry_type') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="inquiry_type">
            @if ($submissionAttempted)
                {{ $errors->first('inquiry_type') }}
            @endif
        </p>
    </label>

    <label class="space-y-2">
        <span class="text-sm font-semibold text-slate-900">Họ tên <span class="text-danger">*</span></span>
        <input
            type="text"
            name="customer_name"
            aria-invalid="{{ $submissionAttempted && $errors->has('customer_name') ? 'true' : 'false' }}"
            value="{{ old('customer_name') }}"
            placeholder="Liên hệ"
            autocomplete="name"
            class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('customer_name') ? 'is-invalid' : '' }}"
        >
        <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('customer_name') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="customer_name">
            @if ($submissionAttempted)
                {{ $errors->first('customer_name') }}
            @endif
        </p>
    </label>

    <label class="space-y-2">
        <span class="text-sm font-semibold text-slate-900">Email <span class="font-normal text-slate-500">(không bắt buộc)</span></span>
        <input
            type="email"
            name="customer_email"
            aria-invalid="{{ $submissionAttempted && $errors->has('customer_email') ? 'true' : 'false' }}"
            value="{{ old('customer_email') }}"
            placeholder="Nhập email nếu có"
            autocomplete="email"
            class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('customer_email') ? 'is-invalid' : '' }}"
        >
        <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('customer_email') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="customer_email">
            @if ($submissionAttempted)
                {{ $errors->first('customer_email') }}
            @endif
        </p>
    </label>

    <label class="space-y-2">
        <span class="text-sm font-semibold text-slate-900">Điện thoại <span class="text-danger">*</span></span>
        <input
            type="text"
            name="customer_phone"
            aria-invalid="{{ $submissionAttempted && $errors->has('customer_phone') ? 'true' : 'false' }}"
            value="{{ old('customer_phone') }}"
            placeholder="Nhập số điện thoại"
            autocomplete="tel"
            class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('customer_phone') ? 'is-invalid' : '' }}"
        >
        <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('customer_phone') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="customer_phone">
            @if ($submissionAttempted)
                {{ $errors->first('customer_phone') }}
            @endif
        </p>
    </label>

    <label class="space-y-2">
        <span class="text-sm font-semibold text-slate-900">Số khách người lớn</span>
        <input
            type="number"
            min="0"
            name="adult_guest_count"
            aria-invalid="{{ $submissionAttempted && $errors->has('adult_guest_count') ? 'true' : 'false' }}"
            value="{{ old('adult_guest_count') }}"
            placeholder="Nhập số khách người lớn"
            inputmode="numeric"
            class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('adult_guest_count') ? 'is-invalid' : '' }}"
        >
        <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('adult_guest_count') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="adult_guest_count">
            @if ($submissionAttempted)
                {{ $errors->first('adult_guest_count') }}
            @endif
        </p>
    </label>

    <label class="space-y-2">
        <span class="text-sm font-semibold text-slate-900">Số trẻ em</span>
        <input
            type="number"
            min="0"
            name="party_size"
            aria-invalid="{{ $submissionAttempted && $errors->has('party_size') ? 'true' : 'false' }}"
            value="{{ old('party_size') }}"
            placeholder="Nhập số trẻ em"
            inputmode="numeric"
            class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('party_size') ? 'is-invalid' : '' }}"
        >
        <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('party_size') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="party_size">
            @if ($submissionAttempted)
                {{ $errors->first('party_size') }}
            @endif
        </p>
    </label>
</div>

<label class="space-y-2">
    <span class="text-sm font-semibold text-slate-900">Địa chỉ</span>
    <input
        type="text"
        name="address"
        aria-invalid="{{ $submissionAttempted && $errors->has('address') ? 'true' : 'false' }}"
        value="{{ old('address') }}"
        placeholder="Nhập địa chỉ"
        autocomplete="street-address"
        class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('address') ? 'is-invalid' : '' }}"
    >
    <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('address') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="address">
        @if ($submissionAttempted)
            {{ $errors->first('address') }}
        @endif
    </p>
</label>

<label class="space-y-2">
    <span class="text-sm font-semibold text-slate-900">Tiêu đề <span class="text-danger">*</span></span>
    <input
        type="text"
        name="subject"
        aria-invalid="{{ $submissionAttempted && $errors->has('subject') ? 'true' : 'false' }}"
        value="{{ old('subject', $subject) }}"
        placeholder="Nhập tiêu đề"
        class="frontsite-form-control min-h-13 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('subject') ? 'is-invalid' : '' }}"
        data-travel-inquiry-subject-input
    >
    <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('subject') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="subject">
        @if ($submissionAttempted)
            {{ $errors->first('subject') }}
        @endif
    </p>
</label>

<label class="space-y-2">
    <span class="text-sm font-semibold text-slate-900">Nội dung <span class="font-normal text-slate-500">(không bắt buộc)</span></span>
    <textarea
        name="message"
        rows="6"
        aria-invalid="{{ $submissionAttempted && $errors->has('message') ? 'true' : 'false' }}"
        placeholder="Nhập nội dung"
        class="frontsite-form-control w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-primary {{ $submissionAttempted && $errors->has('message') ? 'is-invalid' : '' }}"
    >{{ old('message') }}</textarea>
    <p class="frontsite-form-field-error {{ $submissionAttempted && $errors->has('message') ? 'text-danger' : 'hidden text-danger' }}" data-frontsite-field-error="message">
        @if ($submissionAttempted)
            {{ $errors->first('message') }}
        @endif
    </p>
</label>

<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5">
    <p class="text-sm leading-7 text-slate-500">Thông tin bạn sẽ được gửi đến đội ngũ xử lý.</p>

    <button
        type="submit"
        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] px-6 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.68)] transition hover:brightness-105"
    >
        <i class="fa-regular fa-paper-plane"></i>
        {{ $submitLabel }}
    </button>
</div>
