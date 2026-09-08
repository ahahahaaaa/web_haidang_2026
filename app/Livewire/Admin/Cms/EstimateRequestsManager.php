<?php

namespace App\Livewire\Admin\Cms;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\EstimateRequest;

#[Layout('layouts.app')]
#[Title('Phiếu yêu cầu dự toán')]
class EstimateRequestsManager extends Component
{
    use WithPagination;

    public string $adminMailStatus = '';

    public string $customerMailStatus = '';

    public string $search = '';

    public ?int $selectedId = null;

    public string $tier = '';

    public function mount(): void
    {
        $this->selectedId = EstimateRequest::query()->latest('id')->value('id');
    }

    public function render()
    {
        $requests = EstimateRequest::query()
            ->with('accessKey')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_email', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_phone', 'like', '%'.$this->search.'%')
                        ->orWhere('project_location', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->tier !== '', fn ($query) => $query->where('tier_code', $this->tier))
            ->when($this->adminMailStatus !== '', fn ($query) => $query->where('mail_status', $this->adminMailStatus))
            ->when($this->customerMailStatus !== '', fn ($query) => $query->where('customer_mail_status', $this->customerMailStatus))
            ->latest('id')
            ->paginate(10);

        $visibleIds = $requests->getCollection()->pluck('id');

        if (! $this->selectedId || ! $visibleIds->contains($this->selectedId)) {
            $this->selectedId = $requests->first()?->getKey();
        }

        return view('livewire.admin.cms.estimate-requests-manager', [
            'requests' => $requests,
            'selectedRequest' => $this->selectedId
                ? EstimateRequest::query()->with('accessKey')->find($this->selectedId)
                : null,
            'tierOptions' => EstimateRequest::query()
                ->select('tier_code', 'tier_name')
                ->distinct()
                ->orderBy('tier_name')
                ->get(),
        ]);
    }

    public function selectRequest(int $id): void
    {
        $this->selectedId = $id;
    }

    public function updatingAdminMailStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCustomerMailStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTier(): void
    {
        $this->resetPage();
    }
}
