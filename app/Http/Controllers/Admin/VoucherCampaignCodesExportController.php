<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\VoucherCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherCampaignCodesExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $filename = Str::slug('voucher-codes-da-cap-phat').'.xls';

        return response()->streamDownload(function () use ($request): void {
            echo "\xEF\xBB\xBF";
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';
            echo '<thead><tr>';

            foreach ([
                'Campaign',
                'Hạn sử dụng voucher',
                'Mã voucher',
                'Trạng thái',
                'Tên khách',
                'Số điện thoại',
                'Email',
                'Ngữ cảnh lead',
                'Trạng thái lead',
                'Ngày cấp phát',
                'Ngày sử dụng',
                'Nhân viên ghi nhận',
                'Ghi chú sử dụng',
            ] as $heading) {
                echo '<th>'.e($heading).'</th>';
            }

            echo '</tr></thead><tbody>';

            $this->query($request)
                ->orderByDesc('claimed_at')
                ->orderByDesc('id')
                ->get()
                ->each(function (VoucherCode $code): void {
                    $campaign = $code->campaign;
                    $inquiry = $code->inquiry;
                    $usedBy = $code->usedBy;
                    $cells = [
                        $campaign?->title,
                        $campaign?->code_valid_until?->format('d/m/Y'),
                        $code->code,
                        $code->statusLabel(),
                        $inquiry?->customer_name,
                        $inquiry?->customer_phone,
                        $inquiry?->customer_email,
                        $inquiry?->context_title,
                        $inquiry?->status,
                        $code->claimed_at?->format('d/m/Y H:i'),
                        $code->used_at?->format('d/m/Y H:i'),
                        $usedBy ? ($usedBy->name ?: $usedBy->email) : null,
                        $code->used_note,
                    ];

                    echo '<tr>';

                    foreach ($cells as $cell) {
                        echo '<td>'.e((string) ($cell ?? '')).'</td>';
                    }

                    echo '</tr>';
                });

            echo '</tbody></table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected function query(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $leadStatus = trim((string) $request->query('lead_status', ''));
        $claimedFrom = trim((string) $request->query('claimed_from', ''));
        $claimedTo = trim((string) $request->query('claimed_to', ''));

        return VoucherCode::query()
            ->with([
                'campaign:id,title,slug,code_valid_until',
                'inquiry:id,customer_name,customer_phone,customer_email,context_title,status,created_at',
                'usedBy:id,name,email',
            ])
            ->issued()
            ->when(
                in_array($status, [VoucherCode::STATUS_CLAIMED, VoucherCode::STATUS_USED], true),
                fn ($query) => $query->where('status', $status),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('code', 'like', '%'.$search.'%')
                        ->orWhereHas('campaign', function ($campaignQuery) use ($search): void {
                            $campaignQuery
                                ->where('title', 'like', '%'.$search.'%')
                                ->orWhere('slug', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('inquiry', function ($inquiryQuery) use ($search): void {
                            $inquiryQuery
                                ->where('customer_name', 'like', '%'.$search.'%')
                                ->orWhere('customer_phone', 'like', '%'.$search.'%')
                                ->orWhere('customer_email', 'like', '%'.$search.'%')
                                ->orWhere('context_title', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($leadStatus === 'missing', fn ($query) => $query->whereNull('travel_inquiry_id'))
            ->when(
                in_array($leadStatus, ['new', 'contacted', 'closed'], true),
                fn ($query) => $query->whereHas('inquiry', fn ($inquiryQuery) => $inquiryQuery->where('status', $leadStatus)),
            )
            ->when($claimedFrom !== '', fn ($query) => $query->whereDate('claimed_at', '>=', $claimedFrom))
            ->when($claimedTo !== '', fn ($query) => $query->whereDate('claimed_at', '<=', $claimedTo));
    }
}
