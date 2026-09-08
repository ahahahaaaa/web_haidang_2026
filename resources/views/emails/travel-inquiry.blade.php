<div style="font-family:Arial,sans-serif;color:#111827;line-height:1.6;">
    @php
        $meta = $inquiry->meta ?? [];
        $inquiryTypeLabels = [
            'travel' => 'Du lịch',
            'customer_care' => 'Chăm sóc khách hàng',
            'other' => 'Liên hệ thông tin khác',
        ];
        $resolvedInquiryType = $inquiryTypeLabels[$meta['inquiry_type'] ?? ''] ?? null;
        $legacyAdultGuestCount = $meta['company_name'] ?? null;
        $adultGuestCount = $meta['adult_guest_count'] ?? (is_numeric($legacyAdultGuestCount) ? $legacyAdultGuestCount : null);
    @endphp

    <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#0f766e;">Travel Inquiry</p>
    <h1 style="margin:0 0 16px;font-size:22px;">{{ $meta['subject'] ?? ($inquiry->context_title ?: $inquiry->source->label()) }}</h1>

    <p style="margin:0 0 8px;"><strong>Nguồn:</strong> {{ $inquiry->source->label() }}</p>
    @if ($resolvedInquiryType)
        <p style="margin:0 0 8px;"><strong>Loại thông tin:</strong> {{ $resolvedInquiryType }}</p>
    @endif
    <p style="margin:0 0 8px;"><strong>Khách hàng:</strong> {{ $inquiry->customer_name }}</p>
    <p style="margin:0 0 8px;"><strong>Điện thoại:</strong> {{ $inquiry->customer_phone }}</p>

    @if ($inquiry->customer_email)
        <p style="margin:0 0 8px;"><strong>Email:</strong> {{ $inquiry->customer_email }}</p>
    @endif

    @if (filled($adultGuestCount))
        <p style="margin:0 0 8px;"><strong>Số khách người lớn:</strong> {{ $adultGuestCount }}</p>
    @endif

    @if (! empty($meta['expected_destination']))
        <p style="margin:0 0 8px;"><strong>Điểm đến dự kiến:</strong> {{ $meta['expected_destination'] }}</p>
    @endif

    @if (! empty($meta['expected_time']))
        <p style="margin:0 0 8px;"><strong>Thời gian đi dự kiến:</strong> {{ $meta['expected_time'] }}</p>
    @endif

    @if (! empty($meta['address']))
        <p style="margin:0 0 8px;"><strong>Địa chỉ:</strong> {{ $meta['address'] }}</p>
    @endif

    @if (! empty($meta['subject']))
        <p style="margin:0 0 8px;"><strong>Tiêu đề:</strong> {{ $meta['subject'] }}</p>
    @endif

    @if ($inquiry->travel_date)
        <p style="margin:0 0 8px;"><strong>Ngày đi dự kiến:</strong> {{ \Illuminate\Support\Carbon::parse($inquiry->travel_date)->format('d/m/Y') }}</p>
    @endif

    @if (filled($inquiry->party_size))
        <p style="margin:0 0 8px;"><strong>Số trẻ em:</strong> {{ $inquiry->party_size }}</p>
    @endif

    @if ($inquiry->message)
        <p style="margin:16px 0 8px;"><strong>Nội dung:</strong></p>
        <div style="padding:12px 16px;background:#f3f4f6;border-radius:12px;white-space:pre-line;">{{ $inquiry->message }}</div>
    @endif

    @if ($inquiry->page_url)
        <p style="margin:16px 0 0;"><strong>Trang gửi:</strong> {{ $inquiry->page_url }}</p>
    @endif
</div>
