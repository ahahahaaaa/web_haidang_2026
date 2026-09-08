<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Models\SeoOptimizationProposal;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Duyệt đề xuất SEO')]
class ProposalReview extends OptimizationComponent
{
    #[Locked]
    public string $proposalId;

    public string $rejectionReason = '';

    public function mount(SeoOptimizationProposal $proposal): void
    {
        app(OptimizationAccess::class)->authorize($this->actor(), 'index', $proposal->page);
        $this->proposalId = (string) $proposal->getKey();
    }

    public function approve(OptimizationWorkflowService $workflow): void
    {
        $proposal = $this->proposal('approve');
        $this->perform(fn () => $workflow->approve($proposal, $this->actor()), 'Đã duyệt đề xuất. Nội dung public chỉ thay đổi sau thao tác Áp dụng.');
    }

    public function reject(OptimizationWorkflowService $workflow): void
    {
        $proposal = $this->proposal('approve');
        $this->validate(['rejectionReason' => ['required', 'string', 'min:5', 'max:2000']]);
        $this->perform(fn () => $workflow->reject($proposal, $this->actor(), trim($this->rejectionReason)), 'Đã từ chối đề xuất và lưu lý do.');
    }

    public function apply(OptimizationWorkflowService $workflow): void
    {
        $proposal = $this->proposal('apply');
        $this->perform(fn () => $workflow->apply($proposal, $this->actor()), 'Đã xử lý áp dụng đề xuất. Kiểm tra trạng thái và kết quả tái kiểm tra bên dưới.');
    }

    public function rollback(OptimizationWorkflowService $workflow): void
    {
        $proposal = $this->proposal('rollback');
        $this->perform(function () use ($workflow, $proposal): void {
            $rollback = $workflow->rollbackProposal($proposal, $this->actor());
            $this->redirectRoute('admin.seo-optimization.proposals.show', ['proposal' => $rollback->getKey()], navigate: true);
        }, 'Đã tạo đề xuất hoàn tác. Cần duyệt và áp dụng như một thay đổi mới.');
    }

    public function verify(OptimizationWorkflowService $workflow): void
    {
        $proposal = $this->proposal('apply');
        $this->perform(fn () => $workflow->verify($proposal, $this->actor()), 'Đã kiểm tra lại nội dung; không ghi lại thay đổi CMS.');
    }

    public function render()
    {
        return view('livewire.admin.seo-optimization.proposal-review', ['proposal' => $this->proposal()]);
    }

    protected function proposal(string $ability = 'index'): SeoOptimizationProposal
    {
        $proposal = SeoOptimizationProposal::query()->with('page')->findOrFail($this->proposalId);
        app(OptimizationAccess::class)->authorize($this->actor(), $ability, $proposal->page);

        return $proposal;
    }
}
