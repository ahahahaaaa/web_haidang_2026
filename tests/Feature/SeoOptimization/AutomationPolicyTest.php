<?php

namespace Tests\Feature\SeoOptimization;

use App\Livewire\Admin\SeoOptimization\IntegrationSettings;
use App\Livewire\Admin\SeoOptimization\ProposalReview;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPolicy;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationRedirect;
use App\Services\SeoOptimization\OptimizationPolicyService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AutomationPolicyTest extends OptimizationTestCase
{
    public function test_default_policy_requires_preview_and_configuration_is_audited(): void
    {
        $this->assertNull(app(OptimizationPolicyService::class)->current());
        $policy = $this->policy('preview');
        $this->assertSame('preview', $policy->publish_mode);
        $this->assertSame(['service'], $policy->allowed_page_types);
        $this->assertTrue(SeoOptimizationEvent::query()->where('event', 'automation.policy_saved')->exists());
    }

    public function test_settings_ui_saves_preview_and_rejects_unknown_mode(): void
    {
        Livewire::actingAs($this->reviewer)->test(IntegrationSettings::class)
            ->set('allowedTypes', ['service'])->set('publishMode', 'preview')->call('savePolicy')->assertHasNoErrors();
        Livewire::actingAs($this->reviewer)->test(IntegrationSettings::class)
            ->set('publishMode', 'anything')->call('savePolicy')->assertHasErrors('mode');
    }

    public function test_mcp_cannot_change_policy_even_with_admin_permissions(): void
    {
        request()->attributes->set('seo_optimization_credential', $this->credential);
        try {
            $this->expectException(HttpException::class);
            $this->policy('always_publish');
        } finally {
            request()->attributes->remove('seo_optimization_credential');
        }
    }

    public function test_stale_settings_revision_cannot_overwrite_new_configuration(): void
    {
        $policy = $this->policy('preview');
        app(OptimizationPolicyService::class)->save($this->reviewer, 'always_publish', ['service'], $policy->revision);
        $this->expectException(HttpException::class);
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], $policy->revision);
    }

    public function test_policy_automatically_applies_without_fabricating_human_approval(): void
    {
        $policy = $this->policy('always_publish');
        $proposal = $this->automatedProposal($policy);
        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
        $this->assertSame('applied', $result->status, json_encode($result->qa, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->assertNull($result->approved_by);
        $this->assertNull($result->approved_at);
        $this->assertSame('server_policy', $result->qa['authorization']['kind']);
        $this->assertSame($this->reviewer->id, $result->applied_by);
        $this->assertSame($result->patch['meta_description'], $this->service->fresh()->meta_description);
        $this->workflow->applyConfigured($result, $this->writer, $this->credential->id);
        $this->assertSame(1, SeoOptimizationEvent::query()->where('proposal_id', $result->id)->where('event', 'proposal.applied')->count());
    }

    public function test_proposal_review_displays_policy_configurator_name_instead_of_id(): void
    {
        $policy = $this->policy('always_publish');
        $proposal = $this->automatedProposal($policy);
        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);

        Livewire::actingAs($this->reviewer)
            ->test(ProposalReview::class, ['proposal' => $result])
            ->assertSee('Cấu hình bởi tài khoản '.$this->reviewer->name.'.')
            ->assertDontSee('Cấu hình bởi tài khoản '.$this->reviewer->id.'.');
    }

    public function test_proposal_review_handles_a_deleted_policy_configurator(): void
    {
        $policy = $this->policy('always_publish');
        $proposal = $this->automatedProposal($policy);
        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
        $configuredById = $this->reviewer->id;

        $this->reviewer->delete();

        Livewire::actingAs($this->writer)
            ->test(ProposalReview::class, ['proposal' => $result])
            ->assertSee('Cấu hình bởi tài khoản không còn tồn tại.');
        $this->assertSame($configuredById, data_get($result->fresh()->qa, 'authorization.configured_by'));
    }

    public function test_always_publish_applies_slug_without_human_approval_and_creates_redirect(): void
    {
        $policy = $this->policy('always_publish');
        $proposal = $this->automatedProposal($policy, ['slug' => 'tu-van-da-nang-hai-dang']);

        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);

        $this->assertSame('applied', $result->status, json_encode($result->qa, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->assertNull($result->approved_by);
        $this->assertNull($result->approved_at);
        $this->assertSame('tu-van-da-nang-hai-dang', $this->service->fresh()->slug);
        $this->assertTrue(SeoOptimizationRedirect::query()
            ->where('proposal_id', $result->id)
            ->where('source_path', '/dich-vu/tu-van-da-nang')
            ->where('target_path', '/dich-vu/tu-van-da-nang-hai-dang')
            ->where('status_code', 301)
            ->where('is_active', true)
            ->exists());
    }

    public function test_preview_never_changes_public_content(): void
    {
        $proposal = $this->automatedProposal($this->policy('preview'));
        $before = $this->service->fresh()->meta_description;
        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
        $this->assertSame('in_review', $result->status);
        $this->assertNull($result->applied_at);
        $this->assertSame($before, $this->service->fresh()->meta_description);
    }

    public function test_always_publish_keeps_a_non_improving_change_in_preview(): void
    {
        $policy = $this->policy('always_publish');
        $this->credential->update(['abilities' => ['read', 'audit', 'propose', 'automate']]);
        $task = $this->workflow->enqueue($this->page, $this->writer, 'non-improving-'.str()->uuid(), [
            'mode' => 'direct_cms',
            'policy_revision' => $policy->revision,
            'source_revision' => $this->page->source_version,
            'image_required' => false,
        ]);
        $lease = $this->workflow->claim($this->writer, $this->credential->id, $task->id);
        $proposal = $this->workflow->submit(
            $task->id,
            $lease['lease_token'],
            $this->payload($lease, ['meta_title' => 'Dịch vụ du lịch tổng hợp']),
            $this->writer,
            $this->credential->id,
            'non-improving-submit',
        );

        $this->assertLessThanOrEqual(
            data_get($proposal->qa, 'baseline_seo_gate.score'),
            data_get($proposal->qa, 'seo_gate.score'),
        );
        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);

        $this->assertSame('in_review', $result->status);
        $this->assertNull($result->applied_at);
        $this->assertSame('preview_score_not_improved', data_get($result->qa, 'publish_decision'));
    }

    public function test_changed_policy_always_forces_existing_task_to_preview(): void
    {
        $policy = $this->policy('always_publish');
        $proposal = $this->automatedProposal($policy);
        app(OptimizationPolicyService::class)->save($this->reviewer, 'always_publish', ['service'], $policy->revision);
        $result = $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
        $this->assertNull($result->applied_at);
        $this->assertSame('in_review', $result->status);
    }

    public function test_source_conflict_blocks_auto_publish(): void
    {
        $proposal = $this->automatedProposal($this->policy('always_publish'));
        $this->service->update(['meta_description' => 'Nội dung biên tập mới.']);
        try {
            $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
            $this->fail('Expected source conflict');
        } catch (ValidationException) {
            $this->assertNull($proposal->fresh()->applied_at);
            $this->assertSame('Nội dung biên tập mới.', $this->service->fresh()->meta_description);
        }
    }

    public function test_revoked_configuration_owner_permission_blocks_publication(): void
    {
        $proposal = $this->automatedProposal($this->policy('always_publish'));
        $this->reviewer->revokePermissionTo('admin.seo-optimization.apply');
        $this->expectException(HttpException::class);
        $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
    }

    public function test_ordinary_token_cannot_signal_auto_publish(): void
    {
        $proposal = $this->automatedProposal($this->policy('always_publish'));
        $this->credential->update(['abilities' => ['read', 'audit', 'propose']]);
        $this->expectException(ValidationException::class);
        $this->workflow->applyConfigured($proposal, $this->writer, $this->credential->id);
    }

    private function policy(string $mode): SeoOptimizationPolicy
    {
        return app(OptimizationPolicyService::class)->save($this->reviewer, $mode, ['service'], null);
    }

    private function automatedProposal(SeoOptimizationPolicy $policy, array $extraPatch = []): SeoOptimizationProposal
    {
        $this->credential->update(['abilities' => ['read', 'audit', 'propose', 'automate']]);
        $this->workflow->saveBrief($this->page, [
            'primary_keyword' => 'tư vấn Đà Nẵng',
            'search_intent' => 'LOCAL_SERVICE',
            'entities' => ['Hải Đăng Travel'],
            'required_topics' => ['phạm vi dịch vụ', 'quy trình', 'chi phí', 'câu hỏi thường gặp'],
        ], $this->reviewer);
        $task = $this->workflow->enqueue($this->page->fresh(), $this->writer, 'policy-task-'.str()->uuid(), [
            'mode' => 'direct_cms',
            'policy_revision' => $policy->revision,
            'source_revision' => $this->page->fresh()->source_version,
            'image_required' => false,
        ]);
        $lease = $this->workflow->claim($this->writer, $this->credential->id, $task->id);
        $paragraph = 'Tư vấn Đà Nẵng giúp khách xác định hành trình phù hợp nhu cầu và trải nghiệm mong muốn cùng Hải Đăng Travel. ';
        $content = '<h2>Phạm vi dịch vụ tư vấn Đà Nẵng</h2><p>'.str_repeat($paragraph, 18).'</p>'
            .'<h2>Quy trình tư vấn hành trình</h2><p>'.str_repeat($paragraph, 16).'</p>'
            .'<h2>Chi phí và lựa chọn phù hợp</h2><p>'.str_repeat($paragraph, 14).'</p>'
            .'<h2>Câu hỏi thường gặp</h2><p>'.str_repeat($paragraph, 10).'</p>'
            .'<p><a href="/dich-vu">Xem dịch vụ du lịch</a> và <a href="/lien-he">liên hệ tư vấn</a>.</p>';
        $proposal = $this->workflow->submit($task->id, $lease['lease_token'], $this->payload($lease, array_merge([
            'meta_title' => 'Tư vấn Đà Nẵng | Hải Đăng Travel',
            'meta_description' => 'Tư vấn Đà Nẵng theo nhu cầu cùng Hải Đăng Travel, gồm phạm vi dịch vụ, quy trình, chi phí và lưu ý.',
            'content' => $content,
        ], $extraPatch)), $this->writer, $this->credential->id, 'policy-submit-'.str()->uuid());

        return $proposal;
    }
}
