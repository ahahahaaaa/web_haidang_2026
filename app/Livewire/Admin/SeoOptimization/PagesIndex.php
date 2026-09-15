<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Actions\SeoOptimization\BulkAuditSeoPages;
use App\Actions\SeoOptimization\BulkUpdateKeywordBriefs;
use App\Models\SeoOptimizationAudit;
use App\Models\User;
use App\Services\SeoOptimization\KeywordBriefResolver;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationBrief;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use App\Services\SeoOptimization\PageRegistryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public string $scoreSort = '';

    public bool $selectAllFiltered = false;

    /** @var array<int, string> */
    public array $selectedPageIds = [];

    /** @var array<int, string> */
    public array $excludedPageIds = [];

    public string $bulkPrimaryKeyword = '';

    public string $bulkSearchIntent = '';

    public string $bulkSecondaryKeywords = '';

    public string $bulkSemanticTerms = '';

    public string $bulkEntities = '';

    public string $bulkRequiredTopics = '';

    public string $bulkRequiredInternalLinks = '';

    public string $bulkNotes = '';

    public function syncInventory(PageRegistryService $registry, OptimizationAccess $access): void
    {
        $access->authorize($this->actor(), 'audit');
        $this->authorizeAdminPermission('admin.seo-optimization.settings');
        $this->perform(fn () => $registry->sync(), 'Đã đối soát danh sách URL với dữ liệu CMS hiện tại.');
        $this->resetPage();
    }

    public function syncDefaultKeywords(KeywordBriefResolver $briefs, OptimizationAccess $access): void
    {
        $actor = $this->actor();
        $this->authorizeAdminPermission('admin.seo-optimization.propose');
        $query = $this->applyFilters($access->queryFor($actor, 'propose'));
        $result = null;

        $this->perform(
            function () use ($briefs, $query, $actor, &$result): void {
                $result = $briefs->syncDefaults($query, $actor);
            },
            'Đã đối soát từ khóa mặc định cho toàn bộ URL khớp bộ lọc. Brief đã chỉnh thủ công được giữ nguyên.',
        );
        if (is_array($result)) {
            session()->flash('status', sprintf(
                'Đã cập nhật %d URL, %d URL không đổi; giữ nguyên %d brief thủ công, bỏ qua %d URL đang xử lý và %d xung đột từ khóa.',
                $result['updated'],
                $result['unchanged'],
                $result['manual_preserved'],
                $result['active_work_preserved'],
                $result['conflicts'],
            ));
        }
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'pageType', 'classification', 'briefFilter'], true)) {
            $this->resetSelection();
            $this->resetPage();
        }
    }

    public function toggleSelectAllFiltered(OptimizationAccess $access): void
    {
        $actor = $this->actor();
        abort_unless($this->canSelect($actor), 403);
        $access->authorize($actor, $this->selectionAction($actor));
        $this->selectAllFiltered = ! $this->selectAllFiltered;
        $this->selectedPageIds = [];
        $this->excludedPageIds = [];
    }

    public function togglePageSelection(string $pageId, OptimizationAccess $access): void
    {
        abort_unless(Str::isUlid($pageId), 404);
        $actor = $this->actor();
        abort_unless($this->canSelect($actor), 403);
        abort_unless($this->filteredSelectableQuery($access, $actor)->whereKey($pageId)->exists(), 404);

        if ($this->selectAllFiltered) {
            $this->excludedPageIds = $this->toggleId($this->excludedPageIds, $pageId);

            return;
        }

        $this->selectedPageIds = $this->toggleId($this->selectedPageIds, $pageId);
    }

    public function clearSelection(): void
    {
        $this->resetSelection();
    }

    public function auditPage(string $pageId, OptimizationWorkflowService $workflow, OptimizationAccess $access): void
    {
        abort_unless(Str::isUlid($pageId), 404);
        $actor = $this->actor();
        $this->authorizeAdminPermission('admin.seo-optimization.audit');
        $page = $access->queryFor($actor, 'audit')->whereKey($pageId)->firstOrFail();

        $this->perform(
            fn () => $workflow->audit($page, $actor),
            'Đã cập nhật điểm SEO cho URL “'.($page->title ?: $page->path).'”.',
        );
    }

    public function auditSelected(BulkAuditSeoPages $bulk, OptimizationAccess $access): void
    {
        $actor = $this->actor();
        $this->authorizeAdminPermission('admin.seo-optimization.audit');
        $query = $this->selectedAuditableQuery($access, $actor);

        if (! (clone $query)->exists()) {
            $this->addError('bulkAudit', 'Chưa chọn URL nào còn khớp bộ lọc và quyền kiểm tra hiện tại.');

            return;
        }

        $result = null;
        $this->perform(function () use ($bulk, $query, $actor, &$result): void {
            $result = $bulk->handle($query, $actor);
        }, 'Đã hoàn tất kiểm tra SEO hàng loạt.');

        if (! is_array($result)) {
            return;
        }

        if ($result['audited'] === 0 && $result['failed'] > 0) {
            session()->forget('status');
            $this->addError('bulkAudit', sprintf(
                'Không thể kiểm tra %d URL đã chọn. Chi tiết lỗi đã được ghi nhận; vui lòng kiểm tra trạng thái URL rồi thử lại.',
                $result['failed'],
            ));

            return;
        }

        session()->flash('status', sprintf(
            'Đã kiểm tra %d/%d URL: %d URL có điểm, %d URL chưa đủ dữ liệu để tính tổng%s.',
            $result['audited'],
            $result['selected'],
            $result['scored'],
            $result['partial'],
            $result['failed'] > 0 ? ', '.$result['failed'].' URL lỗi đã được bỏ qua' : '',
        ));
        $this->resetSelection();
        $this->resetPage();
    }

    public function applyBulkBrief(BulkUpdateKeywordBriefs $bulk, OptimizationAccess $access): void
    {
        $actor = $this->actor();
        $this->authorizeAdminPermission('admin.seo-optimization.propose');
        $this->validate([
            'bulkPrimaryKeyword' => ['nullable', 'string', 'max:200'],
            'bulkSearchIntent' => ['nullable', Rule::in(OptimizationBrief::INTENTS)],
            'bulkSecondaryKeywords' => ['nullable', 'string', 'max:10000'],
            'bulkSemanticTerms' => ['nullable', 'string', 'max:10000'],
            'bulkEntities' => ['nullable', 'string', 'max:10000'],
            'bulkRequiredTopics' => ['nullable', 'string', 'max:10000'],
            'bulkRequiredInternalLinks' => ['nullable', 'string', 'max:10000'],
            'bulkNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $changes = $this->bulkBriefChanges();
        if ($changes === []) {
            $this->addError('bulkBrief', 'Nhập ít nhất một trường brief cần bổ sung. Các ô trống sẽ được giữ nguyên.');

            return;
        }

        $query = $this->selectedProposableQuery($access, $actor);
        if (! (clone $query)->exists()) {
            $this->addError('bulkBrief', 'Chưa chọn URL nào còn khớp bộ lọc và quyền hiện tại.');

            return;
        }

        $result = null;
        $this->perform(function () use ($bulk, $query, $actor, &$result): void {
            $result = $bulk->handle($query, $this->bulkBriefChanges(), $actor);
        }, 'Đã cập nhật brief từ khóa cho các URL được chọn.');

        if (! is_array($result)) {
            return;
        }

        session()->flash('status', sprintf(
            'Đã cập nhật %d/%d URL; %d URL không đổi, giữ nguyên %d URL đang trong hàng chờ, bỏ qua %d URL chưa đủ từ khóa chính/intent và %d xung đột sở hữu từ khóa.',
            $result['updated'],
            $result['selected'],
            $result['unchanged'],
            $result['active_work_preserved'],
            $result['incomplete'],
            $result['conflicts'],
        ));
        $this->resetBulkBriefForm();
        $this->resetSelection();
        $this->modal('bulk-keyword-brief')->close();
        $this->resetPage();
    }

    public function sortByScore(): void
    {
        $this->scoreSort = $this->scoreSort === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    public function render()
    {
        $actor = $this->actor();
        $access = app(OptimizationAccess::class);
        $base = $access->queryFor($actor);
        $this->authorizeAdminPermission('admin.seo-optimization.index');
        $query = $this->applyFilters(clone $base);
        $canAudit = $actor->can('admin.seo-optimization.audit');
        $canBulkBrief = $actor->can('admin.seo-optimization.propose');
        $canSelect = $canAudit || $canBulkBrief;
        $selectedCount = $canSelect
            ? $this->selectedSelectableQuery($access, $actor)->count()
            : 0;
        $selectedAuditCount = $canAudit
            ? $this->selectedAuditableQuery($access, $actor)->count()
            : 0;
        $selectedBriefCount = $canBulkBrief
            ? $this->selectedProposableQuery($access, $actor)->count()
            : 0;
        $bulkFilteredCount = $canSelect
            ? $this->filteredSelectableQuery($access, $actor)->count()
            : 0;
        $proposableTypes = array_keys(array_filter(
            OptimizationAccess::PAGE_PERMISSIONS,
            fn (string $permission): bool => $actor->can($permission.'.edit'),
        ));
        $selectableTypes = $canAudit
            ? array_keys(array_filter(
                OptimizationAccess::PAGE_PERMISSIONS,
                fn (string $permission): bool => $actor->can($permission.'.index'),
            ))
            : $proposableTypes;

        return view('livewire.admin.seo-optimization.pages-index', [
            'pages' => $this->applyOrdering($query
                ->with(['latestAudit' => fn ($query) => $query->select([
                    'seo_optimization_audits.id',
                    'seo_optimization_audits.page_id',
                    'seo_optimization_audits.source_version',
                    'seo_optimization_audits.score',
                    'seo_optimization_audits.grade',
                    'seo_optimization_audits.status',
                    'seo_optimization_audits.report',
                    'seo_optimization_audits.created_at',
                ])])
                ->withCount(['audits', 'proposals', 'tasks']))
                ->paginate(25),
            'pageTypes' => (clone $base)->distinct()->orderBy('page_type')->pluck('page_type'),
            'classifications' => (clone $base)->distinct()->orderBy('classification')->pluck('classification'),
            'intents' => $this->intentOptions(),
            'canAudit' => $canAudit,
            'canSelect' => $canSelect,
            'selectedCount' => $selectedCount,
            'selectedAuditCount' => $selectedAuditCount,
            'selectedBriefCount' => $selectedBriefCount,
            'bulkFilteredCount' => $bulkFilteredCount,
            'selectableTypes' => $selectableTypes,
            'stats' => [
                'total' => (clone $base)->count(),
                'indexable' => (clone $base)->where('classification', 'INDEXABLE')->count(),
                'audited' => (clone $base)->whereHas('audits')->count(),
                'proposals' => (clone $base)->whereHas('proposals', fn (Builder $query) => $query->where('status', 'in_review'))->count(),
            ],
        ]);
    }

    public function keywordOriginLabel(?string $origin): string
    {
        return [
            'site_seo_keywords' => 'Bộ SEO chính',
            'server_keyword_fallback' => 'Tiêu đề trang (dự phòng)',
            'server_keyword_profile' => 'Hồ sơ SEO tự động',
            'cms_review_brief' => 'Đã chỉnh thủ công',
        ][$origin ?? ''] ?? 'Nguồn khác';
    }

    public function isPageSelected(string $pageId): bool
    {
        return $this->selectAllFiltered
            ? ! in_array($pageId, $this->excludedPageIds, true)
            : in_array($pageId, $this->selectedPageIds, true);
    }

    private function applyFilters(Builder $query): Builder
    {
        return $query
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
    }

    private function applyOrdering(Builder $query): Builder
    {
        if (! in_array($this->scoreSort, ['asc', 'desc'], true)) {
            return $query->latest('last_seen_at')->orderBy('id');
        }

        return $query
            ->addSelect([
                'latest_audit_score' => SeoOptimizationAudit::query()
                    ->select('score')
                    ->whereColumn('seo_optimization_audits.page_id', 'seo_optimization_pages.id')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(1),
            ])
            ->orderByRaw('CASE WHEN latest_audit_score IS NOT NULL THEN 0 WHEN audits_count > 0 THEN 1 ELSE 2 END')
            ->orderBy('latest_audit_score', $this->scoreSort)
            ->latest('last_seen_at')
            ->orderBy('id');
    }

    private function filteredProposableQuery(OptimizationAccess $access, User $actor): Builder
    {
        return $this->applyFilters($access->queryFor($actor, 'propose'));
    }

    private function filteredAuditableQuery(OptimizationAccess $access, User $actor): Builder
    {
        return $this->applyFilters($access->queryFor($actor, 'audit'));
    }

    private function filteredSelectableQuery(OptimizationAccess $access, User $actor): Builder
    {
        return $this->applyFilters($access->queryFor($actor, $this->selectionAction($actor)));
    }

    private function selectedProposableQuery(OptimizationAccess $access, User $actor): Builder
    {
        return $this->selectedQuery($this->filteredProposableQuery($access, $actor));
    }

    private function selectedAuditableQuery(OptimizationAccess $access, User $actor): Builder
    {
        return $this->selectedQuery($this->filteredAuditableQuery($access, $actor));
    }

    private function selectedSelectableQuery(OptimizationAccess $access, User $actor): Builder
    {
        return $this->selectedQuery($this->filteredSelectableQuery($access, $actor));
    }

    private function selectedQuery(Builder $query): Builder
    {
        return $this->selectAllFiltered
            ? $query->when($this->excludedPageIds !== [], fn (Builder $query) => $query->whereKeyNot($this->excludedPageIds))
            : $query->whereKey($this->selectedPageIds);
    }

    private function canSelect(User $actor): bool
    {
        return $actor->can('admin.seo-optimization.audit')
            || $actor->can('admin.seo-optimization.propose');
    }

    private function selectionAction(User $actor): string
    {
        return $actor->can('admin.seo-optimization.audit') ? 'audit' : 'propose';
    }

    /** @return array<int, string> */
    private function toggleId(array $ids, string $pageId): array
    {
        return in_array($pageId, $ids, true)
            ? array_values(array_diff($ids, [$pageId]))
            : [...$ids, $pageId];
    }

    /** @return array<string, string|array<int, string>> */
    private function bulkBriefChanges(): array
    {
        $changes = [];
        foreach ([
            'bulkPrimaryKeyword' => 'primary_keyword',
            'bulkSearchIntent' => 'search_intent',
            'bulkNotes' => 'notes',
        ] as $property => $field) {
            if (trim($this->{$property}) !== '') {
                $changes[$field] = trim($this->{$property});
            }
        }
        foreach ([
            'bulkSecondaryKeywords' => 'secondary_keywords',
            'bulkSemanticTerms' => 'semantic_terms',
            'bulkEntities' => 'entities',
            'bulkRequiredTopics' => 'required_topics',
            'bulkRequiredInternalLinks' => 'required_internal_links',
        ] as $property => $field) {
            $values = $this->lines($this->{$property});
            if ($values !== []) {
                $changes[$field] = $values;
            }
        }

        return $changes;
    }

    /** @return array<int, string> */
    private function lines(string $value): array
    {
        return array_values(array_unique(array_filter(
            array_map('trim', preg_split('/\R/u', $value) ?: []),
            fn (string $line): bool => $line !== '',
        )));
    }

    private function resetSelection(): void
    {
        $this->selectAllFiltered = false;
        $this->selectedPageIds = [];
        $this->excludedPageIds = [];
    }

    private function resetBulkBriefForm(): void
    {
        $this->reset([
            'bulkPrimaryKeyword',
            'bulkSearchIntent',
            'bulkSecondaryKeywords',
            'bulkSemanticTerms',
            'bulkEntities',
            'bulkRequiredTopics',
            'bulkRequiredInternalLinks',
            'bulkNotes',
        ]);
    }

    /** @return array<string, string> */
    private function intentOptions(): array
    {
        return [
            'INFORMATIONAL' => 'Tìm hiểu thông tin',
            'COMMERCIAL_INVESTIGATION' => 'So sánh, cân nhắc',
            'TRANSACTIONAL' => 'Đặt tour / sử dụng dịch vụ',
            'NAVIGATIONAL' => 'Tìm thương hiệu / trang cụ thể',
            'LOCAL_SERVICE' => 'Dịch vụ theo địa phương',
        ];
    }
}
