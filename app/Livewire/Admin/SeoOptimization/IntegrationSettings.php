<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Mcp\Servers\SeoOptimizationServer;
use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Services\SeoOptimization\CodexSeoPluginPackage;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationPolicyService;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\SeoOptimizationCredentialService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Kết nối Codex Schedule MCP')]
class IntegrationSettings extends OptimizationComponent
{
    public string $publishMode = 'preview';

    public array $allowedTypes = ['blog_post', 'tour', 'service', 'landing'];

    public string $tokenUserId = '';

    public string $tokenName = 'Codex Schedule';

    public string $tokenDays = '30';

    public bool $tokenAutomation = true;

    public array $tokenAllowedTypes = ['blog_post', 'tour', 'service', 'landing'];

    #[Locked]
    public ?string $policyRevision = null;

    public function mount(): void
    {
        $actor = $this->actor();
        app(OptimizationAccess::class)->authorize($actor, 'settings');
        $this->tokenUserId = (string) $actor->id;
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
        }, 'Đã lưu chế độ SEO tự động. Codex Schedule sẽ dùng cấu hình này ở lượt nhận bài tiếp theo.');
    }

    public function createToken(SeoOptimizationCredentialService $credentials): void
    {
        $actor = $this->actor();
        app(OptimizationAccess::class)->authorize($actor, 'settings');

        $validated = $this->validate([
            'tokenUserId' => ['required', 'integer', 'exists:users,id'],
            'tokenName' => ['required', 'string', 'min:3', 'max:150'],
            'tokenDays' => ['required', 'integer', 'min:1', 'max:90'],
            'tokenAutomation' => ['boolean'],
            'tokenAllowedTypes' => ['required', 'array', 'min:1'],
            'tokenAllowedTypes.*' => ['string', Rule::in(PageRegistryService::PAGE_TYPES)],
        ]);

        $this->perform(function () use ($actor, $credentials, $validated): void {
            $subject = User::query()->findOrFail((int) $validated['tokenUserId']);
            $result = $credentials->issue(
                $subject,
                $validated['tokenName'],
                $validated['tokenAllowedTypes'],
                (int) $validated['tokenDays'],
                (bool) $validated['tokenAutomation'],
                $actor,
            );

            $this->dispatch(
                'seo-token-issued',
                token: $result['token'],
                tokenName: $result['credential']->name,
                envVar: CodexSeoPluginPackage::TOKEN_ENV_VAR,
            );
        }, 'Đã tạo token MCP. Giá trị bí mật chỉ hiển thị một lần ở khung bên dưới.');
    }

    public function revokeToken(string $credentialId, SeoOptimizationCredentialService $credentials): void
    {
        $actor = $this->actor();
        app(OptimizationAccess::class)->authorize($actor, 'settings');

        $this->perform(function () use ($actor, $credentialId, $credentials): void {
            $credential = SeoOptimizationCredential::query()->findOrFail($credentialId);
            $credentials->revoke($credential, $actor);
        }, 'Đã thu hồi token MCP. Các phiên dùng token này sẽ không còn được xác thực.');
    }

    public function render()
    {
        app(OptimizationAccess::class)->authorize($this->actor(), 'settings');

        $lastUsedCredential = SeoOptimizationCredential::query()
            ->whereNotNull('last_used_at')
            ->latest('last_used_at')
            ->first();
        $tokenAbilities = ['read', 'audit', 'propose'];
        if ($this->tokenAutomation) {
            $tokenAbilities[] = 'automate';
        }
        $mcpTools = SeoOptimizationServer::toolCatalog($tokenAbilities);

        return view('livewire.admin.seo-optimization.integration-settings', [
            'mcpEnabled' => (bool) config('seo_optimization.mcp_enabled'),
            'endpoint' => url('/mcp/seo-optimization'),
            'lastMcpUse' => $lastUsedCredential?->last_used_at,
            'activeCredentialCount' => SeoOptimizationCredential::query()
                ->whereNull('revoked_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->whereHas('user', fn ($query) => $query->where('is_active', true)->whereNotNull('email_verified_at'))
                ->count(),
            'tokenAccounts' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'email_verified_at']),
            'credentials' => SeoOptimizationCredential::query()->with('user:id,name,email')->latest()->limit(25)->get(),
            'pendingAutomationTasks' => SeoOptimizationTask::query()->whereNotNull('automation')->whereIn('status', ['queued', 'leased'])->count(),
            'pageTypes' => PageRegistryService::PAGE_TYPES,
            'completionEndpoint' => url('/mcp/seo-optimization/commit'),
            'scheduleBatchLimit' => (int) config('seo_optimization.schedule_batch_limit', 3),
            'tokenEnvVar' => CodexSeoPluginPackage::TOKEN_ENV_VAR,
            'pluginVersion' => CodexSeoPluginPackage::VERSION,
            'pluginAvailable' => app(CodexSeoPluginPackage::class)->available(),
            'mcpTools' => $mcpTools,
            'allowedMcpToolCount' => count(array_filter($mcpTools, fn (array $tool): bool => $tool['allowed'])),
        ]);
    }
}
