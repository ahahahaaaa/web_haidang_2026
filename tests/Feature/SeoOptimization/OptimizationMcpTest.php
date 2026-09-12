<?php

namespace Tests\Feature\SeoOptimization;

use App\Mcp\Servers\SeoOptimizationServer;
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

    public function test_http_handshake_exposes_scoped_draft_and_backup_tools(): void
    {
        $this->rpc('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'SEO Test', 'version' => '1.0']])->assertOk()->assertJsonPath('result.serverInfo.name', 'Hải Đăng Travel — SEO AI Optimize');
        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');
        $this->assertCount(10, $tools);
        $names = array_column($tools, 'name');
        $expectedNames = collect(SeoOptimizationServer::toolCatalog(['read', 'audit', 'propose']))
            ->where('allowed', true)
            ->pluck('name')
            ->all();
        $this->assertEqualsCanonicalizing($expectedNames, $names);
        $this->assertContains('submit_seo_optimization', $names);
        $this->assertContains('list_content_backups', $names);
        $this->assertContains('request_content_restore', $names);
        foreach (['approve_seo_proposal', 'apply_seo_proposal', 'publish', 'rollback'] as $forbidden) {
            $this->assertNotContains($forbidden, $names);
        }
        $submit = collect($tools)->firstWhere('name', 'submit_seo_optimization');
        $this->assertSame('array', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.faq_items.type'));
        $this->assertSame('object', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.faq_items.items.type'));
        $this->assertSame('string', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.faq_items.items.properties.question.type'));
        $this->assertSame('array', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.block_changes.type'));
        $this->assertSame('object', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.block_changes.items.type'));
        $this->assertSame('string', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.block_changes.items.properties.uuid.type'));
        $this->assertSame('object', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.block_changes.items.properties.changes.type'));
        $this->assertSame('string', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.block_changes.items.properties.changes.properties.cta_label.type'));
        $this->assertSame('string', data_get($submit, 'inputSchema.properties.payload.properties.patch.properties.block_changes.items.properties.changes.properties.panel_description.type'));
        $this->assertSame('array', data_get($submit, 'inputSchema.properties.payload.properties.warnings.type'));
        $this->assertNotContains('claims', data_get($submit, 'inputSchema.properties.payload.required', []));
        $this->assertNotContains('missing_facts', data_get($submit, 'inputSchema.properties.payload.required', []));
        $this->callTool('list_seo_pages')->assertOk()->assertJsonPath('result.structuredContent.pages.0.id', $this->page->id);
        $this->callTool('get_seo_page_snapshot', ['page_id' => $this->page->id])->assertOk()->assertJsonPath('result.isError', false);
    }

    public function test_submit_contract_rejects_string_faq_and_accepts_structured_faq_items(): void
    {
        $this->callTool('request_seo_optimization', [
            'page_id' => $this->page->id,
            'idempotency_key' => 'faq-request-test',
        ])->assertOk()->assertJsonPath('result.isError', false);
        $leaseResponse = $this->callTool('claim_seo_optimization')->assertOk();
        $lease = $leaseResponse->json('result.structuredContent.task');
        $invalid = $this->payload($lease, ['faq_items' => 'FAQ không đúng kiểu']);

        $this->callTool('submit_seo_optimization', [
            'task_id' => $lease['task_id'],
            'lease_token' => $lease['lease_token'],
            'idempotency_key' => 'faq-invalid-submit',
            'payload' => $invalid,
        ])->assertOk()->assertJsonPath('result.isError', true);

        $faq = [[
            'question' => 'Nên chuẩn bị gì trước hành trình?',
            'answer' => '<p>Hãy trao đổi nhu cầu với tư vấn viên Hải Đăng Travel.</p>',
        ]];
        $valid = $this->payload($lease, ['faq_items' => $faq]);
        $valid['warnings'] = ['Dữ kiện hiện hữu cần đối chiếu thêm nhưng không chặn tối ưu.'];
        $result = $this->callTool('submit_seo_optimization', [
            'task_id' => $lease['task_id'],
            'lease_token' => $lease['lease_token'],
            'idempotency_key' => 'faq-valid-submit',
            'payload' => $valid,
        ])->assertOk();

        $this->assertFalse($result->json('result.isError'), $result->getContent());
        $this->assertSame('Dữ kiện hiện hữu cần đối chiếu thêm nhưng không chặn tối ưu.', $result->json('result.structuredContent.warnings.0'));
        $proposal = SeoOptimizationProposal::firstOrFail();
        $this->assertSame($faq, $proposal->patch['faq_items']);
        $this->assertSame('in_review', $proposal->status);
        $this->assertContains('Dữ kiện hiện hữu cần đối chiếu thêm nhưng không chặn tối ưu.', $proposal->qa['warnings']);
    }

    public function test_actual_http_mcp_can_request_claim_submit_and_read_without_publishing(): void
    {
        $this->callTool('request_seo_optimization', ['page_id' => $this->page->id, 'idempotency_key' => 'http-request-test'])->assertOk()->assertJsonPath('result.isError', false);
        $leaseResponse = $this->callTool('claim_seo_optimization')->assertOk();
        $lease = $leaseResponse->json('result.structuredContent.task');
        $this->assertIsArray($lease, $leaseResponse->getContent());
        $payload = $this->payload($lease);
        unset($payload['claims'], $payload['missing_facts']);
        $arguments = ['task_id' => $lease['task_id'], 'lease_token' => $lease['lease_token'], 'idempotency_key' => 'http-submit-test', 'payload' => $payload];
        $result = $this->callTool('submit_seo_optimization', $arguments)->assertOk();
        $this->assertFalse($result->json('result.isError'), $result->getContent());
        $this->assertSame('human_review_required', $result->json('result.structuredContent.approval_mode'));
        $this->assertSame('await_human_review', $result->json('result.structuredContent.next_action'));
        $proposal = SeoOptimizationProposal::firstOrFail();
        $this->assertSame('in_review', $proposal->status);
        $this->assertSame('Tư vấn hành trình theo nhu cầu.', $this->service->fresh()->meta_description);
        $this->callTool('get_seo_proposal', ['proposal_id' => $proposal->id])->assertOk()->assertJsonPath('result.structuredContent.proposal.status', 'in_review');
        $this->assertSame(1, SeoOptimizationTask::count());
    }

    public function test_http_mcp_reports_stale_source_with_a_dedicated_error_code(): void
    {
        $this->callTool('request_seo_optimization', [
            'page_id' => $this->page->id,
            'idempotency_key' => 'stale-http-request',
        ])->assertOk();
        $lease = $this->callTool('claim_seo_optimization')
            ->assertOk()
            ->json('result.structuredContent.task');
        $this->service->update(['content' => 'Nội dung CMS đã đổi sau khi nhận task.']);

        $response = $this->callTool('submit_seo_optimization', [
            'task_id' => $lease['task_id'],
            'lease_token' => $lease['lease_token'],
            'idempotency_key' => 'stale-http-submit',
            'payload' => $this->payload($lease),
        ])->assertOk();

        $this->assertTrue($response->json('result.isError'), $response->getContent());
        $this->assertStringContainsString('STALE_SOURCE:', $response->getContent());
        $this->assertDatabaseHas('seo_optimization_tasks', [
            'id' => $lease['task_id'],
            'status' => 'failed',
        ]);
        $this->assertDatabaseCount('seo_optimization_proposals', 0);
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
        $this->assertCount(4, $this->rpc('tools/list')->json('result.tools'));
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

    public function test_mcp_can_list_backup_and_request_restore_but_cannot_apply_it(): void
    {
        $applied = $this->workflow->apply($this->workflow->approve($this->proposal(), $this->reviewer), $this->reviewer);
        $backup = $applied->backup()->firstOrFail();

        $this->callTool('list_content_backups', ['page_id' => $this->page->id])
            ->assertOk()
            ->assertJsonPath('result.structuredContent.backups.0.id', $backup->id);
        $this->callTool('request_content_restore', ['backup_id' => $backup->id])
            ->assertOk()
            ->assertJsonPath('result.structuredContent.proposal.status', 'in_review')
            ->assertJsonPath('result.structuredContent.approval_mode', 'human_review_required');
        $this->assertSame($applied->patch['meta_description'], $this->service->fresh()->meta_description);
    }
}
