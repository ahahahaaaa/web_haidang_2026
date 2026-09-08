<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <title>Yêu cầu tư vấn dịch vụ</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#0f172a;">
        <div style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:24px;padding:32px;border:1px solid #e2e8f0;">
            <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#dc2626;">Service Consultation</p>
            <h1 style="margin:0 0 12px;font-size:28px;line-height:1.2;">Yêu cầu tư vấn mới cho dịch vụ "{{ $service->title }}"</h1>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                {{ $companyName }} vừa nhận được một yêu cầu tư vấn từ form popup trên trang chi tiết dịch vụ.
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                @foreach ([
                    'Dịch vụ' => $service->title,
                    'Họ tên' => $payload['name'],
                    'Số điện thoại' => $payload['phone'],
                    'Email' => $payload['email'],
                    'Thời gian gửi' => $submittedAt->format('d/m/Y H:i'),
                ] as $label => $value)
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;width:180px;font-weight:700;color:#334155;">{{ $label }}</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>

            <div style="margin-top:24px;padding:20px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                <p style="margin:0 0 8px;font-weight:700;color:#334155;">Nội dung yêu cầu</p>
                <p style="margin:0;white-space:pre-line;line-height:1.7;color:#0f172a;">{{ $payload['message'] }}</p>
            </div>
        </div>
    </body>
</html>
