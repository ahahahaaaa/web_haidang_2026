@php
    /** @var \Src\Domains\Cms\Models\VoucherCampaign $campaign */
@endphp

<section
    class="voucher-recall-section hidden"
    data-voucher-campaign
    data-voucher-campaign-slug="{{ $campaign->slug }}"
    data-voucher-remembered-url="{{ route('voucher-campaigns.remembered', ['campaign' => $campaign->slug]) }}"
    aria-live="polite"
>
    <div class="voucher-recall-shell">
        <div>
            <p class="voucher-recall-kicker">Voucher của bạn</p>
            <p class="voucher-recall-title">Mã đã được lưu trên trình duyệt này.</p>
        </div>

        <button type="button" class="voucher-recall-button" data-voucher-view-code>
            Xem lại mã
        </button>
    </div>
</section>
