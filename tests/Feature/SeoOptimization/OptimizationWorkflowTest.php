<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationOutbox;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OptimizationWorkflowTest extends OptimizationTestCase
{
    public function test_codex_only_proposes_and_human_approval_and_apply_are_separate_idempotent_operations(): void
    {
        $before = $this->service->meta_description;
        $proposal = $this->proposal();
        $this->assertSame('in_review', $proposal->status);
        $this->assertSame($before, $this->service->fresh()->meta_description);
        $approved = $this->workflow->approve($proposal, $this->reviewer);
        $this->assertSame('approved', $approved->status);
        $this->assertSame($before, $this->service->fresh()->meta_description);
        $applied = $this->workflow->apply($approved, $this->reviewer);
        $this->assertSame('applied', $applied->status, json_encode($applied->qa));
        $this->assertSame($proposal->patch['meta_description'], $this->service->fresh()->meta_description);
        $this->assertFalse($applied->qa['verification']['public_http_verified']);
        $this->workflow->apply($applied, $this->reviewer);
        $this->assertSame(1, SeoOptimizationEvent::where('event', 'proposal.applied')->count());
        $this->assertGreaterThan(0, SeoOptimizationOutbox::where('status', 'pending')->count());
        $this->assertNull($applied->audit()->first()?->score);
    }

    public function test_cannot_self_approve(): void
    {
        $proposal = $this->proposal();
        $this->expectException(ValidationException::class);
        $this->workflow->approve($proposal, $this->writer);
    }

    public function test_cannot_apply_before_approval(): void
    {
        $proposal = $this->proposal();
        $this->expectException(ValidationException::class);
        $this->workflow->apply($proposal, $this->reviewer);
    }

    public function test_mcp_request_cannot_approve_even_with_reviewer_permissions(): void
    {
        $proposal = $this->proposal();
        request()->attributes->set('seo_optimization_credential', $this->credential);
        $this->expectException(HttpException::class);
        $this->workflow->approve($proposal, $this->reviewer);
    }

    public function test_approval_cannot_overwrite_a_new_edit_even_in_the_same_second(): void
    {
        $approved = $this->workflow->approve($this->proposal(), $this->reviewer);
        DB::table('services')->where('id', $this->service->id)->update(['meta_description' => 'Nội dung biên tập mới']);
        try {
            $this->workflow->apply($approved, $this->reviewer);
            $this->fail('Stale proposal must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('source', $exception->errors());
            $this->assertSame('Nội dung biên tập mới', $this->service->fresh()->meta_description);
            $this->assertNull($approved->fresh()->applied_at);
        }
    }

    public function test_idempotent_submission_returns_same_proposal_and_rejects_changed_payload(): void
    {
        $lease = $this->lease();
        $payload = $this->payload($lease);
        $first = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $payload, $this->writer, $this->credential->id, 'same-submit-key');
        $again = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $payload, $this->writer, $this->credential->id, 'same-submit-key');
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, SeoOptimizationProposal::count());
        $payload['notes'] = 'Changed request';
        $this->expectException(ValidationException::class);
        $this->workflow->submit($lease['task_id'], $lease['lease_token'], $payload, $this->writer, $this->credential->id, 'same-submit-key');
    }

    public function test_unknown_numbers_and_missing_sources_require_data_and_cannot_be_approved(): void
    {
        $lease = $this->lease();
        $proposal = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease, ['meta_description' => 'Tour giá 999.000 đồng, cam kết tốt nhất.']), $this->writer, $this->credential->id, 'new-facts-submit');
        $this->assertSame('need_data', $proposal->status);
        $this->assertNotEmpty($proposal->missing_facts);
        $this->expectException(ValidationException::class);
        $this->workflow->approve($proposal, $this->reviewer);
    }

    public function test_forbidden_commercial_fields_and_script_content_are_rejected(): void
    {
        $lease = $this->lease();
        foreach ([['status' => 'draft'], ['content' => '<script>alert(1)</script>'], ['content' => '<h1>Đổi H1</h1>'], ['content' => '<p onclick="alert(1)">Test</p>']] as $patch) {
            try {
                $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease, $patch), $this->writer, $this->credential->id, 'forbidden-submit');
                $this->fail('Forbidden patch must be rejected.');
            } catch (ValidationException) {
                $this->assertSame(0, SeoOptimizationProposal::count());
            }
        }
    }

    public function test_expired_lease_cannot_submit_and_can_be_reclaimed_without_duplicate_task(): void
    {
        $lease = $this->lease();
        SeoOptimizationTask::findOrFail($lease['task_id'])->update(['leased_until' => now()->subMinute()]);
        $newLease = $this->workflow->claim($this->writer, $this->credential->id);
        $this->assertSame($lease['task_id'], $newLease['task_id']);
        $this->assertNotSame($lease['lease_token'], $newLease['lease_token']);
        $this->expectException(ValidationException::class);
        $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease), $this->writer, $this->credential->id, 'expired-submit');
    }

    public function test_changing_brief_invalidates_approval(): void
    {
        $proposal = $this->workflow->approve($this->proposal(), $this->reviewer);
        $this->workflow->saveBrief($this->page, ['primary_keyword' => 'hành trình Đà Nẵng', 'search_intent' => 'INFORMATIONAL'], $this->reviewer);
        $this->assertSame('stale', $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->approved_at);
    }

    public function test_revoked_credentials_and_revoked_original_content_permissions_are_denied(): void
    {
        $this->credential->update(['revoked_at' => now()]);
        $this->expectException(ValidationException::class);
        $this->workflow->claim($this->writer, $this->credential->id);
    }

    public function test_tampered_approved_payload_is_not_applied(): void
    {
        $proposal = $this->workflow->approve($this->proposal(), $this->reviewer);
        $proposal->update(['patch' => ['meta_description' => 'Sửa sau duyệt']]);
        $this->expectException(ValidationException::class);
        $this->workflow->apply($proposal, $this->reviewer);
    }

    public function test_rollback_preserves_null_and_requires_a_new_independent_approval(): void
    {
        $this->service->update(['meta_description' => null]);
        $applied = $this->workflow->apply($this->workflow->approve($this->proposal(), $this->reviewer), $this->reviewer);
        $rollback = $this->workflow->rollbackProposal($applied, $this->reviewer);
        $this->assertSame('in_review', $rollback->status);
        $this->assertNull($rollback->patch['meta_description']);
        $this->assertNotNull($this->service->fresh()->meta_description);
        $this->workflow->apply($this->workflow->approve($rollback, $this->writer), $this->reviewer);
        $this->assertNull($this->service->fresh()->meta_description);
    }

    public function test_cancelled_lease_cannot_submit_and_can_be_requeued(): void
    {
        $lease = $this->lease();
        $task = SeoOptimizationTask::findOrFail($lease['task_id']);
        $this->workflow->cancelTask($task, $this->reviewer);
        $this->assertSame('cancelled', $task->fresh()->status);
        $this->assertSame('queued', $this->workflow->enqueue($this->page, $this->writer, 'new-after-cancel')->status);
        $this->expectException(ValidationException::class);
        $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease), $this->writer, $this->credential->id, 'cancelled-submit');
    }

    public function test_exhausted_expired_lease_becomes_failed_and_does_not_block_new_work(): void
    {
        $lease = $this->lease();
        $task = SeoOptimizationTask::findOrFail($lease['task_id']);
        $task->update(['attempts' => 3, 'leased_until' => now()->subMinute()]);
        $this->assertNull($this->workflow->claim($this->writer, $this->credential->id));
        $this->assertSame('failed', $task->fresh()->status);
        $this->assertSame('queued', $this->workflow->enqueue($this->page, $this->writer, 'new-after-exhausted')->status);
    }

    public function test_fabricated_source_quote_is_marked_need_data(): void
    {
        $lease = $this->lease();
        $payload = $this->payload($lease);
        $payload['claims'] = [['claim' => 'Dữ kiện chưa xác minh', 'source_id' => 'invented', 'quote' => 'Bịa nguồn']];
        $proposal = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $payload, $this->writer, $this->credential->id, 'fake-source-submit');
        $this->assertSame('need_data', $proposal->status);
    }
}
