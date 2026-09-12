<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationBackup;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationOutbox;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
use App\Services\SeoOptimization\ContentPatchValidator;
use App\Services\SeoOptimization\Exceptions\StaleSourceException;
use App\Services\SeoOptimization\PageAuditService;
use App\Services\SeoOptimization\PageRegistryService;
use App\Support\LandingPageBlocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\LandingPage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OptimizationWorkflowTest extends OptimizationTestCase
{
    public function test_codex_only_proposes_and_human_approval_and_apply_are_separate_idempotent_operations(): void
    {
        $before = $this->service->meta_description;
        $proposal = $this->proposal();
        $task = $proposal->task()->firstOrFail();
        $expectedBaseline = app(PageAuditService::class)->evaluate($task->snapshot, $task->brief);
        $this->assertSame('in_review', $proposal->status);
        $this->assertSame($expectedBaseline['score'], data_get($proposal->qa, 'baseline_seo_gate.score'));
        $this->assertSame($expectedBaseline['grade'], data_get($proposal->qa, 'baseline_seo_gate.grade'));
        $this->assertSame($expectedBaseline['rule_version'], data_get($proposal->qa, 'baseline_seo_gate.rule_version'));
        $this->assertArrayHasKey('seo_gate', $proposal->qa);
        $this->assertSame($before, $this->service->fresh()->meta_description);
        $approved = $this->workflow->approve($proposal, $this->reviewer);
        $this->assertSame('approved', $approved->status);
        $this->assertSame($before, $this->service->fresh()->meta_description);
        $applied = $this->workflow->apply($approved, $this->reviewer);
        $this->assertSame('applied', $applied->status, json_encode($applied->qa));
        $this->assertSame($proposal->patch['meta_description'], $this->service->fresh()->meta_description);
        $this->assertFalse($applied->qa['verification']['public_http_verified']);
        $this->assertNotNull($applied->audit_id);
        $actualAudit = $applied->audit()->firstOrFail();
        $this->assertEquals($actualAudit->report['score'], $actualAudit->score);
        $this->workflow->apply($applied, $this->reviewer);
        $this->assertSame(1, SeoOptimizationEvent::where('event', 'proposal.applied')->count());
        $this->assertGreaterThan(0, SeoOptimizationOutbox::where('status', 'pending')->count());
        $verifiedAudit = $applied->audit()->firstOrFail();
        $this->assertNotNull($verifiedAudit->score);
        $this->assertSame(12, $verifiedAudit->report['assessed_dimensions']);
        $this->assertSame([], $verifiedAudit->report['missing_dimensions']);
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

    public function test_approved_proposal_cannot_apply_when_score_does_not_increase(): void
    {
        $lease = $this->lease();
        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $this->payload($lease, [
                'meta_description' => 'Tư vấn hành trình phù hợp với nhu cầu của từng khách hàng.',
            ]),
            $this->writer,
            $this->credential->id,
            'non-improving-manual-apply',
        );
        $approved = $this->workflow->approve($proposal, $this->reviewer);

        $this->assertLessThanOrEqual(
            data_get($approved->qa, 'baseline_seo_gate.score'),
            data_get($approved->qa, 'seo_gate.score'),
        );

        try {
            $this->workflow->apply($approved, $this->reviewer);
            $this->fail('A non-improving proposal must not be applied to CMS.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('score', $exception->errors());
            $this->assertSame('Tư vấn hành trình theo nhu cầu.', $this->service->fresh()->meta_description);
            $this->assertNull($approved->fresh()->applied_at);
            $this->assertDatabaseCount('seo_optimization_backups', 0);
            $this->assertDatabaseMissing('seo_optimization_events', [
                'proposal_id' => $approved->id,
                'event' => 'proposal.applied',
            ]);
        }
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

    public function test_human_approval_rebases_a_stale_proposal_and_overrides_the_current_content(): void
    {
        $proposal = $this->proposal();
        $originalSourceVersion = $proposal->source_version;
        $editorContent = 'Nội dung được biên tập sau khi Codex tạo đề xuất.';
        DB::table('services')->where('id', $this->service->id)->update(['meta_description' => $editorContent]);

        $approved = $this->workflow->approve($proposal, $this->reviewer);

        $this->assertSame('approved', $approved->status);
        $this->assertNotSame($originalSourceVersion, $approved->source_version);
        $this->assertSame($editorContent, $approved->before['meta_description']);
        $this->assertTrue((bool) data_get($approved->qa, 'human_override.source_changed'));
        $this->assertSame($this->reviewer->id, data_get($approved->qa, 'human_override.authorized_by'));
        $this->assertSame($editorContent, $this->service->fresh()->meta_description);

        $applied = $this->workflow->apply($approved, $this->reviewer);

        $this->assertSame('applied', $applied->status);
        $this->assertSame($proposal->patch['meta_description'], $this->service->fresh()->meta_description);
        $this->assertSame($editorContent, $applied->backup()->firstOrFail()->content_snapshot['meta_description']);
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

    public function test_stale_submit_closes_the_lease_and_records_a_machine_readable_failure(): void
    {
        $lease = $this->lease();
        DB::table('services')->where('id', $this->service->id)->update([
            'content' => 'Nội dung được biên tập sau khi Codex nhận task.',
        ]);

        try {
            $this->workflow->submit(
                $lease['task_id'],
                $lease['lease_token'],
                $this->payload($lease),
                $this->writer,
                $this->credential->id,
                'stale-source-submit',
            );
            $this->fail('Task có nguồn đã đổi phải trả STALE_SOURCE.');
        } catch (StaleSourceException $exception) {
            $this->assertStringContainsString('đã thay đổi', $exception->getMessage());
        }

        $task = SeoOptimizationTask::query()->findOrFail($lease['task_id']);
        $this->assertSame('failed', $task->status);
        $this->assertStringStartsWith('STALE_SOURCE:', $task->last_error);
        $this->assertNull($task->leased_by);
        $this->assertNull($task->leased_until);
        $this->assertNull($task->lease_token_hash);
        $this->assertNotNull($task->completed_at);
        $this->assertDatabaseHas('seo_optimization_events', [
            'page_id' => $this->page->id,
            'event' => 'task.stale_source',
        ]);
        $this->assertDatabaseCount('seo_optimization_proposals', 0);

        $replacement = $this->workflow->enqueue(
            $this->page->fresh(),
            $this->writer,
            'stale-source-replacement',
        );
        $this->assertSame('queued', $replacement->status);
    }

    public function test_wrong_expected_version_does_not_release_a_valid_lease(): void
    {
        $lease = $this->lease();
        $payload = $this->payload($lease);
        $payload['expected_version'] = str_repeat('0', 64);

        try {
            $this->workflow->submit(
                $lease['task_id'],
                $lease['lease_token'],
                $payload,
                $this->writer,
                $this->credential->id,
                'wrong-expected-version',
            );
            $this->fail('Sai expected_version phải bị từ chối.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('expected_version', $exception->errors());
        }

        $task = SeoOptimizationTask::query()->findOrFail($lease['task_id']);
        $this->assertSame('leased', $task->status);
        $this->assertNotNull($task->lease_token_hash);
        $this->assertNotNull($task->leased_until);
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

    public function test_existing_rendered_metrics_are_trusted_but_new_or_stronger_claims_need_data(): void
    {
        $snapshot = [
            'writable_fields' => ['meta_description'],
            'source_fields' => ['meta_description' => 'Hải Đăng Travel tổ chức hành trình theo nhu cầu.'],
            'text' => 'Hải Đăng Travel có 19 năm kinh nghiệm, 500+ sự kiện, 300+ đối tác và 98% hài lòng.',
        ];
        $validator = app(ContentPatchValidator::class);

        $retained = $validator->validate([
            'meta_description' => '19 năm kinh nghiệm, 500+ sự kiện, 300+ đối tác và 98% hài lòng tại Hải Đăng Travel.',
        ], $snapshot, []);

        $this->assertSame([], $retained['missing_facts']);
        $this->assertSame([], $retained['warnings']);

        $strengthened = $validator->validate([
            'meta_description' => 'Hải Đăng Travel dẫn đầu với 19 năm kinh nghiệm và 99% hài lòng.',
        ], $snapshot, []);

        $this->assertStringContainsString('99', implode(' ', $strengthened['missing_facts']));
        $this->assertStringContainsString('dẫn đầu', implode(' ', $strengthened['missing_facts']));
    }

    public function test_page_source_claim_is_trusted_and_proposal_continues_without_fact_warning(): void
    {
        $metrics = 'Hải Đăng Travel có 19 năm kinh nghiệm, 500+ sự kiện, 300+ đối tác và 98% hài lòng.';
        $this->service->update(['content' => '<h2>Năng lực</h2><p>'.$metrics.'</p>']);
        app(PageRegistryService::class)->sync();
        $lease = $this->lease();
        $payload = $this->payload($lease, [
            'meta_description' => 'Hải Đăng Travel: 19 năm kinh nghiệm, 500+ sự kiện, 300+ đối tác và 98% hài lòng.',
        ]);
        $payload['claims'] = [[
            'claim' => 'Các số liệu năng lực đang hiển thị trên trang.',
            'source_id' => 'page',
            'quote' => $metrics,
        ]];

        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $payload,
            $this->writer,
            $this->credential->id,
            'page-source-warning',
        );

        $this->assertSame('in_review', $proposal->status);
        $this->assertSame([], $proposal->missing_facts);
        $this->assertFalse((bool) data_get($proposal->qa, 'fact_review_required'));
        $this->assertSame([], $proposal->qa['warnings']);
    }

    public function test_existing_policy_sentence_does_not_need_an_external_document_even_with_a_different_source_id(): void
    {
        $policySentence = 'Phí hủy thay đổi theo thời điểm hủy và có thể lên đến 100% giá trị tour.';
        $this->service->update(['content' => '<h2>Điều kiện hủy</h2><p>'.$policySentence.'</p>']);
        app(PageRegistryService::class)->sync();
        $lease = $this->lease();
        $payload = $this->payload($lease, [
            'meta_description' => 'Tư vấn Đà Nẵng với thông tin hành trình và điều kiện hủy tour rõ ràng.',
        ]);
        $payload['claims'] = [[
            'claim' => $policySentence,
            'source_id' => 'tai-lieu-ngoai-khong-bat-buoc',
            'quote' => $policySentence,
        ]];

        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $payload,
            $this->writer,
            $this->credential->id,
            'existing-policy-sentence',
        );

        $this->assertSame('in_review', $proposal->status);
        $this->assertSame([], $proposal->missing_facts);
    }

    public function test_claims_and_missing_facts_can_be_omitted_when_only_existing_content_is_optimized(): void
    {
        $lease = $this->lease();
        $payload = $this->payload($lease);
        unset($payload['claims'], $payload['missing_facts']);

        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $payload,
            $this->writer,
            $this->credential->id,
            'omit-existing-fact-fields',
        );

        $this->assertSame('in_review', $proposal->status);
        $this->assertSame([], $proposal->claims);
        $this->assertSame([], $proposal->missing_facts);
    }

    public function test_human_verified_brief_source_allows_supported_business_metrics(): void
    {
        $verifiedQuote = 'Hải Đăng Travel có 19 năm kinh nghiệm, 500+ sự kiện, 300+ đối tác và 98% hài lòng.';
        $this->workflow->saveBrief($this->page, [
            'primary_keyword' => 'tư vấn Đà Nẵng',
            'search_intent' => 'LOCAL_SERVICE',
            'fact_sources' => [[
                'id' => 'company-profile-2026',
                'label' => 'Hồ sơ năng lực Hải Đăng Travel 2026',
                'quote' => $verifiedQuote,
            ]],
        ], $this->reviewer);
        $lease = $this->lease();
        $payload = $this->payload($lease, [
            'meta_description' => 'Hải Đăng Travel: 19 năm kinh nghiệm, 500+ sự kiện, 300+ đối tác và 98% hài lòng.',
        ]);
        $payload['claims'] = [[
            'claim' => 'Các số liệu kinh nghiệm, sự kiện, đối tác và mức độ hài lòng.',
            'source_id' => 'company-profile-2026',
            'quote' => $verifiedQuote,
        ]];

        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $payload,
            $this->writer,
            $this->credential->id,
            'verified-business-metrics',
        );

        $this->assertSame('in_review', $proposal->status);
        $this->assertSame([], $proposal->missing_facts);
        $this->assertSame([], $proposal->qa['warnings']);
        $this->assertNotNull(data_get($this->page->fresh()->keyword_brief, 'fact_sources.0.verified_by'));
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

    public function test_human_can_approve_a_stale_proposal_after_the_brief_changes(): void
    {
        $proposal = $this->proposal();
        $previousStrategyRevision = $proposal->strategy_revision;
        $this->workflow->saveBrief($this->page, ['primary_keyword' => 'hành trình Đà Nẵng', 'search_intent' => 'INFORMATIONAL'], $this->reviewer);

        $this->assertSame('stale', $proposal->fresh()->status);

        $approved = $this->workflow->approve($proposal->fresh(), $this->reviewer);

        $this->assertSame('approved', $approved->status);
        $this->assertNotSame($previousStrategyRevision, $approved->strategy_revision);
        $this->assertTrue((bool) data_get($approved->qa, 'human_override.strategy_changed'));
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

    public function test_slug_and_faq_apply_create_redirect_and_immutable_restorable_backup(): void
    {
        $oldPath = $this->page->path;
        $lease = $this->lease();
        $faq = [['question' => 'Nên chuẩn bị gì?', 'answer' => '<p>Hãy trao đổi nhu cầu hành trình với tư vấn viên.</p>']];
        $proposal = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease, [
            'slug' => 'tu-van-da-nang-moi',
            'faq_items' => $faq,
        ]), $this->writer, $this->credential->id, 'slug-faq-submit');
        $applied = $this->workflow->apply($this->workflow->approve($proposal, $this->reviewer), $this->reviewer);
        $backup = $applied->backup()->firstOrFail();

        $this->assertSame('applied', $applied->status);
        $this->assertInstanceOf(SeoOptimizationBackup::class, $backup);
        $this->assertSame('tu-van-da-nang-moi', $this->service->fresh()->slug);
        $this->assertSame($faq, $this->service->fresh()->faq_items);
        $this->assertDatabaseHas('seo_optimization_redirects', [
            'source_path' => $oldPath,
            'target_path' => '/dich-vu/tu-van-da-nang-moi',
            'status_code' => 301,
            'is_active' => true,
        ]);
        $this->get($oldPath)->assertRedirect('/dich-vu/tu-van-da-nang-moi')->assertStatus(301);

        try {
            $backup->update(['owner_id' => 'tampered']);
            $this->fail('Backup must be immutable.');
        } catch (\LogicException) {
            $backup = $backup->fresh();
            $this->assertSame((string) $this->service->id, $backup->owner_id);
        }

        $restore = $this->workflow->requestBackupRestore($backup, $this->writer);
        $this->assertSame('in_review', $restore->status);
        $this->assertSame('tu-van-da-nang', $restore->patch['slug']);
        $restored = $this->workflow->apply($this->workflow->approve($restore, $this->reviewer), $this->reviewer);
        $this->assertSame('applied', $restored->status, json_encode($restored->qa, JSON_UNESCAPED_UNICODE));
        $this->assertSame('tu-van-da-nang', $this->service->fresh()->slug);
        $this->assertSame([], $this->service->fresh()->faq_items ?? []);
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

    public function test_landing_block_patch_updates_only_targeted_content_and_restores_from_full_backup(): void
    {
        $rich = [
            'uuid' => 'rich-main',
            'type' => LandingPageBlocks::TYPE_RICH_TEXT,
            'is_enabled' => true,
            'body' => '<p>Nội dung cũ về hành trình.</p>',
        ];
        $html = [
            ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET),
            'uuid' => 'html-campaign',
            'html' => '<section class="campaign"><p>Widget cũ</p></section>',
        ];
        $faq = [
            ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FAQ),
            'uuid' => 'faq-main',
            'title' => 'FAQ cũ',
            'items' => [['question' => 'Câu hỏi cũ?', 'answer' => '<p>Câu trả lời cũ.</p>']],
        ];
        $tourList = [
            ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_LIST),
            'uuid' => 'tour-query',
            'title' => 'Tour đang mở',
            'category_slug' => 'tour-bien',
            'limit' => 4,
        ];
        $landing = LandingPage::query()->create([
            'title' => 'Landing mùa hè',
            'slug' => 'landing-mua-he',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'blocks' => [$rich, $html, $faq, $tourList],
            'home_config' => ['layout_order' => ['block:rich-main', 'block:html-campaign']],
            'is_active' => true,
        ]);
        foreach ([$this->writer, $this->reviewer] as $actor) {
            foreach (['admin.landing-pages.index', 'admin.landing-pages.edit'] as $permission) {
                $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $this->credential->update(['allowed_page_types' => ['landing']]);
        app(PageRegistryService::class)->sync();
        $this->page = SeoOptimizationPage::query()
            ->where('page_type', 'landing')->where('owner_id', (string) $landing->id)->firstOrFail();
        $this->workflow->saveBrief($this->page, [
            'primary_keyword' => 'landing mùa hè',
            'search_intent' => 'INFORMATIONAL',
            'required_topics' => ['tư vấn hành trình mùa hè'],
        ], $this->reviewer);
        $lease = $this->lease();
        $newFaq = [['question' => 'Nên chuẩn bị gì?', 'answer' => '<p>Chuẩn bị lịch trình phù hợp nhu cầu.</p>']];
        $patch = [
            'meta_title' => 'Landing mùa hè | Hải Đăng Travel',
            'meta_description' => 'Khám phá landing mùa hè với thông tin hành trình rõ ràng và nội dung hữu ích.',
            'block_changes' => [
            ['uuid' => 'rich-main', 'type' => 'rich_text', 'changes' => ['title' => 'Giới thiệu hành trình', 'body' => '<h2>Khám phá hành trình</h2><p>Tư vấn hành trình mùa hè với nội dung mới phù hợp nhu cầu.</p>']],
            ['uuid' => 'html-campaign', 'type' => 'html_widget', 'changes' => ['html' => '<section class="campaign"><div data-slot="copy"><p>Widget mới</p></div></section>']],
            ['uuid' => 'faq-main', 'type' => 'faq', 'changes' => ['title' => 'Câu hỏi thường gặp', 'items' => $newFaq]],
            ],
        ];
        $proposal = $this->workflow->submit(
            $lease['task_id'], $lease['lease_token'], $this->payload($lease, $patch),
            $this->writer, $this->credential->id, 'landing-block-submit',
        );
        $applied = $this->workflow->apply($this->workflow->approve($proposal, $this->reviewer), $this->reviewer);
        $landing->refresh();

        $this->assertSame('applied', $applied->status, json_encode($applied->qa, JSON_UNESCAPED_UNICODE));
        $this->assertSame('Giới thiệu hành trình', data_get($landing->blocks, '0.title'));
        $this->assertSame('<section class="campaign"><div data-slot="copy"><p>Widget mới</p></div></section>', data_get($landing->blocks, '1.html'));
        $this->assertSame('tour-bien', data_get($landing->blocks, '3.category_slug'));
        $this->assertSame(4, data_get($landing->blocks, '3.limit'));
        $this->assertSame(['block:rich-main', 'block:html-campaign'], data_get($landing->home_config, 'layout_order'));
        $this->assertSame('<h2>Khám phá hành trình</h2><p>Tư vấn hành trình mùa hè với nội dung mới phù hợp nhu cầu.</p>', $landing->body);
        $this->assertSame($newFaq, $landing->faq_items);
        $this->assertSame('<p>Nội dung cũ về hành trình.</p>', data_get($applied->backup?->content_snapshot, 'blocks.0.body'));

        $restore = $this->workflow->requestBackupRestore($applied->backup, $this->writer);
        $restored = $this->workflow->apply($this->workflow->approve($restore, $this->reviewer), $this->reviewer);
        $landing->refresh();
        $this->assertSame('applied', $restored->status, json_encode($restored->qa, JSON_UNESCAPED_UNICODE));
        $this->assertSame('', data_get($landing->blocks, '0.title'));
        $this->assertSame('<p>Nội dung cũ về hành trình.</p>', data_get($landing->blocks, '0.body'));
        $this->assertSame('<section class="campaign"><p>Widget cũ</p></section>', data_get($landing->blocks, '1.html'));
    }

    public function test_landing_manual_html_keeps_layout_markup_but_rejects_executable_html(): void
    {
        $landing = LandingPage::query()->create([
            'title' => 'Landing HTML',
            'slug' => 'landing-html-safe',
            'editor_mode' => LandingPage::EDITOR_MODE_HTML,
            'body' => '<section><p>Nội dung cũ</p></section>',
            'is_active' => true,
        ]);
        foreach ([$this->writer, $this->reviewer] as $actor) {
            foreach (['admin.landing-pages.index', 'admin.landing-pages.edit'] as $permission) {
                $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $this->credential->update(['allowed_page_types' => ['landing']]);
        app(PageRegistryService::class)->sync();
        $this->page = SeoOptimizationPage::query()
            ->where('page_type', 'landing')->where('owner_id', (string) $landing->id)->firstOrFail();
        $this->workflow->saveBrief($this->page, [
            'primary_keyword' => 'landing html',
            'search_intent' => 'INFORMATIONAL',
            'required_topics' => ['lịch trình landing html'],
        ], $this->reviewer);
        $lease = $this->lease();
        $safeHtml = '<section class="campaign"><div data-slot="copy"><h2>Hành trình phù hợp</h2><p>Lịch trình landing HTML với nội dung mới.</p></div></section>';
        $proposal = $this->workflow->submit(
            $lease['task_id'], $lease['lease_token'], $this->payload($lease, [
                'body' => $safeHtml,
                'meta_title' => 'Landing HTML | Hải Đăng Travel',
                'meta_description' => 'Landing HTML giới thiệu hành trình phù hợp với nội dung an toàn và dễ theo dõi.',
            ]),
            $this->writer, $this->credential->id, 'landing-html-submit',
        );
        $applied = $this->workflow->apply($this->workflow->approve($proposal, $this->reviewer), $this->reviewer);

        $this->assertSame('applied', $applied->status, json_encode($applied->qa, JSON_UNESCAPED_UNICODE));
        $this->assertSame($safeHtml, $landing->fresh()->body);

        app(PageRegistryService::class)->sync();
        $lease = $this->lease();
        $this->expectException(ValidationException::class);
        $this->workflow->submit(
            $lease['task_id'], $lease['lease_token'], $this->payload($lease, ['body' => '<section onclick="alert(1)">Không an toàn</section>']),
            $this->writer, $this->credential->id, 'landing-html-unsafe',
        );
    }

    public function test_landing_block_commit_rejects_a_targeted_editor_change_after_approval(): void
    {
        $landing = LandingPage::query()->create([
            'title' => 'Landing khóa block',
            'slug' => 'landing-khoa-block',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'blocks' => [[
                ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT),
                'uuid' => 'rich-lock',
                'title' => 'Giới thiệu',
                'body' => '<p>Nội dung ban đầu.</p>',
            ]],
            'is_active' => true,
        ]);
        foreach ([$this->writer, $this->reviewer] as $actor) {
            foreach (['admin.landing-pages.index', 'admin.landing-pages.edit'] as $permission) {
                $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $this->credential->update(['allowed_page_types' => ['landing']]);
        app(PageRegistryService::class)->sync();
        $this->page = SeoOptimizationPage::query()
            ->where('page_type', 'landing')->where('owner_id', (string) $landing->id)->firstOrFail();
        $this->workflow->saveBrief($this->page, ['primary_keyword' => 'landing khóa block', 'search_intent' => 'INFORMATIONAL'], $this->reviewer);
        $lease = $this->lease();
        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $this->payload($lease, ['block_changes' => [[
                'uuid' => 'rich-lock',
                'type' => LandingPageBlocks::TYPE_RICH_TEXT,
                'changes' => ['body' => '<p>Nội dung Codex đề xuất.</p>'],
            ]]]),
            $this->writer,
            $this->credential->id,
            'landing-block-lock-submit',
        );
        $approved = $this->workflow->approve($proposal, $this->reviewer);
        $blocks = $landing->fresh()->blocks;
        $blocks[0]['body'] = '<p>Nội dung biên tập viên vừa sửa.</p>';
        $landing->update(['blocks' => $blocks]);

        try {
            $this->workflow->apply($approved, $this->reviewer);
            $this->fail('Targeted block edit after approval must reject the proposal.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('source', $exception->errors());
            $this->assertSame('<p>Nội dung biên tập viên vừa sửa.</p>', data_get($landing->fresh()->blocks, '0.body'));
            $this->assertNull($approved->fresh()->applied_at);
        }
    }

    public function test_blog_category_commit_uses_plain_description_and_redirects_child_posts_after_slug_change(): void
    {
        $category = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Kinh nghiệm biển',
            'slug' => 'kinh-nghiem-bien',
            'description' => 'Nội dung cũ',
        ]);
        $post = BlogPost::query()->create([
            'title' => 'Cẩm nang Phú Quốc',
            'slug' => 'cam-nang-phu-quoc',
            'status' => 'published',
            'content_category_id' => $category->id,
            'content' => '<p>Kinh nghiệm chuẩn bị hành trình biển.</p>',
        ]);
        foreach ([$this->writer, $this->reviewer] as $actor) {
            foreach (['admin.blogs.categories.index', 'admin.blogs.categories.edit'] as $permission) {
                $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $this->credential->update(['allowed_page_types' => ['blog_category']]);
        app(PageRegistryService::class)->sync();
        $this->page = SeoOptimizationPage::query()
            ->where('page_type', 'blog_category')->where('owner_id', (string) $category->id)->firstOrFail();
        $postPage = SeoOptimizationPage::query()
            ->where('page_type', 'blog_post')->where('owner_id', (string) $post->id)->firstOrFail();
        $this->workflow->saveBrief($this->page, [
            'primary_keyword' => 'kinh nghiệm biển',
            'search_intent' => 'INFORMATIONAL',
            'required_topics' => ['lịch khởi hành linh hoạt'],
        ], $this->reviewer);
        $lease = $this->lease();
        $proposal = $this->workflow->submit(
            $lease['task_id'], $lease['lease_token'], $this->payload($lease, [
                'slug' => 'kinh-nghiem-du-lich-bien',
                'description' => '<p>Kinh nghiệm chuẩn bị hành trình biển phù hợp nhu cầu.</p>',
                'faq_items' => [[
                    'question' => 'Nên chuẩn bị lịch trình thế nào?',
                    'answer' => '<p>Nên tham khảo lịch khởi hành linh hoạt trước khi chọn hành trình.</p>',
                ]],
            ]),
            $this->writer, $this->credential->id, 'blog-category-submit',
        );
        $applied = $this->workflow->apply($this->workflow->approve($proposal, $this->reviewer), $this->reviewer);

        $this->assertSame('applied', $applied->status, json_encode($applied->qa, JSON_UNESCAPED_UNICODE));
        $this->assertSame('Kinh nghiệm chuẩn bị hành trình biển phù hợp nhu cầu.', $category->fresh()->description);
        $this->assertSame('/kinh-nghiem-du-lich-bien/cam-nang-phu-quoc', $postPage->fresh()->path);
        $this->assertDatabaseHas('seo_optimization_redirects', [
            'source_path' => '/kinh-nghiem-bien/cam-nang-phu-quoc',
            'target_path' => '/kinh-nghiem-du-lich-bien/cam-nang-phu-quoc',
            'status_code' => 301,
            'is_active' => true,
        ]);
        $this->get('/kinh-nghiem-bien/cam-nang-phu-quoc')
            ->assertStatus(301)
            ->assertRedirect('/kinh-nghiem-du-lich-bien/cam-nang-phu-quoc');
    }
}
