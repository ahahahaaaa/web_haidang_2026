<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Models\SeoOptimizationProposal;
use App\Models\User;
use App\Services\SeoOptimization\ContentWriteContractService;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use App\Services\SeoOptimization\PageAuditService;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\ProposalScoreRevisionService;
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

    public array $editPatch = [];

    public function mount(SeoOptimizationProposal $proposal): void
    {
        $page = $proposal->page()->firstOrFail();
        app(OptimizationAccess::class)->authorize($this->actor(), 'index', $page);
        $this->proposalId = (string) $proposal->getKey();
        $this->editPatch = $proposal->patch ?? [];
    }

    public function approve(OptimizationWorkflowService $workflow): void
    {
        $proposal = $this->proposal('approve');
        $this->perform(fn () => $workflow->approve($proposal, $this->actor()), 'Đã duyệt đề xuất và ưu tiên nội dung được chọn trên phiên bản CMS mới nhất. Nội dung public chỉ thay đổi sau thao tác Áp dụng.');
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

    public function rescore(ProposalScoreRevisionService $revisions): void
    {
        $this->reviseProposal($revisions, false);
    }

    public function rescoreAndApply(ProposalScoreRevisionService $revisions): void
    {
        $this->reviseProposal($revisions, true);
    }

    public function render(PageRegistryService $registry, PageAuditService $audits)
    {
        $proposal = $this->proposal();
        $configuredById = data_get($proposal->qa, 'authorization.configured_by');
        $configuredByName = filled($configuredById)
            ? User::query()->whereKey($configuredById)->value('name')
            : null;
        $contentPermission = OptimizationAccess::PAGE_PERMISSIONS[$proposal->page->page_type] ?? null;
        $contentEditUrl = $contentPermission && $this->actor()->can($contentPermission.'.edit')
            ? $registry->adminEditUrl($proposal->page)
            : null;

        $comparisonRows = app(ContentWriteContractService::class)->comparisonRows(
            $proposal->page,
            $proposal->patch ?? [],
            $proposal->before ?? [],
            is_array($proposal->task?->snapshot) ? $proposal->task->snapshot : [],
        );
        $taskSnapshot = is_array($proposal->task?->snapshot) ? $proposal->task->snapshot : [];
        $taskBrief = is_array($proposal->task?->brief) ? $proposal->task->brief : [];
        $qaDimensionComparisons = $taskSnapshot !== []
            ? $audits->comparisonDetails($taskSnapshot, $taskBrief, $proposal->patch ?? [])
            : [];
        $beforeScore = data_get($proposal->qa, 'baseline_seo_gate.score');
        $afterScore = data_get($proposal->qa, 'seo_gate.score');

        return view('livewire.admin.seo-optimization.proposal-review', [
            'proposal' => $proposal,
            'configuredByName' => $configuredByName ?: 'không còn tồn tại',
            'contentPublicUrl' => $registry->url($proposal->page),
            'contentEditUrl' => $contentEditUrl,
            'comparisonRows' => $this->withEditorModels($comparisonRows, $proposal->patch ?? []),
            'qaDimensionComparisons' => $qaDimensionComparisons,
            'isRestoreProposal' => isset($proposal->qa['rollback_of']),
            'scoreImproved' => is_numeric($beforeScore) && is_numeric($afterScore) && (float) $afterScore > (float) $beforeScore,
            'canEditProposal' => ! $proposal->applied_at
                && in_array($proposal->status, ['in_review', 'need_data', 'approved', 'stale'], true)
                && ! isset($proposal->qa['rollback_of']),
        ]);
    }

    private function reviseProposal(ProposalScoreRevisionService $revisions, bool $autoApply): void
    {
        $proposal = $this->proposal('approve');
        $this->validate(['editPatch' => ['present', 'array', 'max:30']]);
        $result = null;
        $this->perform(function () use ($revisions, $proposal, $autoApply, &$result): void {
            $result = $revisions->revise($proposal, $this->editPatch, $this->actor(), $autoApply);
            $this->editPatch = $result['proposal']->patch ?? [];
        }, 'Đã chấm lại nội dung đề xuất.');

        if (is_array($result)) {
            session()->flash('status', $result['message']);
        }
    }

    /** @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function withEditorModels(array $rows, array $proposalPatch): array
    {
        $models = [];
        foreach ($proposalPatch[ContentWriteContractService::BLOCK_CHANGES] ?? [] as $changeIndex => $change) {
            foreach ($change['changes'] ?? [] as $field => $_value) {
                $key = ContentWriteContractService::BLOCK_CHANGES.'.'.($change['uuid'] ?? '').'.'.$field;
                $models[$key] = 'editPatch.'.ContentWriteContractService::BLOCK_CHANGES.'.'.$changeIndex.'.changes.'.$field;
            }
        }

        return collect($rows)->map(function (array $row) use ($models): array {
            $model = $models[$row['key']] ?? (array_key_exists($row['key'], $this->editPatch) ? 'editPatch.'.$row['key'] : null);

            return [
                ...$row,
                'edit_model' => $model,
                'edit_value' => $model ? data_get($this, $model) : $row['after'],
            ];
        })->all();
    }

    protected function proposal(string $ability = 'index'): SeoOptimizationProposal
    {
        $proposal = SeoOptimizationProposal::query()->with([
            'page',
            'task',
            'backup',
            'audit:id,page_id,score,grade,status,report,source_version,rule_version,created_at',
        ])->findOrFail($this->proposalId);
        app(OptimizationAccess::class)->authorize($this->actor(), $ability, $proposal->page);

        return $proposal;
    }
}
