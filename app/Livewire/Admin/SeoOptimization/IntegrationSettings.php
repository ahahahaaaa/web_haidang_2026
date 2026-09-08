<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationOutbox;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationPolicyService;
use App\Services\SeoOptimization\PageRegistryService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Kết nối Codex Schedule MCP')]
class IntegrationSettings extends OptimizationComponent
{
    public string $publishMode = 'preview';

    public array $allowedTypes = ['blog_post', 'tour', 'service', 'landing'];

    #[Locked]
    public ?string $policyRevision = null;

    public function mount(): void
    {
        app(OptimizationAccess::class)->authorize($this->actor(), 'settings');
        $policy = app(OptimizationPolicyService::class)->current();
        if ($policy) {
            $this->publishMode = $policy->publish_mode;
            $this->allowedTypes = $policy->allowed_page_types ?? [];
            $this->policyRevision = $policy->revision;
        }
    }

    public function savePolicy(): void
    {
        $this->perform(function () {
            $policy = app(OptimizationPolicyService::class)->save($this->actor(), $this->publishMode, $this->allowedTypes, $this->policyRevision);
            $this->policyRevision = $policy->revision;
        }, 'Đã lưu chế độ SEO tự động. Chưa tạo lịch hoặc bật các dòng Sheet.');
    }

    public function render()
    {
        app(OptimizationAccess::class)->authorize($this->actor(), 'settings');

        return view('livewire.admin.seo-optimization.integration-settings', [
            'mcpEnabled' => (bool) config('seo_optimization.mcp_enabled'),
            'endpoint' => url('/mcp/seo-optimization'),
            'lastMcpUse' => SeoOptimizationCredential::query()->max('last_used_at'),
            'pendingSheetEvents' => SeoOptimizationOutbox::query()->where('status', 'pending')->count(),
            'pageTypes' => PageRegistryService::PAGE_TYPES,
            'sheetConfigured' => filled(config('seo_optimization.spreadsheet_id')) && filled(config('seo_optimization.sheet_endpoint')) && filled(config('seo_optimization.sheet_secret')),
            'spreadsheetId' => config('seo_optimization.spreadsheet_id'),
            'completionEndpoint' => url('/mcp/seo-optimization/sync'),
        ]);
    }
}
