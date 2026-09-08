<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;

class OptimizationMcpTest extends OptimizationTestCase
{
    public function test_mcp_still_requires_a_verified_account_even_for_super_admin(): void
    {
        $this->writer->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->writer->forceFill(['email_verified_at' => null])->save();

        $this->rpc('tools/list')->assertUnauthorized();
    }

    private function rpc(string $method, array $params = [], ?string $token = null): TestResponse
    {
        return $this->withToken($token ?? $this->testToken)->postJson('/mcp/seo-optimization', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params]);
    }

    private function callTool(string $name, array $arguments = []): TestResponse
    {
        return $this->rpc('tools/call', ['name' => $name, 'arguments' => (object) $arguments]);
    }

    public function test_http_handshake_exposes_only_eight_scoped_draft_tools(): void
    {
        $this->rpc('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'SEO Test', 'version' => '1.0']])->assertOk()->assertJsonPath('result.serverInfo.name', 'Hải Đăng Travel — SEO AI Optimize');
        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');
        $this->assertCount(8, $tools);
        $names = array_column($tools, 'name');
        $this->assertContains('submit_seo_optimization', $names);
        foreach (['approve_seo_proposal', 'apply_seo_proposal', 'publish', 'rollback'] as $forbidden) {
            $this->assertNotContains($forbidden, $names);
        }
        $this->callTool('list_seo_pages')->assertOk()->assertJsonPath('result.structuredContent.pages.0.id', $this->page->id);
        $this->callTool('get_seo_page_snapshot', ['page_id' => $this->page->id])->assertOk()->assertJsonPath('result.isError', false);
    }

    public function test_actual_http_mcp_can_request_claim_submit_and_read_without_publishing(): void
    {
        $this->callTool('request_seo_optimization', ['page_id' => $this->page->id, 'idempotency_key' => 'http-request-test'])->assertOk()->assertJsonPath('result.isError', false);
        $leaseResponse = $this->callTool('claim_seo_optimization')->assertOk();
        $lease = $leaseResponse->json('result.structuredContent.task');
        $this->assertIsArray($lease, $leaseResponse->getContent());
        $arguments = ['task_id' => $lease['task_id'], 'lease_token' => $lease['lease_token'], 'idempotency_key' => 'http-submit-test', 'payload' => $this->payload($lease)];
        $result = $this->callTool('submit_seo_optimization', $arguments)->assertOk();
        $this->assertFalse($result->json('result.isError'), $result->getContent());
        $proposal = SeoOptimizationProposal::firstOrFail();
        $this->assertSame('in_review', $proposal->status);
        $this->assertSame('Tư vấn hành trình theo nhu cầu.', $this->service->fresh()->meta_description);
        $this->callTool('get_seo_proposal', ['proposal_id' => $proposal->id])->assertOk()->assertJsonPath('result.structuredContent.proposal.status', 'in_review');
        $this->assertSame(1, SeoOptimizationTask::count());
    }

    public function test_disabled_invalid_revoked_expired_and_cross_origin_connections_are_denied(): void
    {
        config(['seo_optimization.mcp_enabled' => false]);
        $this->rpc('tools/list')->assertNotFound();
        config(['seo_optimization.mcp_enabled' => true]);
        $this->rpc('tools/list', [], str_repeat('x', 40))->assertUnauthorized();
        $this->credential->update(['expires_at' => now()->subMinute()]);
        $this->rpc('tools/list')->assertUnauthorized();
        $this->credential->update(['expires_at' => now()->addDay(), 'revoked_at' => now()]);
        $this->rpc('tools/list')->assertUnauthorized();
        $this->credential->update(['revoked_at' => null]);
        $this->withHeader('Origin', 'https://untrusted.example')->rpc('tools/list')->assertForbidden();
    }

    public function test_read_only_token_cannot_invoke_mutating_tools(): void
    {
        $this->credential->update(['abilities' => ['read']]);
        $this->assertCount(3, $this->rpc('tools/list')->json('result.tools'));
        $response = $this->callTool('request_seo_optimization', ['page_id' => $this->page->id, 'idempotency_key' => 'read-only-request']);
        $this->assertNotNull($response->json('error'));
        $this->assertSame(0, SeoOptimizationTask::count());
    }

    public function test_token_scope_and_newly_private_source_are_checked_on_every_content_read(): void
    {
        $proposal = $this->proposal();
        $this->credential->update(['allowed_page_types' => ['blog_post']]);
        $this->callTool('get_seo_proposal', ['proposal_id' => $proposal->id])->assertJsonPath('result.isError', true);
        $this->credential->update(['allowed_page_types' => ['service']]);
        $this->service->update(['status' => 'draft']);
        $this->callTool('get_seo_proposal', ['proposal_id' => $proposal->id])->assertJsonPath('result.isError', true);
        $this->callTool('get_seo_page_snapshot', ['page_id' => $this->page->id])->assertJsonPath('result.isError', true);
    }
}
