<?php

namespace App\Livewire\Admin\SeoOptimization;

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

    public function render()
    {
        $this->authorizeAdminPermission('admin.seo-optimization.index');
        $pages = app(OptimizationAccess::class)->queryFor($this->actor())->select('seo_optimization_pages.id');
        $query = SeoOptimizationTask::query()->whereIn('page_id', $pages);

        return view('livewire.admin.seo-optimization.tasks-index', [
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
