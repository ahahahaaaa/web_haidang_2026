<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\VoucherCode;

#[Layout('layouts.app')]
#[Title('Mã voucher đã cấp phát')]
class VoucherCampaignCodesManager extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $leadStatusFilter = '';

    public string $claimedFrom = '';

    public string $claimedTo = '';

    public array $usageNotes = [];

    public function mount(): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.index');
    }

    public function render()
    {
        $codes = $this->issuedCodesQuery()
            ->latest('claimed_at')
            ->latest('id')
            ->paginate(50);

        foreach ($codes as $code) {
            $this->usageNotes[$code->id] ??= (string) ($code->used_note ?? '');
        }

        return view('livewire.admin.cms.voucher-campaign-codes-manager', [
            'codes' => $codes,
            'exportUrl' => route('admin.voucher-codes.export', $this->exportQuery()),
            'issuedCodesCount' => VoucherCode::query()->issued()->count(),
            'claimedCodesCount' => VoucherCode::query()->where('status', VoucherCode::STATUS_CLAIMED)->count(),
            'usedCodesCount' => VoucherCode::query()->where('status', VoucherCode::STATUS_USED)->count(),
            'campaignsWithIssuedCodesCount' => VoucherCode::query()->issued()->distinct()->count('voucher_campaign_id'),
            'hasFilters' => $this->hasFilters(),
            'statusLabels' => VoucherCode::statuses(),
        ]);
    }

    public function markVoucherCodeUsed(int $codeId): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $code = $this->issuedCodeForAction($codeId);
        $campaign = $code->campaign;

        if (! $campaign || ! $campaign->isCodeUsageOpen()) {
            session()->flash('warning', 'Không thể đánh dấu đã sử dụng vì campaign đã quá hạn sử dụng voucher.');

            return;
        }

        $code->forceFill([
            'status' => VoucherCode::STATUS_USED,
            'used_at' => now(),
            'used_by' => auth()->id(),
            'used_note' => $this->normalizedUsageNote($codeId),
        ])->save();

        session()->flash('status', 'Đã đánh dấu voucher là đã sử dụng.');
    }

    public function markVoucherCodeClaimed(int $codeId): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $code = $this->issuedCodeForAction($codeId);
        $code->forceFill([
            'status' => VoucherCode::STATUS_CLAIMED,
            'used_at' => null,
            'used_by' => null,
            'used_note' => $this->normalizedUsageNote($codeId),
        ])->save();

        session()->flash('status', 'Đã chuyển voucher về trạng thái đã phát.');
    }

    public function saveUsageNote(int $codeId): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $code = $this->issuedCodeForAction($codeId);
        $code->forceFill([
            'used_note' => $this->normalizedUsageNote($codeId),
        ])->save();

        session()->flash('status', 'Đã cập nhật ghi chú voucher.');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->leadStatusFilter = '';
        $this->claimedFrom = '';
        $this->claimedTo = '';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLeadStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClaimedFrom(): void
    {
        $this->resetPage();
    }

    public function updatingClaimedTo(): void
    {
        $this->resetPage();
    }

    protected function issuedCodesQuery()
    {
        $search = $this->search;

        return VoucherCode::query()
            ->with([
                'campaign:id,landing_page_id,title,slug,code_valid_until',
                'campaign.landingPage:id,title,slug',
                'inquiry:id,customer_name,customer_phone,customer_email,context_title,status,created_at',
                'usedBy:id,name,email',
            ])
            ->issued()
            ->when(
                in_array($this->statusFilter, [VoucherCode::STATUS_CLAIMED, VoucherCode::STATUS_USED], true),
                fn ($query) => $query->where('status', $this->statusFilter),
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
            ->when($this->leadStatusFilter === 'missing', fn ($query) => $query->whereNull('travel_inquiry_id'))
            ->when(
                in_array($this->leadStatusFilter, ['new', 'contacted', 'closed'], true),
                fn ($query) => $query->whereHas('inquiry', fn ($inquiryQuery) => $inquiryQuery->where('status', $this->leadStatusFilter)),
            )
            ->when($this->claimedFrom !== '', fn ($query) => $query->whereDate('claimed_at', '>=', $this->claimedFrom))
            ->when($this->claimedTo !== '', fn ($query) => $query->whereDate('claimed_at', '<=', $this->claimedTo));
    }

    protected function issuedCodeForAction(int $codeId): VoucherCode
    {
        return VoucherCode::query()
            ->with('campaign')
            ->issued()
            ->findOrFail($codeId);
    }

    protected function normalizedUsageNote(int $codeId): ?string
    {
        $note = trim((string) ($this->usageNotes[$codeId] ?? ''));

        return $note !== '' ? mb_substr($note, 0, 1000) : null;
    }

    protected function exportQuery(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'lead_status' => $this->leadStatusFilter,
            'claimed_from' => $this->claimedFrom,
            'claimed_to' => $this->claimedTo,
        ], fn ($value): bool => $value !== '');
    }

    protected function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->statusFilter !== ''
            || $this->leadStatusFilter !== ''
            || $this->claimedFrom !== ''
            || $this->claimedTo !== '';
    }
}
