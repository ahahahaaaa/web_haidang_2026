<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\PageRegistryService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('SEO AI Optimize')]
class PagesIndex extends OptimizationComponent
{
    use WithPagination;

    public string $search = '';

    public string $pageType = '';

    public string $classification = '';

    public string $briefFilter = '';

    public function syncInventory(PageRegistryService $registry, OptimizationAccess $access): void
    {
        $access->authorize($this->actor(), 'audit');
        $this->authorizeAdminPermission('admin.seo-optimization.settings');
        $this->perform(fn () => $registry->sync(), 'Đã đối soát danh sách URL với dữ liệu CMS hiện tại.');
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'pageType', 'classification', 'briefFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $base = app(OptimizationAccess::class)->queryFor($this->actor());
        $this->authorizeAdminPermission('admin.seo-optimization.index');
        $query = (clone $base)
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                $term = '%'.mb_substr(trim($this->search), 0, 200).'%';
                $query->where('title', 'like', $term)->orWhere('path', 'like', $term);
            }))
            ->when($this->pageType !== '', fn (Builder $query) => $query->where('page_type', $this->pageType))
            ->when($this->classification !== '', fn (Builder $query) => $query->where('classification', $this->classification))
            ->when($this->briefFilter === 'missing', fn (Builder $query) => $query->where(function (Builder $query): void {
                $query->whereNull('keyword_brief->primary_keyword')->orWhere('keyword_brief->primary_keyword', '');
            }))
            ->when($this->briefFilter === 'configured', fn (Builder $query) => $query->whereNotNull('keyword_brief->primary_keyword')->where('keyword_brief->primary_keyword', '!=', ''));

        return view('livewire.admin.seo-optimization.pages-index', [
            'pages' => $query->withCount(['audits', 'proposals', 'tasks'])->latest('last_seen_at')->orderBy('id')->paginate(25),
            'pageTypes' => (clone $base)->distinct()->orderBy('page_type')->pluck('page_type'),
            'classifications' => (clone $base)->distinct()->orderBy('classification')->pluck('classification'),
            'stats' => [
                'total' => (clone $base)->count(),
                'indexable' => (clone $base)->where('classification', 'INDEXABLE')->count(),
                'audited' => (clone $base)->whereHas('audits')->count(),
                'proposals' => (clone $base)->whereHas('proposals', fn (Builder $query) => $query->where('status', 'in_review'))->count(),
            ],
        ]);
    }
}
