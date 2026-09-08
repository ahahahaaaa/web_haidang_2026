<?php

namespace App\Livewire\Admin\Seo;

use Illuminate\Validation\Rules\Enum;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Seo\Actions\CreateClusterAction;
use Src\Domains\Seo\Actions\CreateSeoPageAction;
use Src\Domains\Seo\Actions\DispatchClusterGenerationAction;
use Src\Domains\Seo\Actions\SeedSeoDemoPagesAction;
use Src\Domains\Seo\Enums\SeoClusterStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Jobs\GenerateSeoDraftJob;
use Src\Domains\Seo\Models\SeoPage;

class SeoPagesIndex extends Component
{
    use WithPagination;

    public array $clusterForm = [];

    public array $manualForm = [];

    public string $search = '';

    public string $status = '';

    public function mount(): void
    {
        $this->resetClusterForm();
        $this->resetManualForm();
    }

    public function createCluster(CreateClusterAction $createCluster, DispatchClusterGenerationAction $dispatchGeneration): void
    {
        $validated = $this->validate($this->clusterRules());

        $cluster = $createCluster->execute([
            'name' => $validated['clusterForm']['name'],
            'primary_keyword' => $validated['clusterForm']['primary_keyword'],
            'secondary_keywords' => $this->parseKeywordList($validated['clusterForm']['secondary_keywords_text'] ?? null),
            'lsi_keywords' => $this->parseKeywordList($validated['clusterForm']['lsi_keywords_text'] ?? null),
            'intent' => $validated['clusterForm']['intent'],
            'target_page_type' => $validated['clusterForm']['target_page_type'],
            'priority_score' => 50,
            'status' => SeoClusterStatus::Approved->value,
            'context' => $this->buildContextPayload(
                $validated['clusterForm']['context_notes'] ?? null,
                $validated['clusterForm']['cta'] ?? null,
                $validated['clusterForm']['location'] ?? null,
            ),
        ]);

        if ($validated['clusterForm']['dispatch_generation'] ?? true) {
            $dispatchGeneration->execute($cluster);
            session()->flash('status', 'Đã tạo cluster SEO và đưa tác vụ generate vào hàng đợi.');
        } else {
            session()->flash('status', 'Đã tạo cluster SEO ở trạng thái sẵn sàng đồng bộ.');
        }

        $this->resetClusterForm();
    }

    public function createManualPage(CreateSeoPageAction $createSeoPage): void
    {
        $validated = $this->validate($this->manualRules());

        $page = $createSeoPage->execute([
            'page_type' => $validated['manualForm']['page_type'],
            'title' => $validated['manualForm']['title'] ?? null,
            'slug' => $validated['manualForm']['slug'] ?? null,
            'primary_keyword' => $validated['manualForm']['primary_keyword'],
            'secondary_keywords' => $this->parseKeywordList($validated['manualForm']['secondary_keywords_text'] ?? null),
            'h1' => $validated['manualForm']['h1'] ?? null,
        ]);

        if ($validated['manualForm']['generate_after_create'] ?? true) {
            GenerateSeoDraftJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo'));
            session()->flash('status', 'Đã tạo SEO page và đưa tác vụ generate vào hàng đợi.');
        } else {
            session()->flash('status', 'Đã tạo SEO page nháp.');
        }

        $this->resetManualForm();
        $this->redirectRoute('admin.seo.pages.edit', ['page' => $page->getKey()], navigate: true);
    }

    public function seedDemoPages(SeedSeoDemoPagesAction $seedSeoDemoPages): void
    {
        $pages = $seedSeoDemoPages->execute();

        session()->flash('status', 'Đã nạp '.count($pages).' SEO page demo cho các hub travel như danh mục tour, điểm đến, vùng miền, quốc gia, dịch vụ, blog và liên hệ.');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $pages = SeoPage::query()
            ->when($this->search !== '', fn ($query) => $query->where('primary_keyword', 'like', '%'.$this->search.'%'))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.seo.pages-index', [
            'pageTypeOptions' => $this->pageTypeOptions(),
            'pages' => $pages,
            'statusClasses' => $this->statusClasses(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    protected function buildContextPayload(?string $notes, ?string $cta, ?string $location): ?array
    {
        $context = [];

        if (trim((string) $notes) !== '') {
            $context['notes'] = trim((string) $notes);
        }

        if (trim((string) $cta) !== '') {
            $context['cta'] = trim((string) $cta);
        }

        if (trim((string) $location) !== '') {
            $context['location'] = trim((string) $location);
        }

        return $context === [] ? null : $context;
    }

    protected function clusterRules(): array
    {
        return [
            'clusterForm.name' => ['required', 'string', 'max:255'],
            'clusterForm.primary_keyword' => ['required', 'string', 'max:255'],
            'clusterForm.secondary_keywords_text' => ['nullable', 'string'],
            'clusterForm.lsi_keywords_text' => ['nullable', 'string'],
            'clusterForm.intent' => ['required', 'string', 'max:50'],
            'clusterForm.target_page_type' => ['required', new Enum(SeoPageType::class)],
            'clusterForm.context_notes' => ['nullable', 'string'],
            'clusterForm.cta' => ['nullable', 'string', 'max:255'],
            'clusterForm.location' => ['nullable', 'string', 'max:255'],
            'clusterForm.dispatch_generation' => ['boolean'],
        ];
    }

    protected function manualRules(): array
    {
        return [
            'manualForm.title' => ['nullable', 'string', 'max:255'],
            'manualForm.slug' => ['nullable', 'string', 'max:255'],
            'manualForm.primary_keyword' => ['required', 'string', 'max:255'],
            'manualForm.secondary_keywords_text' => ['nullable', 'string'],
            'manualForm.page_type' => ['required', new Enum(SeoPageType::class)],
            'manualForm.h1' => ['nullable', 'string', 'max:255'],
            'manualForm.generate_after_create' => ['boolean'],
        ];
    }

    protected function pageTypeOptions(): array
    {
        return SeoPageType::adminOptions();
    }

    protected function parseKeywordList(?string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $value))
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function resetClusterForm(): void
    {
        $this->clusterForm = [
            'name' => '',
            'primary_keyword' => '',
            'secondary_keywords_text' => '',
            'lsi_keywords_text' => '',
            'intent' => 'commercial',
            'target_page_type' => SeoPageType::Destination->value,
            'context_notes' => '',
            'cta' => 'Nhận tư vấn hành trình',
            'location' => '',
            'dispatch_generation' => true,
        ];
    }

    protected function resetManualForm(): void
    {
        $this->manualForm = [
            'title' => '',
            'slug' => '',
            'primary_keyword' => '',
            'secondary_keywords_text' => '',
            'page_type' => SeoPageType::Destination->value,
            'h1' => '',
            'generate_after_create' => true,
        ];
    }

    protected function statusClasses(): array
    {
        return [
            'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            'generated' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            'qa_failed' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
            'pending_review' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'approved' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
            'published' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'archived' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        ];
    }

    protected function statusLabels(): array
    {
        return [
            'draft' => 'Nháp',
            'generated' => 'Đã sinh nháp',
            'qa_failed' => 'QA lỗi',
            'pending_review' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'published' => 'Đã xuất bản',
            'archived' => 'Lưu trữ',
        ];
    }
}
