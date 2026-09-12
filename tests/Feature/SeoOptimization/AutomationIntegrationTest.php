<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationAsset;
use App\Models\SeoOptimizationAudit;
use App\Models\SeoOptimizationBackup;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationTask;
use App\Services\SeoOptimization\KeywordBriefResolver;
use App\Services\SeoOptimization\OptimizationAutomationService;
use App\Services\SeoOptimization\OptimizationBrief;
use App\Services\SeoOptimization\OptimizationMediaService;
use App\Services\SeoOptimization\OptimizationPolicyService;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\PublicImageDownloader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Service;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AutomationIntegrationTest extends OptimizationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media-library.disk_name' => 'public', 'filesystems.disks.public.visibility' => 'public']);
        $this->writer->givePermissionTo(Permission::findOrCreate('admin.media.index', 'web'));
        $this->credential->update(['abilities' => ['read', 'audit', 'propose', 'automate']]);
    }

    public function test_direct_cms_proposal_and_preview_do_not_change_public_content(): void
    {
        [$proposal, $asset] = $this->preparedProposal('preview');
        $result = app(OptimizationAutomationService::class)->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);

        $this->assertSame('in_review', $result['status']);
        $this->assertFalse($result['public_content_changed']);
        $this->assertSame('synced', $result['sync_status']);
        $this->assertStringNotContainsString($asset['url'], $this->service->fresh()->content);
    }

    public function test_claim_next_prioritizes_the_admin_queue_even_without_an_auto_policy(): void
    {
        $task = $this->workflow->enqueue($this->page, $this->reviewer, 'admin-priority-'.str()->uuid());
        $automation = app(OptimizationAutomationService::class);

        $lease = $automation->claimNext($this->writer, $this->credential->id, true);

        $this->assertSame($task->id, $lease['task_id']);
        $this->assertSame('admin_queue', $lease['queue_source']);
        $this->assertSame('human_review_required', $lease['approval_mode']);
        $this->assertArrayNotHasKey('completion_url', $lease);
        $this->assertStringContainsString('không gọi commit_content_optimization', $lease['instructions']);
        $this->assertSame($this->credential->id, $task->fresh()->leased_by);
        $this->assertDatabaseCount('seo_optimization_tasks', 1);

        $proposal = $this->workflow->submit(
            $task->id,
            $lease['lease_token'],
            $this->payload($lease),
            $this->writer,
            $this->credential->id,
            'admin-priority-submit',
        );

        $this->assertSame('in_review', $proposal->status);
        $this->assertSame('Tư vấn hành trình theo nhu cầu.', $this->service->fresh()->meta_description);
        $this->assertDatabaseCount('seo_optimization_backups', 0);

        try {
            $automation->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
            $this->fail('Task từ hàng chờ admin không được tự commit.');
        } catch (ValidationException) {
            $this->assertSame('in_review', $proposal->fresh()->status);
            $this->assertSame('Tư vấn hành trình theo nhu cầu.', $this->service->fresh()->meta_description);
        }
    }

    public function test_claim_skips_a_current_admin_task_above_80_and_continues_with_the_next_task(): void
    {
        $highScoreTask = $this->workflow->enqueue($this->page, $this->reviewer, 'high-score-'.str()->uuid());
        SeoOptimizationAudit::query()->create([
            'page_id' => $this->page->id,
            'actor_id' => $this->reviewer->id,
            'source_version' => $highScoreTask->source_version,
            'strategy_revision' => $highScoreTask->strategy_revision,
            'rule_version' => config('seo_optimization.rule_version'),
            'status' => 'improve',
            'score' => 80.1,
            'grade' => 'B',
            'snapshot' => $highScoreTask->snapshot,
            'report' => ['score' => 80.1, 'grade' => 'B'],
        ]);

        $nextService = Service::query()->create([
            'title' => 'Tư vấn hành trình Nha Trang',
            'slug' => 'tu-van-nha-trang',
            'status' => 'published',
            'meta_description' => 'Tư vấn hành trình Nha Trang.',
            'content' => '<h2>Chuẩn bị hành trình</h2><p>Trao đổi nhu cầu với tư vấn viên.</p>',
        ]);
        app(PageRegistryService::class)->sync();
        $nextPage = SeoOptimizationPage::query()
            ->where('owner_type', 'service')
            ->where('owner_id', (string) $nextService->getKey())
            ->firstOrFail();
        $this->workflow->saveBrief($nextPage, [
            'primary_keyword' => 'tư vấn Nha Trang',
            'search_intent' => 'LOCAL_SERVICE',
        ], $this->reviewer);
        $nextTask = $this->workflow->enqueue($nextPage, $this->reviewer, 'next-score-'.str()->uuid());

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id, true);

        $this->assertSame($nextTask->id, $lease['task_id']);
        $this->assertSame('skipped', $highScoreTask->fresh()->status);
        $this->assertNotNull($highScoreTask->fresh()->completed_at);
        $this->assertDatabaseHas('seo_optimization_events', [
            'page_id' => $this->page->id,
            'event' => 'task.score_threshold_skipped',
        ]);
    }

    public function test_claim_keeps_a_task_with_score_exactly_80(): void
    {
        $task = $this->workflow->enqueue($this->page, $this->reviewer, 'score-80-'.str()->uuid());
        SeoOptimizationAudit::query()->create([
            'page_id' => $this->page->id,
            'actor_id' => $this->reviewer->id,
            'source_version' => $task->source_version,
            'strategy_revision' => $task->strategy_revision,
            'rule_version' => config('seo_optimization.rule_version'),
            'status' => 'improve',
            'score' => 80,
            'grade' => 'B',
            'snapshot' => $task->snapshot,
            'report' => ['score' => 80, 'grade' => 'B'],
        ]);

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id, true);

        $this->assertSame($task->id, $lease['task_id']);
        $this->assertSame('leased', $task->fresh()->status);
    }

    public function test_direct_selection_skips_a_current_page_above_80_without_creating_a_task(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        app(PageRegistryService::class)->sync();
        $this->page->refresh();
        $brief = app(KeywordBriefResolver::class)->resolve($this->page, $this->writer);
        SeoOptimizationAudit::query()->create([
            'page_id' => $this->page->id,
            'actor_id' => $this->reviewer->id,
            'source_version' => app(PageRegistryService::class)->currentVersion($this->page),
            'strategy_revision' => app(OptimizationBrief::class)->revision($brief),
            'rule_version' => config('seo_optimization.rule_version'),
            'status' => 'improve',
            'score' => 81,
            'grade' => 'B',
            'snapshot' => [],
            'report' => ['score' => 81, 'grade' => 'B'],
        ]);

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);

        $this->assertNull($lease);
        $this->assertDatabaseCount('seo_optimization_tasks', 0);
        $this->assertDatabaseHas('seo_optimization_events', [
            'page_id' => $this->page->id,
            'event' => 'automation.score_threshold_skipped',
        ]);
    }

    public function test_admin_queue_only_returns_null_without_creating_an_automatic_task(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);

        $lease = app(OptimizationAutomationService::class)->claimNext(
            $this->writer,
            $this->credential->id,
            true,
        );

        $this->assertNull($lease);
        $this->assertDatabaseCount('seo_optimization_tasks', 0);
    }

    public function test_claim_repairs_a_legacy_admin_task_with_missing_audit_criteria(): void
    {
        $task = $this->workflow->enqueue($this->page, $this->reviewer, 'legacy-incomplete-'.str()->uuid());
        $legacyBrief = $task->brief;
        $legacyBrief['entities'] = [];
        $legacyBrief['required_topics'] = [];
        $legacyBrief['revision'] = app(OptimizationBrief::class)->revision($legacyBrief);
        $this->page->update(['keyword_brief' => $legacyBrief]);
        $task->update([
            'brief' => $legacyBrief,
            'strategy_revision' => $legacyBrief['revision'],
        ]);

        $lease = app(OptimizationAutomationService::class)->claimNext(
            $this->writer,
            $this->credential->id,
            true,
        );

        $this->assertSame($task->id, $lease['task_id']);
        $this->assertSame('admin_queue', $lease['queue_source']);
        $this->assertNotEmpty($lease['brief']['entities']);
        $this->assertNotEmpty($lease['brief']['required_topics']);
        $this->assertSame($this->page->fresh()->keyword_brief, $task->fresh()->brief);
        $this->assertSame($this->page->fresh()->keyword_brief['revision'], $task->fresh()->strategy_revision);
        $this->assertDatabaseHas('seo_optimization_events', [
            'page_id' => $this->page->id,
            'event' => 'brief.automation_completed',
        ]);
    }

    public function test_admin_queue_is_claimed_before_an_existing_direct_automation_task(): void
    {
        $policy = app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $otherService = Service::query()->create([
            'title' => 'Dịch vụ tư vấn Phú Quốc',
            'slug' => 'tu-van-phu-quoc',
            'status' => 'published',
            'meta_description' => 'Tư vấn hành trình Phú Quốc.',
            'content' => '<h2>Chuẩn bị hành trình</h2><p>Trao đổi nhu cầu với tư vấn viên.</p>',
        ]);
        app(PageRegistryService::class)->sync();
        $otherPage = SeoOptimizationPage::query()
            ->where('owner_type', 'service')
            ->where('owner_id', (string) $otherService->getKey())
            ->firstOrFail();
        $this->workflow->saveBrief($otherPage, [
            'primary_keyword' => 'tư vấn Phú Quốc',
            'search_intent' => 'LOCAL_SERVICE',
        ], $this->reviewer);
        $directTask = $this->workflow->enqueue($otherPage, $this->writer, 'direct-before-admin-'.str()->uuid(), [
            'mode' => 'direct_cms',
            'policy_revision' => $policy->revision,
            'source_revision' => $otherPage->source_version,
            'image_required' => false,
        ]);
        $adminTask = $this->workflow->enqueue($this->page, $this->reviewer, 'admin-after-direct-'.str()->uuid());

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);

        $this->assertSame($adminTask->id, $lease['task_id']);
        $this->assertSame('admin_queue', $lease['queue_source']);
        $this->assertSame('queued', $directTask->fresh()->status);
    }

    public function test_direct_claim_skips_a_page_with_same_primary_keyword_and_intent(): void
    {
        $brief = $this->page->fresh()->keyword_brief;
        $conflictingService = Service::query()->create([
            'title' => 'Dịch vụ có từ khóa xung đột',
            'slug' => 'dich-vu-co-tu-khoa-xung-dot',
            'status' => 'published',
            'content' => '<h2>Dịch vụ</h2><p>Nội dung kiểm thử.</p>',
        ]);
        app(PageRegistryService::class)->sync();
        SeoOptimizationPage::query()
            ->where('owner_type', 'service')
            ->where('owner_id', (string) $conflictingService->getKey())
            ->firstOrFail()
            ->update(['keyword_brief' => $brief]);
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);

        $this->assertNull($lease);
        $this->assertDatabaseHas('seo_optimization_events', [
            'page_id' => $this->page->id,
            'event' => 'automation.keyword_conflict_skipped',
        ]);
    }

    public function test_http_automation_tools_and_commit_follow_server_policy(): void
    {
        $response = $this->withToken($this->testToken)->postJson('/mcp/seo-optimization', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => (object) [],
        ])->assertOk();
        $this->assertCount(13, $response->json('result.tools'));
        $names = array_column($response->json('result.tools'), 'name');
        $this->assertContains('claim_next_content_optimization', $names);
        $this->assertContains('commit_content_optimization', $names);
        $this->assertContains('list_content_backups', $names);
        $this->assertContains('request_content_restore', $names);
        $claim = collect($response->json('result.tools'))->firstWhere('name', 'claim_next_content_optimization');
        $this->assertSame('boolean', data_get($claim, 'inputSchema.properties.admin_queue_only.type'));
        $this->assertFalse(data_get($claim, 'inputSchema.properties.admin_queue_only.default'));

        [$proposal] = $this->preparedProposal('always_publish', strong: true);
        $this->withToken($this->testToken)->postJson('/mcp/seo-optimization/commit', [
            'proposal_id' => $proposal->id, 'content_hash' => str_repeat('0', 64),
        ])->assertUnprocessable();
        $this->assertNull($proposal->fresh()->applied_at);
        $this->withToken($this->testToken)->postJson('/mcp/seo-optimization/commit', [
            'proposal_id' => $proposal->id, 'content_hash' => $proposal->content_hash,
        ])->assertOk()->assertJsonPath('public_content_changed', true)->assertJsonPath('status', 'applied');
        $this->assertDatabaseCount('seo_optimization_backups', 1);
    }

    public function test_always_publish_applies_any_score_increase_without_absolute_gate(): void
    {
        $policy = app(OptimizationPolicyService::class)->save($this->reviewer, 'always_publish', ['service'], null);
        $this->workflow->saveBrief($this->page, [
            'primary_keyword' => 'tư vấn Đà Nẵng',
            'search_intent' => 'LOCAL_SERVICE',
            'entities' => ['Hải Đăng Travel'],
            'required_topics' => ['chuẩn bị hành trình'],
        ], $this->reviewer);
        $task = $this->workflow->enqueue($this->page, $this->writer, 'incremental-publish-'.str()->uuid(), [
            'mode' => 'direct_cms',
            'policy_revision' => $policy->revision,
            'source_revision' => $this->page->source_version,
            'image_required' => false,
        ]);
        $lease = $this->workflow->claim($this->writer, $this->credential->id, $task->id);
        $paragraph = 'Tư vấn Đà Nẵng giúp khách chuẩn bị hành trình phù hợp nhu cầu cùng Hải Đăng Travel. ';
        $content = '<h2>Chuẩn bị hành trình</h2><p>'.str_repeat($paragraph, 8).'</p>'
            .'<h2>Quy trình tư vấn</h2><p>'.str_repeat($paragraph, 8).'</p>'
            .'<p><a href="/dich-vu">Xem dịch vụ</a> và <a href="/lien-he">liên hệ tư vấn</a>.</p>';
        $proposal = $this->workflow->submit(
            $task->id,
            $lease['lease_token'],
            $this->payload($lease, [
                'meta_description' => 'Tư vấn Đà Nẵng theo nhu cầu, giúp khách chuẩn bị hành trình phù hợp cùng Hải Đăng Travel.',
                'content' => $content,
            ]),
            $this->writer,
            $this->credential->id,
            'incremental-publish-submit',
        );
        $beforeScore = data_get($proposal->qa, 'baseline_seo_gate.score');
        $afterScore = data_get($proposal->qa, 'seo_gate.score');

        $this->assertGreaterThan($beforeScore, $afterScore);
        $this->assertLessThan(90, $afterScore);
        $result = app(OptimizationAutomationService::class)->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);

        $this->assertTrue($result['public_content_changed']);
        $this->assertSame('applied', $result['status']);
        $this->assertSame($beforeScore, $result['seo_score_before']);
        $this->assertSame($afterScore, $result['seo_score']);
        $this->assertGreaterThan(0, $result['seo_score_delta']);
        $this->assertSame('published_score_improved', data_get($proposal->fresh()->qa, 'publish_decision'));
        $this->assertDatabaseCount('seo_optimization_backups', 1);
    }

    public function test_auto_publish_retry_applies_and_creates_backup_only_once(): void
    {
        [$proposal, $asset] = $this->preparedProposal('always_publish', strong: true);
        $automation = app(OptimizationAutomationService::class);
        $first = $automation->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
        $retry = $automation->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);

        $this->assertTrue($first['public_content_changed']);
        $this->assertSame($first['backup_id'], $retry['backup_id']);
        $this->assertStringContainsString($asset['url'], $this->service->fresh()->content);
        $this->assertSame(1, SeoOptimizationBackup::count());
        $this->assertSame(1, SeoOptimizationEvent::query()->where('proposal_id', $proposal->id)->where('event', 'proposal.applied')->count());
    }

    public function test_non_blocking_warning_still_allows_always_publish_policy_to_process(): void
    {
        [$proposal] = $this->preparedProposal(
            'always_publish',
            strong: true,
            warnings: ['Dữ kiện hiện hữu cần đối chiếu thêm nhưng không chặn tối ưu.'],
        );

        $result = app(OptimizationAutomationService::class)->complete(
            $proposal->id,
            $proposal->content_hash,
            $this->writer,
            $this->credential->id,
        );

        $this->assertSame('applied', $result['status']);
        $this->assertTrue($result['public_content_changed']);
        $this->assertContains('Dữ kiện hiện hữu cần đối chiếu thêm nhưng không chặn tối ưu.', $result['warnings']);
        $this->assertContains('Dữ kiện hiện hữu cần đối chiếu thêm nhưng không chặn tối ưu.', $proposal->fresh()->qa['warnings']);
    }

    public function test_generated_image_upload_requires_media_permission(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $media = app(OptimizationMediaService::class);
        $media->upload($lease['task_id'], $lease['lease_token'], UploadedFile::fake()->image('illustration.png', 64, 64), 'Ảnh minh họa hành trình', 'Minh họa hành trình du lịch', $this->writer, $this->credential->id);
        $this->writer->revokePermissionTo('admin.media.index');

        $this->expectException(HttpException::class);
        $media->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);
    }

    public function test_fake_image_is_rejected_without_creating_asset(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        try {
            app(OptimizationMediaService::class)->upload($lease['task_id'], $lease['lease_token'], UploadedFile::fake()->createWithContent('fake.png', '<script>bad</script>'), 'Ảnh minh họa', 'Minh họa hành trình', $this->writer, $this->credential->id);
            $this->fail('Invalid image must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('seo_optimization_assets', 0);
        }
    }

    public function test_generated_image_upload_is_normalized_to_webp_before_media_storage(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);

        $ready = app(OptimizationMediaService::class)->upload(
            $lease['task_id'],
            $lease['lease_token'],
            UploadedFile::fake()->image('illustration.png', 64, 48),
            'Ảnh minh họa hành trình',
            'Minh họa hành trình du lịch',
            $this->writer,
            $this->credential->id,
        );

        $stored = Media::query()->findOrFail($ready['asset']['media_id']);
        $dimensions = getimagesize($stored->getPath());

        $this->assertSame('image/webp', $stored->mime_type);
        $this->assertStringEndsWith('.webp', $stored->file_name);
        $this->assertSame('image/webp', $ready['asset']['mime_type']);
        $this->assertSame('image/webp', $dimensions['mime']);
        $this->assertSame(64, $dimensions[0]);
        $this->assertSame(48, $dimensions[1]);
        $this->assertSame('image/webp', $stored->getCustomProperty('seo_optimization.stored_format'));
    }

    public function test_same_domain_image_reuses_existing_owner_media(): void
    {
        config(['filesystems.disks.public.url' => 'https://haidangtravel.test/storage']);
        $original = $this->service->addMedia(UploadedFile::fake()->image('existing.png', 64, 64))->toMediaCollection('cover', 'public');
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $ready = app(OptimizationMediaService::class)->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);

        $this->assertSame('same_site', $ready['asset']['source_type']);
        $this->assertSame($original->id, $ready['asset']['media_id']);
        $this->assertDatabaseCount('media', 1);
    }

    public function test_existing_owner_media_makes_image_optional_without_manifest_reinsertion(): void
    {
        config(['filesystems.disks.public.url' => 'https://haidangtravel.test/storage']);
        $original = $this->service
            ->addMedia(UploadedFile::fake()->image('existing-cover.png', 64, 64))
            ->toMediaCollection('cover', 'public');
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);

        $this->assertFalse($lease['automation']['image_required']);
        $this->assertSame('existing_media_reused', $lease['automation']['image_handling']);
        $this->assertSame('https://haidangtravel.test/storage/'.$original->id.'/existing-cover.png', $lease['automation']['image_url']);

        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $this->payload($lease),
            $this->writer,
            $this->credential->id,
            'existing-media-submit',
        );
        $result = app(OptimizationAutomationService::class)->complete(
            $proposal->id,
            $proposal->content_hash,
            $this->writer,
            $this->credential->id,
        );

        $this->assertSame('in_review', $result['status']);
        $this->assertFalse($result['public_content_changed']);
        $this->assertDatabaseCount('seo_optimization_assets', 0);
        $this->assertDatabaseCount('media', 1);
    }

    public function test_page_without_rich_text_target_is_not_blocked_by_image_requirement(): void
    {
        $this->writer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.index', 'web'));
        $this->writer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.edit', 'web'));
        $this->reviewer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.edit', 'web'));
        $this->credential->update(['allowed_page_types' => ['home']]);
        LandingPage::query()->create([
            'page_key' => 'home',
            'title' => 'Hải Đăng Travel',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'is_active' => true,
        ]);
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['home'], null);

        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);

        $this->assertFalse($lease['automation']['image_required']);
        $this->assertSame('no_writable_image_target', $lease['automation']['image_handling']);
        $this->assertSame([], $lease['automation']['image_target_fields']);
        $this->assertContains('cover_alt', PageRegistryService::PATCH_FIELDS);
        $this->assertContains('body', PageRegistryService::PATCH_FIELDS);
    }

    public function test_external_image_can_be_imported_for_direct_task(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $task = SeoOptimizationTask::query()->findOrFail($lease['task_id']);
        $task->update(['automation' => [...$task->automation, 'image_url' => 'https://images.example/actual.png']]);
        $this->partialMock(PublicImageDownloader::class, function ($mock): void {
            $mock->shouldReceive('validatedHost')->once()->andReturn('images.example');
            $mock->shouldReceive('download')->once()->with('https://images.example/actual.png')->andReturn(UploadedFile::fake()->image('actual.png', 64, 64));
            $mock->shouldReceive('maxBytes')->andReturn(10485760);
        });

        $ready = app(OptimizationMediaService::class)->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);
        $this->assertSame('imported', $ready['asset']['source_type']);
        $this->assertSame('image/webp', $ready['asset']['mime_type']);

        $stored = Media::query()->findOrFail($ready['asset']['media_id']);
        $this->assertSame('image/webp', $stored->mime_type);
        $this->assertStringEndsWith('.webp', $stored->file_name);
        $this->assertSame('image/webp', getimagesize($stored->getPath())['mime']);
    }

    public function test_tampered_media_manifest_blocks_commit(): void
    {
        [$proposal] = $this->preparedProposal('always_publish', strong: true);
        $asset = SeoOptimizationAsset::query()->firstOrFail();
        $asset->update(['manifest' => [...$asset->manifest, 'url' => 'https://invalid.example/image.jpg']]);

        $this->expectException(HttpException::class);
        app(OptimizationAutomationService::class)->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
    }

    private function preparedProposal(string $mode, bool $strong = false, array $warnings = []): array
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, $mode, ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $media = app(OptimizationMediaService::class);
        $prepared = $media->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);
        $this->assertSame('generation_required', $prepared['status']);
        $ready = $media->upload($lease['task_id'], $lease['lease_token'], UploadedFile::fake()->image('illustration.png', 64, 64), 'Ảnh minh họa hành trình', 'Minh họa hành trình du lịch', $this->writer, $this->credential->id);
        $asset = $ready['asset'];
        $content = $strong ? $this->strongContent($asset['url']) : $this->service->content.'<p><img src="'.$asset['url'].'" alt="Ảnh minh họa hành trình"></p><p>Ảnh minh họa được tạo bằng AI.</p>';
        $patch = $strong
            ? ['meta_title' => 'Tư vấn Đà Nẵng | Hải Đăng Travel', 'meta_description' => 'Tư vấn Đà Nẵng theo nhu cầu cùng Hải Đăng Travel, gồm phạm vi dịch vụ, quy trình, chi phí và các lưu ý cần thiết.', 'content' => $content]
            : ['content' => $content];
        $payload = $this->payload($lease, $patch);
        $payload['warnings'] = $warnings;
        $proposal = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $payload, $this->writer, $this->credential->id, 'submit-direct-test');

        return [$proposal, $asset];
    }

    private function strongContent(string $imageUrl): string
    {
        $paragraph = 'Tư vấn Đà Nẵng giúp khách xác định hành trình phù hợp nhu cầu, thời gian và trải nghiệm mong muốn cùng Hải Đăng Travel. ';

        return '<h2>Phạm vi dịch vụ tư vấn Đà Nẵng</h2><p>'.str_repeat($paragraph, 18).'</p>'
            .'<h2>Quy trình tư vấn hành trình</h2><p>'.str_repeat($paragraph, 14).'</p>'
            .'<h2>Chi phí và lựa chọn phù hợp</h2><p>'.str_repeat($paragraph, 12).'</p>'
            .'<h2>Câu hỏi thường gặp</h2><p>'.str_repeat($paragraph, 8).'</p>'
            .'<p><a href="/dich-vu">Xem dịch vụ du lịch</a> và <a href="/lien-he">liên hệ tư vấn</a>.</p>'
            .'<figure><img src="'.$imageUrl.'" alt="Ảnh minh họa hành trình Đà Nẵng"><figcaption>Ảnh minh họa được tạo bằng AI.</figcaption></figure>';
    }
}
