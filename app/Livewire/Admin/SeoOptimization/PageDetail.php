<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Services\SeoOptimization\KeywordBriefResolver;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Chi tiết tối ưu SEO')]
class PageDetail extends OptimizationComponent
{
    #[Locked]
    public string $pageId;

    #[Locked]
    public string $requestKey;

    public string $primaryKeyword = '';

    public string $searchIntent = '';

    public string $secondaryKeywords = '';

    public string $semanticTerms = '';

    public string $entities = '';

    public string $requiredTopics = '';

    public string $requiredInternalLinks = '';

    public string $factSources = '[]';

    public string $notes = '';

    public function mount(SeoOptimizationPage $page): void
    {
        app(OptimizationAccess::class)->authorize($this->actor(), 'index', $page);
        $this->pageId = (string) $page->getKey();
        $this->requestKey = (string) Str::uuid();
        $this->fillBrief($page->keyword_brief ?? []);
    }

    public function saveBrief(OptimizationWorkflowService $workflow): void
    {
        $page = $this->page('propose');
        $this->validate([
            'primaryKeyword' => ['required', 'string', 'max:200'],
            'searchIntent' => ['required', Rule::in(array_keys($this->intentOptions()))],
            'secondaryKeywords' => ['string', 'max:10000'], 'semanticTerms' => ['string', 'max:10000'],
            'entities' => ['string', 'max:10000'], 'requiredTopics' => ['string', 'max:10000'],
            'requiredInternalLinks' => ['string', 'max:10000'],
            'factSources' => ['required', 'json', 'max:50000'], 'notes' => ['string', 'max:5000'],
        ]);
        $sources = json_decode($this->factSources, true, 32);
        if (! is_array($sources) || ! array_is_list($sources)) {
            $this->addError('factSources', 'Nguồn dữ liệu phải là mảng JSON, ví dụ [].');

            return;
        }
        $brief = [
            'primary_keyword' => trim($this->primaryKeyword), 'search_intent' => $this->searchIntent,
            'secondary_keywords' => $this->lines($this->secondaryKeywords), 'semantic_terms' => $this->lines($this->semanticTerms),
            'entities' => $this->lines($this->entities), 'required_topics' => $this->lines($this->requiredTopics),
            'required_internal_links' => $this->lines($this->requiredInternalLinks),
            'fact_sources' => $sources, 'notes' => trim($this->notes),
        ];
        $saved = null;
        $this->perform(function () use ($workflow, $page, $brief, &$saved): void {
            $saved = $workflow->saveBrief($page, $brief, $this->actor());
        }, 'Đã lưu brief từ khóa và hoàn thiện tiêu chí đánh giá. Nội dung public chưa thay đổi.');
        if ($saved instanceof SeoOptimizationPage) {
            $this->fillBrief($saved->keyword_brief ?? []);
        }
    }

    public function runAudit(OptimizationWorkflowService $workflow, KeywordBriefResolver $briefs): void
    {
        $page = $this->page('audit');
        $completed = false;
        $this->perform(function () use ($workflow, $page, &$completed): void {
            $workflow->audit($page, $this->actor());
            $completed = true;
        }, 'Đã cập nhật kết quả kiểm tra SEO. Xem phạm vi đánh giá và dữ liệu còn thiếu bên dưới.');
        if ($completed) {
            $this->fillBrief($briefs->completeAuditInput($page, $page->fresh()->keyword_brief ?? []));
        }
    }

    public function enqueue(OptimizationWorkflowService $workflow): void
    {
        $page = $this->page('propose');
        $completed = false;
        $this->perform(function () use ($workflow, $page, &$completed): void {
            $workflow->enqueue($page, $this->actor(), $this->requestKey);
            $completed = true;
        }, 'Đã đưa vào hàng chờ Codex. Kết quả sẽ là đề xuất chờ người duyệt.');
        if ($completed) {
            $this->fillBrief($page->fresh()->keyword_brief ?? []);
        }
    }

    public function render()
    {
        $page = $this->page();

        return view('livewire.admin.seo-optimization.page-detail', [
            'page' => $page,
            'latestAudit' => $page->audits()->latest()->first(),
            'proposals' => $page->proposals()
                ->with('audit:id,page_id,score,grade,status,report,source_version,rule_version,created_at')
                ->latest()
                ->limit(20)
                ->get(),
            'tasks' => $page->tasks()->latest()->limit(10)->get(),
            'intents' => $this->intentOptions(),
        ]);
    }

    public function cancelTask(string $taskId, OptimizationWorkflowService $workflow): void
    {
        $task = $this->page('propose')->tasks()->findOrFail($taskId);
        $this->perform(function () use ($workflow, $task): void {
            $workflow->cancelTask($task, $this->actor());
            $this->requestKey = (string) Str::uuid();
        }, 'Đã hủy yêu cầu; kết quả từ lượt Codex cũ sẽ không được nhận.');
    }

    protected function page(string $ability = 'index'): SeoOptimizationPage
    {
        $page = SeoOptimizationPage::query()->findOrFail($this->pageId);
        app(OptimizationAccess::class)->authorize($this->actor(), $ability, $page);

        return $page;
    }

    protected function lines(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: []), fn (string $line): bool => $line !== '')));
    }

    private function fillBrief(array $brief): void
    {
        $this->primaryKeyword = (string) ($brief['primary_keyword'] ?? '');
        $this->searchIntent = (string) ($brief['search_intent'] ?? '');
        $this->notes = (string) ($brief['notes'] ?? '');
        foreach (['secondaryKeywords' => 'secondary_keywords', 'semanticTerms' => 'semantic_terms', 'entities' => 'entities', 'requiredTopics' => 'required_topics', 'requiredInternalLinks' => 'required_internal_links'] as $property => $key) {
            $this->{$property} = implode("\n", $brief[$key] ?? []);
        }
        $this->factSources = json_encode($brief['fact_sources'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function intentOptions(): array
    {
        return [
            'INFORMATIONAL' => 'Tìm hiểu thông tin', 'COMMERCIAL_INVESTIGATION' => 'So sánh, cân nhắc',
            'TRANSACTIONAL' => 'Đặt tour / sử dụng dịch vụ', 'NAVIGATIONAL' => 'Tìm thương hiệu / trang cụ thể',
            'LOCAL_SERVICE' => 'Dịch vụ theo địa phương',
        ];
    }
}
