<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Models\SeoContentCreationTask;
use App\Services\SeoOptimization\ContentCreationRegistry;
use App\Services\SeoOptimization\ContentCreationWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Nội dung Codex tạo mới')]
class ContentCreationIndex extends OptimizationComponent
{
    use WithPagination;

    public string $status = '';

    public string $contentType = '';

    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'contentType', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function approve(string $taskId, ContentCreationWorkflowService $workflow): void
    {
        $this->perform(function () use ($taskId, $workflow): void {
            $workflow->approveManual(SeoContentCreationTask::query()->findOrFail($taskId), $this->actor());
        }, 'Đã xác nhận và tạo taxonomy trong CMS.');
    }

    public function render()
    {
        $actor = $this->actor();
        $this->authorizeAdminPermission('admin.seo-optimization.index');
        $registry = app(ContentCreationRegistry::class);
        $contracts = collect($registry->types())
            ->mapWithKeys(fn (string $type): array => [$type => $registry->get($type)])
            ->filter(fn (array $contract): bool => $actor->can($contract['permission']));

        return view('livewire.admin.seo-optimization.content-creation-index', [
            'contracts' => $contracts,
            'statuses' => SeoContentCreationTask::query()->whereIn('content_type', $contracts->keys())->distinct()->orderBy('status')->pluck('status'),
            'tasks' => SeoContentCreationTask::query()
                ->with('requester:id,name')
                ->whereIn('content_type', $contracts->keys())
                ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
                ->when($this->contentType !== '', fn (Builder $query) => $query->where('content_type', $this->contentType))
                ->when($this->search !== '', function (Builder $query): void {
                    $term = '%'.mb_substr(trim($this->search), 0, 200).'%';
                    $query->where(fn (Builder $nested) => $nested->where('brief->request', 'like', $term)->orWhere('brief->primary_keyword', 'like', $term));
                })
                ->latest()
                ->paginate(25),
        ]);
    }
}
