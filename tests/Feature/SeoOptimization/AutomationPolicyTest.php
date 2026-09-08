<?php

namespace Tests\Feature\SeoOptimization;

use App\Livewire\Admin\SeoOptimization\IntegrationSettings;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPolicy;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
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
        $this->assertSame('applied', $result->status);
        $this->assertNull($result->approved_by);
        $this->assertNull($result->approved_at);
        $this->assertSame('server_policy', $result->qa['authorization']['kind']);
        $this->assertSame($this->reviewer->id, $result->applied_by);
        $this->assertSame($result->patch['meta_description'], $this->service->fresh()->meta_description);
        $this->workflow->applyConfigured($result, $this->writer, $this->credential->id);
        $this->assertSame(1, SeoOptimizationEvent::query()->where('proposal_id', $result->id)->where('event', 'proposal.applied')->count());
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

    private function automatedProposal(SeoOptimizationPolicy $policy): SeoOptimizationProposal
    {
        $this->credential->update(['abilities' => ['read', 'audit', 'propose', 'automate']]);
        $proposal = $this->proposal();
        SeoOptimizationTask::query()->findOrFail($proposal->task_id)->update(['automation' => ['policy_revision' => $policy->revision]]);

        return $proposal;
    }
}
