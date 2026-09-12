<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Actions\SeoOptimization\DeleteOptimizationTasks;
use App\Models\SeoOptimizationTask;
use App\Services\SeoOptimization\OptimizationAccess;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Hàng chờ SEO AI Optimize')]
class TasksIndex extends OptimizationComponent
{
    use WithPagination;

    public string $status = '';

    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function deleteTask(string $taskId, DeleteOptimizationTasks $deleteTasks): void
    {
        $this->perform(function () use ($taskId, $deleteTasks): void {
            $task = SeoOptimizationTask::query()->findOrFail($taskId);
            $deleteTasks->deleteOne($task, $this->actor());
        }, 'Đã xóa task khỏi hàng chờ SEO. Proposal, asset, backup và nhật ký liên quan vẫn được giữ.');

        $this->resetPage();
    }

    public function deleteFinishedTasks(DeleteOptimizationTasks $deleteTasks): void
    {
        $this->perform(
            fn () => $deleteTasks->deleteFinished($this->actor()),
            'Đã xóa các task kết thúc khỏi hàng chờ SEO. Proposal, asset, backup và nhật ký liên quan vẫn được giữ.',
        );

        $this->resetPage();
    }

    public function render()
    {
        $this->authorizeAdminPermission('admin.seo-optimization.index');
        $actor = $this->actor();
        $access = app(OptimizationAccess::class);
        $pages = $access->queryFor($actor)->select('seo_optimization_pages.id');
        $query = SeoOptimizationTask::query()->whereIn('page_id', $pages);
        $canManageTasks = $actor->can('admin.seo-optimization.propose');
        $editablePageTypes = $canManageTasks
            ? array_keys(array_filter(OptimizationAccess::PAGE_PERMISSIONS, fn (string $permission): bool => $actor->can($permission.'.edit')))
            : [];
        $finishedTasksCount = $canManageTasks
            ? SeoOptimizationTask::query()
                ->whereIn('page_id', $access->queryFor($actor, 'propose')->select('seo_optimization_pages.id'))
                ->finished()
                ->count()
            : 0;

        return view('livewire.admin.seo-optimization.tasks-index', [
            'canManageTasks' => $canManageTasks,
            'editablePageTypes' => $editablePageTypes,
            'finishedTasksCount' => $finishedTasksCount,
            'statuses' => (clone $query)->distinct()->orderBy('status')->pluck('status'),
            'tasks' => $query->with('page')
                ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
                ->when($this->search !== '', fn (Builder $query) => $query->whereHas('page', function (Builder $query): void {
                    $term = '%'.mb_substr(trim($this->search), 0, 200).'%';
                    $query->where(fn (Builder $query) => $query->where('title', 'like', $term)->orWhere('path', 'like', $term));
                }))
                ->latest()->paginate(25),
        ]);
    }
}
