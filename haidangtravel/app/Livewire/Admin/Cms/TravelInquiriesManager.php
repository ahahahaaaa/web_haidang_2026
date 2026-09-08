<?php

namespace App\Livewire\Admin\Cms;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\TravelInquiry;

#[Layout('layouts.app')]
#[Title('Yêu cầu tư vấn du lịch')]
class TravelInquiriesManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $selectedId = null;

    public string $source = '';

    public string $status = '';

    public function mount(): void
    {
        $this->selectedId = TravelInquiry::query()->latest('id')->value('id');
    }

    public function selectInquiry(int $id): void
    {
        $this->selectedId = $id;
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
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest('id')
            ->paginate(12);

        return view('livewire.admin.cms.travel-inquiries-manager', [
            'inquiries' => $inquiries,
            'selectedInquiry' => $this->selectedId ? TravelInquiry::query()->with(['tour', 'service'])->find($this->selectedId) : null,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }
}
