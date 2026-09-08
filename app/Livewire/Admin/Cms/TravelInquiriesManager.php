<?php

namespace App\Livewire\Admin\Cms;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\TravelInquiry;
use Src\Domains\Cms\Models\VoucherCampaign;

#[Layout('layouts.app')]
#[Title('Yêu cầu tư vấn du lịch')]
class TravelInquiriesManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $selectedId = null;

    public string $source = '';

    public string $voucherCampaign = '';

    public string $status = '';

    public string $createdFrom = '';

    public string $createdTo = '';

    public function selectInquiry(int $id): void
    {
        $this->selectedId = $this->selectedId === $id ? null : $id;
    }

    public function markStatus(int $id, string $status): void
    {
        TravelInquiry::query()->findOrFail($id)->update(['status' => $status]);

        session()->flash('status', 'Đã cập nhật trạng thái yêu cầu.');
    }

    public function render()
    {
        $inquiries = TravelInquiry::query()
            ->with(['tour', 'service'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->where('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_phone', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_email', 'like', '%'.$this->search.'%')
                        ->orWhere('context_title', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->source !== '', fn ($query) => $query->where('source', $this->source))
            ->when($this->voucherCampaign === '__any', function ($query): void {
                $query->where(function ($nested): void {
                    $nested
                        ->whereNotNull('meta->voucher_campaign_slug')
                        ->orWhereNotNull('meta->voucher->campaign_slug');
                });
            })
            ->when($this->voucherCampaign !== '' && $this->voucherCampaign !== '__any', function ($query): void {
                $query->where(function ($nested): void {
                    $nested
                        ->where('meta->voucher_campaign_slug', $this->voucherCampaign)
                        ->orWhere('meta->voucher->campaign_slug', $this->voucherCampaign);
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->createdFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->createdFrom))
            ->when($this->createdTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->createdTo))
            ->latest('id')
            ->paginate(12);

        return view('livewire.admin.cms.travel-inquiries-manager', [
            'inquiries' => $inquiries,
            'selectedInquiry' => $this->selectedId ? TravelInquiry::query()->with(['tour', 'service'])->find($this->selectedId) : null,
            'voucherCampaigns' => VoucherCampaign::query()
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }

    public function updatingVoucherCampaign(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }

    public function updatingCreatedFrom(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }

    public function updatingCreatedTo(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }
}
