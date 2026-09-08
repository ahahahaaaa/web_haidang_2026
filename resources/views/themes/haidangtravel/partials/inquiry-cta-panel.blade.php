@props([
    'badge' => 'Thông tin đặt tour',
    'title' => 'Gửi yêu cầu để đội ngũ liên hệ và tư vấn nhanh hơn.',
    'description' => 'Popup form dùng chung cho toàn bộ frontsite, phù hợp khi khách cần tour, dịch vụ đi kèm hoặc tư vấn theo nhu cầu riêng.',
    'buttonLabel' => 'Mở form đặt tour',
    'source' => 'general',
    'tourId' => null,
    'serviceId' => null,
    'contextTitle' => 'Liên hệ chung',
    'subject' => 'Tư vấn du lịch',
    'modalTitle' => 'Thông tin đặt tour',
    'modalDescription' => 'Điền nhanh thông tin đặt tour để Hải Đăng Travel liên hệ và tư vấn đúng nhu cầu của bạn.',
])

<div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.35)] sm:p-8">
    @if (filled($title))
        <h2 class="frontsite-h2-compact">{{ $title }}</h2>
    @endif

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="rounded-[1.25rem] bg-slate-50 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Biểu mẫu dùng chung</h3>
            <p class="mt-2 text-sm leading-7 text-slate-600">Một form thống nhất cho tour, dịch vụ và nhu cầu liên hệ chung trên toàn frontsite.</p>
        </div>
        <div class="rounded-[1.25rem] bg-slate-50 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Trường thông tin</h3>
            <p class="mt-2 text-sm leading-7 text-slate-600">Gồm loại yêu cầu, liên hệ, số khách người lớn, số trẻ em, địa chỉ, tiêu đề và nội dung chi tiết.</p>
        </div>
    </div>

    <button
        type="button"
        class="mt-6 inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover"
        data-travel-inquiry-open
        data-travel-inquiry-source="{{ $source }}"
        data-travel-inquiry-tour-id="{{ $tourId }}"
        data-travel-inquiry-service-id="{{ $serviceId }}"
        data-travel-inquiry-context="{{ $contextTitle }}"
        data-travel-inquiry-subject="{{ $subject }}"
        data-travel-inquiry-modal-title="{{ $modalTitle }}"
        data-travel-inquiry-modal-description="{{ $modalDescription }}"
    >
        <i class="fa-regular fa-paper-plane"></i>
        {{ $buttonLabel }}
    </button>
</div>
