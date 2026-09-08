<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationProposal;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use App\Services\SeoOptimization\PageRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

abstract class OptimizationTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $writer;

    protected User $reviewer;

    protected Service $service;

    protected SeoOptimizationPage $page;

    protected SeoOptimizationCredential $credential;

    protected OptimizationWorkflowService $workflow;

    protected string $testToken = 'test-only-seo-token-not-for-real-use-123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config(['seo_optimization.enabled' => true, 'seo_optimization.site_id' => 'haidang-test', 'seo_optimization.apply_enabled' => true, 'seo_optimization.mcp_enabled' => true, 'frontsite_seo.canonical_url' => 'https://haidangtravel.test']);
        SiteSetting::query()->create(['id' => 1, 'active_theme' => 'haidangtravel', 'site_name' => 'Hải Đăng Travel', 'company_name' => 'Hải Đăng Travel']);
        $this->service = Service::query()->create(['title' => 'Tư vấn hành trình Đà Nẵng', 'slug' => 'tu-van-da-nang', 'status' => 'published', 'meta_title' => 'Tư vấn Đà Nẵng', 'meta_description' => 'Tư vấn hành trình theo nhu cầu.', 'content' => '<h2>Chuẩn bị hành trình</h2><p>Chọn điểm đến và trao đổi nhu cầu với tư vấn viên.</p>']);
        $this->writer = $this->editor();
        $this->reviewer = $this->editor();
        $this->credential = SeoOptimizationCredential::query()->create(['user_id' => $this->writer->id, 'name' => 'Test Codex', 'token_hash' => hash('sha256', $this->testToken), 'abilities' => ['read', 'audit', 'propose'], 'allowed_page_types' => ['service'], 'expires_at' => now()->addDay()]);
        app(PageRegistryService::class)->sync();
        $this->page = SeoOptimizationPage::query()->where('page_type', 'service')->firstOrFail();
        $this->workflow = app(OptimizationWorkflowService::class);
        $this->workflow->saveBrief($this->page, ['primary_keyword' => 'tư vấn Đà Nẵng', 'search_intent' => 'LOCAL_SERVICE'], $this->reviewer);
        Http::preventStrayRequests();
    }

    protected function editor(): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $names = ['access admin panel', 'admin.services.index', 'admin.services.edit'];
        foreach (['index', 'audit', 'propose', 'approve', 'apply', 'rollback', 'settings'] as $action) {
            $names[] = 'admin.seo-optimization.'.$action;
        }
        foreach ($names as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $user;
    }

    protected function lease(): array
    {
        $task = $this->workflow->enqueue($this->page, $this->writer, 'test-queue-'.str()->uuid());

        return $this->workflow->claim($this->writer, $this->credential->id, $task->id);
    }

    protected function payload(array $lease, array $patch = ['meta_description' => 'Tư vấn Đà Nẵng: trao đổi nhu cầu và chuẩn bị hành trình phù hợp.']): array
    {
        return ['expected_version' => $lease['source_version'], 'patch' => $patch, 'notes' => 'Làm rõ nội dung từ nguồn hiện hữu.', 'claims' => [], 'missing_facts' => []];
    }

    protected function proposal(): SeoOptimizationProposal
    {
        $lease = $this->lease();

        return $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease), $this->writer, $this->credential->id, 'test-submit-'.str()->uuid());
    }
}
