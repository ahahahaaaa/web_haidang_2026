<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <title>Yêu cầu dự toán mới</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#0f172a;">
        @php
            $estimatePayload = data_get($estimateRequest->result_snapshot, 'estimate', []);
            $storedInputPayload = $estimateRequest->input_payload ?? [];
            $flatInputPayload = data_get($storedInputPayload, 'flat', is_array($storedInputPayload) ? $storedInputPayload : []);
            $roomProgram = data_get($storedInputPayload, 'room_program', []);
            $pricingPackages = is_array(data_get($estimatePayload, 'pricing_summary.packages')) ? data_get($estimatePayload, 'pricing_summary.packages') : (is_array(data_get($estimatePayload, 'pricing.packages')) ? data_get($estimatePayload, 'pricing.packages') : []);
        @endphp
        <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:24px;padding:32px;border:1px solid #e2e8f0;">
            <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#dc2626;">Estimate Request</p>
            <h1 style="margin:0 0 12px;font-size:28px;line-height:1.2;">Yêu cầu dự toán mới từ landing page</h1>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                {{ $companyName }} vừa nhận được một yêu cầu dự toán mới từ landing page chuyên biệt.
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                @foreach ([
                    'Cấp dự toán' => $estimateRequest->tier_name,
                    'Khách hàng' => $estimateRequest->customer_name,
                    'Số điện thoại' => $estimateRequest->customer_phone,
                    'Email' => $estimateRequest->customer_email,
                    'Công ty' => $estimateRequest->company_name ?: 'Không cung cấp',
                    'Khu vực dự án' => $estimateRequest->project_location ?: 'Không cung cấp',
                    'Mã key' => $estimateRequest->estimate_key_code ?: 'Không áp dụng',
                    'Trang gửi' => $estimateRequest->page_url ?: 'Không cung cấp',
                    'Thời gian gửi' => $submittedAt->format('d/m/Y H:i'),
                ] as $label => $value)
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;width:190px;font-weight:700;color:#334155;">{{ $label }}</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>

            <div style="margin-top:24px;padding:20px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                <p style="margin:0 0 8px;font-weight:700;color:#334155;">Mô tả nhu cầu</p>
                <p style="margin:0;white-space:pre-line;line-height:1.7;color:#0f172a;">{{ $estimateRequest->project_overview }}</p>
            </div>

            <div style="margin-top:24px;padding:20px;border-radius:18px;background:#fff7ed;border:1px solid #fed7aa;">
                <p style="margin:0 0 8px;font-weight:700;color:#9a3412;">Thông số đầu vào</p>
                <ul style="margin:0;padding-left:18px;color:#7c2d12;line-height:1.7;">
                    @forelse ($flatInputPayload as $item)
                        <li><strong>{{ $item['label'] ?? $item['slug'] }}:</strong> {{ $item['display_value'] ?? $item['value'] }}</li>
                    @empty
                        <li>Không có dữ liệu đầu vào.</li>
                    @endforelse
                </ul>

                @if ($roomProgram !== [])
                    <p style="margin:12px 0 8px;font-weight:700;color:#9a3412;">Công năng & nội thất</p>
                    <ul style="margin:0;padding-left:18px;color:#7c2d12;line-height:1.7;">
                        @foreach ($roomProgram as $room)
                            <li>{{ $room['room_type_label'] ?? $room['room_type'] }} · {{ $room['count'] ?? 0 }} phòng{{ filled($room['template_label'] ?? null) ? ' · '.$room['template_label'] : '' }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if (is_array($estimatePayload) && $estimatePayload !== [])
                <div style="margin-top:24px;padding:20px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                    <p style="margin:0 0 12px;font-weight:700;color:#334155;">Chỉ số quy đổi</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        @foreach ([
                            'Diện tích footprint' => number_format((float) data_get($estimatePayload, 'derived.base_area', 0), 2, ',', '.').' m2',
                            'Diện tích quy đổi' => number_format((float) data_get($estimatePayload, 'totals.converted_area', 0), 2, ',', '.').' m2',
                        ] as $label => $value)
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;width:180px;font-weight:700;color:#334155;">{{ $label }}</td>
                                <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                @if ($pricingPackages !== [])
                    <div style="margin-top:24px;padding:20px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                        <p style="margin:0 0 12px;font-weight:700;color:#334155;">Kết quả theo gói</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            @foreach ($pricingPackages as $package)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-weight:700;color:#334155;">{{ $package['label'] ?? $package['key'] ?? 'Gói' }}</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right;">
                                        <strong style="color:#0f172a;">{{ number_format((float) ($package['amount'] ?? 0), 0, ',', '.') }} đ</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </body>
</html>
