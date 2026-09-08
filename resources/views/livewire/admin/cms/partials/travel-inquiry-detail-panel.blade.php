@php
    $selectedLegacyAdultGuestCount = data_get($selectedInquiry->meta, 'company_name');
    $selectedAdultGuestCount = data_get($selectedInquiry->meta, 'adult_guest_count', is_numeric($selectedLegacyAdultGuestCount) ? $selectedLegacyAdultGuestCount : null);
    $selectedChildGuestCount = $selectedInquiry->party_size;
    $selectedVoucherVariant = data_get($selectedInquiry->meta, 'voucher_variant', data_get($selectedInquiry->meta, 'ab_variant'));
    $selectedExpectedDestination = data_get($selectedInquiry->meta, 'expected_destination');
    $selectedExpectedTime = data_get($selectedInquiry->meta, 'expected_time');
@endphp

<div class="space-y-4 rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->customer_name }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedInquiry->source->label() }} · {{ $selectedInquiry->context_title ?: 'Yêu cầu chung' }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="markStatus({{ $selectedInquiry->id }}, 'contacted')" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:text-zinc-200">Đánh dấu đã liên hệ</button>
            <button type="button" wire:click="markStatus({{ $selectedInquiry->id }}, 'closed')" class="rounded-2xl bg-teal-600 px-3 py-2 text-sm font-semibold text-white">Đóng yêu cầu</button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Điện thoại</p>
            <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->customer_phone }}</p>
        </div>
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Email</p>
            <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->customer_email ?: 'Chưa cung cấp' }}</p>
        </div>
        @if ($selectedExpectedDestination)
            <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Điểm đến dự kiến</p>
                <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedExpectedDestination }}</p>
            </div>
        @endif
        @if ($selectedExpectedTime)
            <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Thời gian đi dự kiến</p>
                <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedExpectedTime }}</p>
            </div>
        @endif
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Ngày đi dự kiến</p>
            <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ optional($selectedInquiry->travel_date)->format('d/m/Y') ?: 'Chưa có' }}</p>
        </div>
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Số khách người lớn</p>
            <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ filled($selectedAdultGuestCount) ? $selectedAdultGuestCount : 'Chưa có' }}</p>
        </div>
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Số trẻ em</p>
            <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ filled($selectedChildGuestCount) ? $selectedChildGuestCount : 'Chưa có' }}</p>
        </div>
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Ngày tạo</p>
            <p class="mt-2 whitespace-nowrap font-semibold text-zinc-900 dark:text-white">{{ optional($selectedInquiry->created_at)->format('d/m/Y H:i:s') ?: 'Chưa có' }}</p>
        </div>
    </div>

    @if ($selectedInquiry->message)
        <div class="rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Nội dung yêu cầu</p>
            <div class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $selectedInquiry->message }}</div>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @if (data_get($selectedInquiry->meta, 'voucher.code'))
            <div class="rounded-2xl bg-orange-50 p-4 dark:bg-orange-500/10">
                <p class="text-xs uppercase tracking-[0.2em] text-orange-700 dark:text-orange-300">Voucher</p>
                <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ data_get($selectedInquiry->meta, 'voucher.code') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ data_get($selectedInquiry->meta, 'voucher.campaign_slug') }}</p>
            </div>
        @endif

        @if ($selectedVoucherVariant)
            <div class="rounded-2xl bg-sky-50 p-4 dark:bg-sky-500/10">
                <p class="text-xs uppercase tracking-[0.2em] text-sky-700 dark:text-sky-300">Biến thể voucher</p>
                <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedVoucherVariant }}</p>
            </div>
        @endif

        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Mail status</p>
            <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->mail_status ?: 'pending' }}</p>
        </div>
        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Trang gửi</p>
            <p class="mt-2 break-all text-sm text-zinc-700 dark:text-zinc-200">{{ $selectedInquiry->page_url ?: 'Chưa có' }}</p>
        </div>
    </div>
</div>
