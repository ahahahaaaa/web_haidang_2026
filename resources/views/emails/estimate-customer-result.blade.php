<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <title>Phiếu tiếp nhận dự toán</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#0f172a;">
        @php
            $estimatePayload = data_get($estimateRequest->result_snapshot, 'estimate', []);
            $storedInputPayload = $estimateRequest->input_payload ?? [];
            $flatInputPayload = data_get($storedInputPayload, 'flat', is_array($storedInputPayload) ? $storedInputPayload : []);
            $roomProgram = data_get($storedInputPayload, 'room_program', []);
            $pricingPackages = is_array(data_get($estimatePayload, 'pricing_summary.packages')) ? data_get($estimatePayload, 'pricing_summary.packages') : (is_array(data_get($estimatePayload, 'pricing.packages')) ? data_get($estimatePayload, 'pricing.packages') : []);
            $logoUrl = $siteSettings->getFirstMediaUrl('logo');
            $companyDisplayName = $siteSettings->company_name ?: $siteSettings->site_name ?: $companyName;
            $tagline = $siteSettings->site_tagline ?: 'Xây dựng giá trị vững bền';
            $phone = $siteSettings->phone ?: 'Đang cập nhật';
            $hotline = $siteSettings->hotline ?: $siteSettings->phone ?: 'Đang cập nhật';
            $zalo = $siteSettings->zalo_url ?: 'Đang cập nhật';
            $socialLinks = collect([
                ['label' => 'Facebook', 'url' => $siteSettings->facebook_url],
                ['label' => 'YouTube', 'url' => $siteSettings->youtube_url],
                ['label' => 'LinkedIn', 'url' => $siteSettings->linkedin_url],
                ['label' => 'TikTok', 'url' => $siteSettings->tiktok_url],
                ['label' => 'Zalo', 'url' => $siteSettings->zalo_url],
            ])->filter(fn (array $item) => filled($item['url']))->values();
        @endphp
        <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:24px;padding:32px;border:1px solid #e2e8f0;">
            <div style="margin-bottom:24px;border-radius:20px;background:linear-gradient(135deg,#0d1a2d 0%,#133a67 45%,#0d6cb6 100%);padding:24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <div style="display:flex;align-items:center;gap:16px;">
                                @if (filled($logoUrl))
                                    <img src="{{ $logoUrl }}" alt="{{ $companyDisplayName }}" style="display:block;max-height:56px;width:auto;border-radius:12px;background:#ffffff;padding:8px;">
                                @endif
                                <div>
                                    <p style="margin:0;font-size:24px;line-height:1.2;font-weight:700;color:#ffffff;">{{ $companyDisplayName }}</p>
                                    <p style="margin:8px 0 0;font-size:12px;line-height:1.6;letter-spacing:0.24em;text-transform:uppercase;color:rgba(255,255,255,0.76);">{{ $tagline }}</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#dc2626;">Estimate Result</p>
            <h1 style="margin:0 0 12px;font-size:28px;line-height:1.2;">Phiếu tiếp nhận yêu cầu dự toán</h1>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                {{ $companyName }} đã ghi nhận yêu cầu dự toán của bạn vào lúc {{ $submittedAt->format('d/m/Y H:i') }}.
                Dưới đây là phần thông tin chính đang đi cùng yêu cầu của bạn.
            </p>

            <div style="margin-top:24px;padding:20px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                <p style="margin:0 0 8px;font-weight:700;color:#334155;">Thông tin tiếp nhận</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    @foreach ([
                        'Khách hàng' => $estimateRequest->customer_name,
                        'Cấp dự toán' => $estimateRequest->tier_name,
                        'Số điện thoại' => $estimateRequest->customer_phone,
                        'Email' => $estimateRequest->customer_email,
                        'Khu vực dự án' => $estimateRequest->project_location ?: 'Không cung cấp',
                    ] as $label => $value)
                        <tr>
                            <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;width:180px;font-weight:700;color:#334155;">{{ $label }}</td>
                            <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;">{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
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

            <div style="margin-top:24px;padding:20px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                <p style="margin:0 0 8px;font-weight:700;color:#334155;">Ghi chú nhu cầu công trình</p>
                <p style="margin:0;white-space:pre-line;line-height:1.7;color:#0f172a;">{{ $estimateRequest->project_overview }}</p>
            </div>

            <div style="margin-top:24px;border-top:1px solid #e2e8f0;padding-top:24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:top;padding-right:16px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if (filled($logoUrl))
                                    <img src="{{ $logoUrl }}" alt="{{ $companyDisplayName }}" style="display:block;max-height:44px;width:auto;border-radius:10px;background:#f8fafc;padding:6px;border:1px solid #e2e8f0;">
                                @endif
                                <div>
                                    <p style="margin:0;font-size:18px;line-height:1.4;font-weight:700;color:#0f172a;">{{ $companyDisplayName }}</p>
                                    <p style="margin:4px 0 0;font-size:12px;line-height:1.6;color:#64748b;">{{ $tagline }}</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;border-collapse:collapse;">
                    <tr>
                        <td style="padding:6px 0;width:120px;font-weight:700;color:#334155;vertical-align:top;">Địa chỉ</td>
                        <td style="padding:6px 0;color:#475569;">{{ $siteSettings->address ?: 'Đang cập nhật' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;width:120px;font-weight:700;color:#334155;vertical-align:top;">Phone</td>
                        <td style="padding:6px 0;color:#475569;">{{ $phone }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;width:120px;font-weight:700;color:#334155;vertical-align:top;">Hotline</td>
                        <td style="padding:6px 0;color:#475569;">{{ $hotline }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;width:120px;font-weight:700;color:#334155;vertical-align:top;">Zalo</td>
                        <td style="padding:6px 0;color:#475569;">
                            @if (filter_var($zalo, FILTER_VALIDATE_URL))
                                <a href="{{ $zalo }}" style="color:#0d6cb6;text-decoration:none;">{{ $zalo }}</a>
                            @else
                                {{ $zalo }}
                            @endif
                        </td>
                    </tr>
                </table>

                @if ($socialLinks->isNotEmpty())
                    <div style="margin-top:18px;">
                        <p style="margin:0 0 10px;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Kết nối cùng {{ $companyDisplayName }}</p>
                        @foreach ($socialLinks as $social)
                            <a href="{{ $social['url'] }}" style="display:inline-block;margin:0 12px 8px 0;color:#0d6cb6;text-decoration:none;font-weight:700;">
                                {{ $social['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
