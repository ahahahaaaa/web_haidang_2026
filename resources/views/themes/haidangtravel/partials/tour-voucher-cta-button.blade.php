@php
    $voucherCta = $voucherCta ?? ($tourVoucherCta ?? null);
    $campaign = data_get($voucherCta, 'campaign');
    $href = trim((string) data_get($voucherCta, 'url'));
    $label = trim((string) ($label ?? 'Nhận voucher du lịch')) ?: 'Nhận voucher du lịch';
    $campaignTitle = trim((string) ($campaign?->title ?? ''));
    $containerClasses = $containerClasses ?? 'frontsite-text-reveal flex justify-center py-2';
    $innerClasses = $innerClasses ?? 'max-w-2xl text-center';
    $buttonClasses = $buttonClasses ?? 'tour-voucher-cta-button inline-flex min-h-[72px] max-w-full flex-wrap items-center justify-center gap-3 rounded-full bg-[linear-gradient(135deg,#FF6A00,#FF8C00)] px-9 py-[18px] text-[1.3125rem] font-bold leading-7 text-white shadow-[0_20px_45px_-24px_rgba(255,106,0,0.78)] transition hover:-translate-y-0.5 hover:shadow-[0_24px_55px_-24px_rgba(255,106,0,0.82)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2';
    $ariaLabel = $campaignTitle !== '' ? $label.' - '.$campaignTitle : $label;
@endphp

@if ($href !== '')
    <div class="{{ $containerClasses }}" data-reveal="panel">
        <div class="{{ $innerClasses }}">
            <a
                href="{{ $href }}"
                class="{{ $buttonClasses }}"
                aria-label="{{ $ariaLabel }}"
                target="_blank"
                rel="noopener noreferrer"
            >
                <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                <span>{{ $label }}</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
@endif
