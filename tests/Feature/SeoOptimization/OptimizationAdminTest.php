<?php

namespace Tests\Feature\SeoOptimization;

use App\Livewire\Admin\SeoOptimization\PageDetail;
use App\Livewire\Admin\SeoOptimization\PagesIndex;
use App\Livewire\Admin\SeoOptimization\ProposalReview;
use App\Livewire\Admin\SeoOptimization\TasksIndex;
use App\Models\SeoOptimizationAudit;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationBrief;
use App\Services\SeoOptimization\PageRegistryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\Domains\Cms\Models\SiteSetting;

class OptimizationAdminTest extends OptimizationTestCase
{
    public function test_active_super_admin_without_email_verification_can_access_seo_admin(): void
    {
        $admin = User::factory()->unverified()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));

        $this->actingAs($admin);
        foreach (['/admin/seo-optimization', '/admin/seo-optimization/pages/'.$this->page->id, '/admin/seo-optimization/tasks', '/admin/seo-optimization/settings'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->assertNull($admin->fresh()->email_verified_at);
    }

    public function test_inactive_super_admin_is_still_denied(): void
    {
        $admin = User::factory()->create(['is_active' => false]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));

        $this->actingAs($admin)->get('/admin/seo-optimization')->assertForbidden();
    }

    public function test_authorized_cms_editor_follows_optional_email_verification(): void
    {
        $this->reviewer->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->reviewer)->get('/admin/seo-optimization')->assertOk();
        $this->reviewer->revokePermissionTo('admin.seo-optimization.index');
        $this->get('/admin/seo-optimization')->assertForbidden();
    }

    public function test_admin_pages_render_and_guests_and_unprivileged_users_are_denied(): void
    {
        $this->get('/admin/seo-optimization')->assertRedirect();
        $this->actingAs(User::factory()->create(['is_active' => true]))->get('/admin/seo-optimization')->assertForbidden();
        $this->actingAs($this->reviewer);
        $this->get('/admin/seo-optimization')->assertOk()->assertSee('SEO AI Optimize');
        $this->get('/admin/seo-optimization/pages/'.$this->page->id)->assertOk()->assertSee('Tư vấn hành trình Đà Nẵng');
        $this->get('/admin/seo-optimization/tasks')->assertOk();
        $this->get('/admin/seo-optimization/settings')->assertOk()->assertSee('Codex Schedule MCP');
        $this->get('/admin/seo-optimization/proposals/'.$this->proposal()->id)->assertOk();
    }

    public function test_saving_brief_fills_required_audit_topics_and_entities(): void
    {
        Livewire::actingAs($this->reviewer)
            ->test(PageDetail::class, ['page' => $this->page])
            ->set('entities', '')
            ->set('requiredTopics', '')
            ->call('saveBrief')
            ->assertHasNoErrors()
            ->assertSet('entities', "Tư vấn hành trình Đà Nẵng\nHải Đăng Travel")
            ->assertSet('requiredTopics', "phạm vi dịch vụ\nquy trình\nchi phí\ncâu hỏi thường gặp");

        $brief = $this->page->fresh()->keyword_brief;
        $this->assertSame(['Tư vấn hành trình Đà Nẵng', 'Hải Đăng Travel'], $brief['entities']);
        $this->assertSame(['phạm vi dịch vụ', 'quy trình', 'chi phí', 'câu hỏi thường gặp'], $brief['required_topics']);
        $this->assertSame('cms_review_brief', $brief['origin']);
    }

    public function test_partial_audit_ui_shows_coverage_and_missing_dimensions(): void
    {
        $report = [
            'status' => 'partial',
            'score' => null,
            'grade' => null,
            'assessed_dimensions' => 10,
            'total_dimensions' => 12,
            'assessment_coverage' => 83.3,
            'missing_dimensions' => [
                ['dimension' => 'topic', 'label' => 'Chủ đề bắt buộc'],
                ['dimension' => 'entity', 'label' => 'Thực thể cần đề cập'],
            ],
        ];
        SeoOptimizationAudit::query()->create([
            'page_id' => $this->page->id,
            'actor_id' => $this->reviewer->id,
            'source_version' => $this->page->source_version,
            'strategy_revision' => 'partial-brief',
            'rule_version' => 'test-v1',
            'status' => 'partial',
            'score' => null,
            'grade' => null,
            'snapshot' => [],
            'report' => $report,
        ]);

        Livewire::actingAs($this->reviewer)
            ->test(PageDetail::class, ['page' => $this->page])
            ->assertSee('Chưa thể tính tổng')
            ->assertSee('Chưa xếp hạng · Chưa đánh giá đủ')
            ->assertSee('Đã đánh giá 10/12 tiêu chí (83,3%) · Cần bổ sung: Chủ đề bắt buộc, Thực thể cần đề cập')
            ->assertDontSee('Chưa có · Chưa đánh giá đủ');

        Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->assertSee('Chưa thể tính tổng')
            ->assertSee('Đã đánh giá 10/12 tiêu chí (83,3%) · Cần bổ sung: Chủ đề bắt buộc, Thực thể cần đề cập');
    }

    public function test_audit_uses_safe_fallback_criteria_without_mutating_the_saved_brief(): void
    {
        $brief = $this->page->keyword_brief;
        $brief['entities'] = [];
        $brief['required_topics'] = [];
        $brief['revision'] = app(OptimizationBrief::class)->revision($brief);
        $this->page->update(['keyword_brief' => $brief]);

        Livewire::actingAs($this->reviewer)
            ->test(PageDetail::class, ['page' => $this->page])
            ->call('runAudit')
            ->assertHasNoErrors()
            ->assertSet('entities', "Tư vấn hành trình Đà Nẵng\nHải Đăng Travel")
            ->assertSet('requiredTopics', "phạm vi dịch vụ\nquy trình\nchi phí\ncâu hỏi thường gặp");

        $audit = $this->page->audits()->latest()->firstOrFail();
        $this->assertNotNull($audit->score);
        $this->assertSame([], $audit->report['missing_dimensions']);
        $this->assertSame([], $this->page->fresh()->keyword_brief['entities']);
        $this->assertSame([], $this->page->fresh()->keyword_brief['required_topics']);
    }

    public function test_pages_index_shows_the_latest_seo_score_without_lazy_loading(): void
    {
        Model::preventLazyLoading(true);
        try {
            Livewire::actingAs($this->reviewer)
                ->test(PagesIndex::class)
                ->assertSee('Điểm SEO')
                ->assertSee('N/A')
                ->assertDontSee('Chưa kiểm tra');
        } finally {
            Model::preventLazyLoading(false);
        }

        foreach ([[58.0, 'D'], [82.5, 'B']] as [$score, $grade]) {
            SeoOptimizationAudit::query()->create([
                'page_id' => $this->page->id,
                'actor_id' => $this->reviewer->id,
                'source_version' => $this->page->source_version,
                'strategy_revision' => 'strategy-'.$grade,
                'rule_version' => 'test-v1',
                'status' => 'complete',
                'score' => $score,
                'grade' => $grade,
                'snapshot' => [],
                'report' => [],
            ]);
        }

        Model::preventLazyLoading(true);
        try {
            Livewire::actingAs($this->reviewer)
                ->test(PagesIndex::class)
                ->assertSee('Điểm SEO')
                ->assertSee('82.5/100')
                ->assertSee('Cấp B')
                ->assertSee('Kiểm tra gần nhất')
                ->assertSee('2 lần kiểm tra')
                ->assertDontSee('58/100');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_pages_index_sorts_by_latest_seo_score_and_keeps_na_last(): void
    {
        $low = SeoOptimizationPage::query()->create([
            'site_id' => config('seo_optimization.site_id'),
            'locale' => 'vi',
            'page_type' => 'service',
            'owner_type' => 'service',
            'owner_id' => 'score-low',
            'path' => '/dich-vu/score-low',
            'title' => 'Điểm thấp',
            'classification' => 'INDEXABLE',
            'source_version' => 'low-v1',
            'last_seen_at' => now()->subMinute(),
        ]);
        $high = SeoOptimizationPage::query()->create([
            'site_id' => config('seo_optimization.site_id'),
            'locale' => 'vi',
            'page_type' => 'service',
            'owner_type' => 'service',
            'owner_id' => 'score-high',
            'path' => '/dich-vu/score-high',
            'title' => 'Điểm cao',
            'classification' => 'INDEXABLE',
            'source_version' => 'high-v1',
            'last_seen_at' => now()->subMinutes(2),
        ]);

        SeoOptimizationAudit::query()->create([
            'page_id' => $low->id,
            'actor_id' => $this->reviewer->id,
            'source_version' => $low->source_version,
            'strategy_revision' => 'sort-old-high-score',
            'rule_version' => 'test-v1',
            'status' => 'complete',
            'score' => 99,
            'grade' => 'A+',
            'snapshot' => [],
            'report' => [],
        ]);

        foreach ([[$low, 42.0, 'F'], [$high, 91.0, 'A']] as [$page, $score, $grade]) {
            SeoOptimizationAudit::query()->create([
                'page_id' => $page->id,
                'actor_id' => $this->reviewer->id,
                'source_version' => $page->source_version,
                'strategy_revision' => 'sort-'.$grade,
                'rule_version' => 'test-v1',
                'status' => 'complete',
                'score' => $score,
                'grade' => $grade,
                'snapshot' => [],
                'report' => [],
            ]);
        }

        $component = Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->call('sortByScore')
            ->assertSet('scoreSort', 'desc');

        $this->assertSame(
            ['Điểm cao', 'Điểm thấp', $this->page->title],
            $component->viewData('pages')->pluck('title')->all(),
        );

        $component->call('sortByScore')->assertSet('scoreSort', 'asc');

        $this->assertSame(
            ['Điểm thấp', 'Điểm cao', $this->page->title],
            $component->viewData('pages')->pluck('title')->all(),
        );
    }

    public function test_filtered_bulk_keyword_sync_uses_main_seo_keywords_and_preserves_manual_briefs(): void
    {
        $this->reviewer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.index', 'web'));
        $this->reviewer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.edit', 'web'));
        SiteSetting::query()->update([
            'seo_keywords' => 'tour trong nước, tour nước ngoài, tour đoàn, Hải Đăng Travel',
        ]);
        $domestic = SeoOptimizationPage::query()->where('path', '/tour-trong-nuoc')->firstOrFail();
        $international = SeoOptimizationPage::query()->where('path', '/tour-nuoc-ngoai')->firstOrFail();

        Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->set('search', 'Tour trong nước')
            ->set('pageType', 'tour_scope')
            ->set('classification', 'INDEXABLE')
            ->set('briefFilter', 'missing')
            ->call('syncDefaultKeywords')
            ->assertHasNoErrors();

        $this->assertSame('tour trong nước', data_get($domestic->fresh()->keyword_brief, 'primary_keyword'));
        $this->assertSame('site_seo_keywords', data_get($domestic->fresh()->keyword_brief, 'origin'));
        $this->assertContains('tour nước ngoài', data_get($domestic->fresh()->keyword_brief, 'secondary_keywords'));
        $this->assertNull($international->fresh()->keyword_brief);
        $this->assertSame('tư vấn Đà Nẵng', data_get($this->page->fresh()->keyword_brief, 'primary_keyword'));
        $this->assertSame('cms_review_brief', data_get($this->page->fresh()->keyword_brief, 'origin'));
        $this->assertDatabaseHas('seo_optimization_events', [
            'page_id' => $domestic->id,
            'actor_id' => $this->reviewer->id,
            'event' => 'brief.default_synced',
        ]);

        SiteSetting::query()->update(['seo_keywords' => 'Tư vấn hành trình Đà Nẵng']);
        Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->set('search', 'Tư vấn hành trình Đà Nẵng')
            ->set('pageType', 'service')
            ->set('briefFilter', 'configured')
            ->call('syncDefaultKeywords')
            ->assertHasNoErrors();

        $this->assertSame('tư vấn Đà Nẵng', data_get($this->page->fresh()->keyword_brief, 'primary_keyword'));
        $this->assertSame('cms_review_brief', data_get($this->page->fresh()->keyword_brief, 'origin'));
        $this->assertFalse(SeoOptimizationEvent::query()
            ->where('page_id', $this->page->id)
            ->where('event', 'brief.default_synced')
            ->exists());
    }

    public function test_bulk_keyword_sync_requires_propose_permission(): void
    {
        $this->reviewer->revokePermissionTo('admin.seo-optimization.propose');

        Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->call('syncDefaultKeywords')
            ->assertForbidden();
    }

    public function test_bulk_brief_updates_all_filtered_selected_pages_ignores_blank_fields_and_preserves_active_work(): void
    {
        $pages = collect(range(1, 27))->map(function (int $index): SeoOptimizationPage {
            $slug = sprintf('%02d', $index);
            $page = SeoOptimizationPage::query()->create([
                'site_id' => config('seo_optimization.site_id'),
                'locale' => 'vi',
                'page_type' => 'service',
                'owner_type' => 'service',
                'owner_id' => 'batch-'.$slug,
                'path' => '/dich-vu/batch-brief-'.$slug,
                'title' => 'Batch Brief '.$slug,
                'classification' => 'INDEXABLE',
                'source_version' => 'batch-'.$slug.'-v1',
                'last_seen_at' => now(),
            ]);
            $brief = [
                'primary_keyword' => 'từ khóa riêng '.$slug,
                'search_intent' => 'INFORMATIONAL',
                'secondary_keywords' => ['từ khóa hiện có '.$slug],
                'semantic_terms' => [],
                'entities' => ['Hải Đăng Travel'],
                'required_topics' => ['lịch trình'],
                'required_internal_links' => [],
                'fact_sources' => [],
                'notes' => 'Ghi chú riêng '.$slug,
                'origin' => 'cms_review_brief',
                'updated_by' => $this->reviewer->id,
                'keyword_role' => 'OWNER',
            ];
            $brief['revision'] = app(OptimizationBrief::class)->revision($brief);
            $page->update(['keyword_brief' => $brief]);

            return $page->fresh();
        })->values();
        $queued = $pages->last();
        $excluded = $pages[24];
        SeoOptimizationTask::query()->create([
            'page_id' => $queued->id,
            'requested_by' => $this->reviewer->id,
            'status' => 'queued',
            'brief' => $queued->keyword_brief,
            'snapshot' => [],
            'source_version' => $queued->source_version,
            'strategy_revision' => data_get($queued->keyword_brief, 'revision'),
            'idempotency_key' => 'batch-active-'.str()->uuid(),
            'request_hash' => hash('sha256', (string) $queued->id),
        ]);

        Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->set('search', 'Batch Brief')
            ->call('toggleSelectAllFiltered')
            ->assertSet('selectAllFiltered', true)
            ->assertViewHas('selectedCount', 27)
            ->call('togglePageSelection', $excluded->id)
            ->assertViewHas('selectedCount', 26)
            ->set('bulkSecondaryKeywords', "tour đoàn\nvisa")
            ->set('bulkRequiredTopics', "chi phí\nlịch trình")
            ->set('bulkPrimaryKeyword', '')
            ->set('bulkSearchIntent', '')
            ->set('bulkNotes', '')
            ->call('applyBulkBrief')
            ->assertHasNoErrors()
            ->assertSet('selectAllFiltered', false)
            ->assertSet('selectedPageIds', []);

        foreach ($pages as $index => $page) {
            $brief = $page->fresh()->keyword_brief;
            $this->assertSame('từ khóa riêng '.str($page->title)->afterLast(' '), $brief['primary_keyword']);
            $this->assertSame('INFORMATIONAL', $brief['search_intent']);
            if (in_array($index, [24, 26], true)) {
                $this->assertNotContains('tour đoàn', $brief['secondary_keywords']);

                continue;
            }
            $this->assertContains('tour đoàn', $brief['secondary_keywords']);
            $this->assertContains('visa', $brief['secondary_keywords']);
            $this->assertSame(['lịch trình', 'chi phí'], $brief['required_topics']);
            $this->assertStringStartsWith('Ghi chú riêng ', $brief['notes']);
            $this->assertSame('cms_review_brief', $brief['origin']);
            $this->assertDatabaseHas('seo_optimization_events', [
                'page_id' => $page->id,
                'event' => 'brief.saved',
            ]);
        }
    }

    public function test_bulk_brief_rebuilds_selection_from_current_permissions_and_requires_non_blank_changes(): void
    {
        $landing = SeoOptimizationPage::query()->create([
            'site_id' => config('seo_optimization.site_id'),
            'locale' => 'vi',
            'page_type' => 'landing',
            'owner_type' => 'landing',
            'owner_id' => 'bulk-permission-landing',
            'path' => '/bulk-permission-landing',
            'title' => 'Landing chỉ được xem',
            'classification' => 'INDEXABLE',
            'source_version' => 'landing-v1',
            'last_seen_at' => now(),
        ]);
        $this->reviewer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.index', 'web'));

        Livewire::actingAs($this->reviewer)
            ->test(PagesIndex::class)
            ->set('selectedPageIds', [$this->page->id, $landing->id])
            ->call('applyBulkBrief')
            ->assertHasErrors(['bulkBrief'])
            ->set('bulkEntities', 'Sở Du lịch Đà Nẵng')
            ->call('applyBulkBrief')
            ->assertHasNoErrors();

        $this->assertContains('Sở Du lịch Đà Nẵng', $this->page->fresh()->keyword_brief['entities']);
        $this->assertNull($landing->fresh()->keyword_brief);
        $this->assertFalse(SeoOptimizationEvent::query()->where('page_id', $landing->id)->where('event', 'brief.saved')->exists());
    }

    public function test_livewire_approve_and_apply_require_separate_actions(): void
    {
        $proposal = $this->proposal();
        $editorContent = 'Nội dung biên tập mới nhất trong CMS.';
        DB::table('services')->where('id', $this->service->id)->update(['meta_description' => $editorContent]);

        Livewire::actingAs($this->reviewer)->test(ProposalReview::class, ['proposal' => $proposal])
            ->call('approve')->assertHasNoErrors();
        $approved = $proposal->fresh();
        $this->assertSame('approved', $approved->status);
        $this->assertSame($editorContent, $approved->before['meta_description']);
        $this->assertTrue((bool) data_get($approved->qa, 'human_override.source_changed'));
        $this->assertSame($editorContent, $this->service->fresh()->meta_description);
        Livewire::test(ProposalReview::class, ['proposal' => $proposal->fresh()])->call('apply')->assertHasNoErrors();
        $this->assertSame('applied', $proposal->fresh()->status);
        $this->assertSame($proposal->patch['meta_description'], $this->service->fresh()->meta_description);
    }

    public function test_proposal_review_links_to_public_content_and_matching_cms_editor(): void
    {
        $proposal = $this->proposal();
        $registry = app(PageRegistryService::class);
        $publicUrl = $registry->url($this->page);
        $editUrl = $registry->adminEditUrl($this->page);

        Livewire::actingAs($this->reviewer)
            ->test(ProposalReview::class, ['proposal' => $proposal])
            ->assertSee('Xem bài viết')
            ->assertSee('Chỉnh sửa bài viết')
            ->assertSee($publicUrl)
            ->assertSee($editUrl);

        $this->reviewer->revokePermissionTo('admin.services.edit');

        Livewire::actingAs($this->reviewer)
            ->test(ProposalReview::class, ['proposal' => $proposal])
            ->assertSee('Xem bài viết')
            ->assertSee($publicUrl)
            ->assertDontSee('Chỉnh sửa bài viết')
            ->assertDontSee($editUrl);
    }

    public function test_feedback_exposes_the_actual_validation_message_to_sweetalert(): void
    {
        $proposal = $this->proposal();

        Livewire::actingAs($this->writer)
            ->test(ProposalReview::class, ['proposal' => $proposal])
            ->call('approve')
            ->assertHasErrors(['proposal'])
            ->assertSeeHtml('data-admin-error-count="1"')
            ->assertSeeHtml('data-admin-error-summary="Người tạo đề xuất không được tự duyệt đề xuất của mình."');
    }

    public function test_proposal_and_page_detail_show_before_projected_and_verified_scores_without_lazy_loading(): void
    {
        $proposal = $this->proposal();
        $this->actingAs($this->reviewer);

        Model::preventLazyLoading(true);
        try {
            Livewire::test(ProposalReview::class, ['proposal' => $proposal])
                ->assertSee('Điểm SEO trước / sau')
                ->assertSee('Meta description')
                ->assertSee('Trước tối ưu')
                ->assertSee('Sau đề xuất')
                ->assertSee('Chọn một tiêu chí để xem rõ dữ liệu trước và sau đề xuất')
                ->assertSee('Trước đề xuất')
                ->assertSee('Tư vấn hành trình theo nhu cầu.')
                ->assertDontSee('Đề xuất cũ chưa lưu điểm và bằng chứng chi tiết của tiêu chí này')
                ->assertSee('DỰ KIẾN')
                ->assertSee('Chưa áp dụng hoặc chưa kiểm tra lại.');

            $this->assertCount(12, data_get($proposal->fresh()->qa, 'baseline_seo_gate.dimensions', []));

            $applied = $this->workflow->apply($this->workflow->approve($proposal, $this->reviewer), $this->reviewer);

            Livewire::test(ProposalReview::class, ['proposal' => $applied])
                ->assertSee('Sau áp dụng')
                ->assertSee('ĐÃ TÁI KIỂM TRA CMS')
                ->assertSee('Xem bài viết')
                ->assertSee('Chỉnh sửa bài viết')
                ->assertSee('Kiểm tra lại')
                ->assertSee('Tạo đề xuất hoàn tác')
                ->assertDontSee('Bài viết gốc')
                ->assertDontSee('Xử lý đề xuất')
                ->assertSeeHtml('class="flex flex-wrap items-center justify-end gap-2"');

            Livewire::test(PageDetail::class, ['page' => $this->page])
                ->assertSee('Điểm trước / sau')
                ->assertSee('Sau kiểm tra');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_seo_editor_can_rescore_fields_keep_only_score_gains_and_auto_apply_with_backup(): void
    {
        $lease = $this->lease();
        $proposal = $this->workflow->submit(
            $lease['task_id'],
            $lease['lease_token'],
            $this->payload($lease, [
                'meta_title' => 'Thông tin chung',
                'meta_description' => 'Tư vấn Đà Nẵng: trao đổi nhu cầu và chuẩn bị hành trình phù hợp.',
            ]),
            $this->writer,
            $this->credential->id,
            'editor-rescore-'.str()->uuid(),
        );

        Livewire::actingAs($this->reviewer)
            ->test(ProposalReview::class, ['proposal' => $proposal])
            ->assertSee('Kết quả QA đề xuất')
            ->assertSee('12 tiêu chí SEO')
            ->set('editPatch.meta_title', 'Thông tin chung')
            ->set('editPatch.meta_description', 'Tư vấn Đà Nẵng: trao đổi nhu cầu và chuẩn bị hành trình phù hợp.')
            ->call('rescoreAndApply')
            ->assertHasNoErrors()
            ->assertSee('Đã giữ để áp dụng')
            ->assertSee('Đã bỏ qua');

        $proposal->refresh();
        $retained = collect(data_get($proposal->qa, 'editor_revision.retained_units'))->pluck('key');
        $ignored = collect(data_get($proposal->qa, 'editor_revision.ignored_units'))->pluck('key');
        $this->assertContains('meta_description', $retained);
        $this->assertContains('meta_title', $ignored);
        $this->assertSame(['meta_description'], array_keys($proposal->patch));
        $this->assertNotNull($proposal->applied_at);
        $this->assertContains($proposal->status, ['applied', 'verify_failed']);
        $this->assertSame('Tư vấn Đà Nẵng', $this->service->fresh()->meta_title);
        $this->assertSame('Tư vấn Đà Nẵng: trao đổi nhu cầu và chuẩn bị hành trình phù hợp.', $this->service->fresh()->meta_description);
        $this->assertNotNull($proposal->backup);
        $this->assertTrue(SeoOptimizationEvent::query()->where('proposal_id', $proposal->id)->where('event', 'proposal.editor_rescored')->exists());
    }

    public function test_original_content_permission_is_required_even_with_seo_permissions(): void
    {
        $this->reviewer->revokePermissionTo('admin.services.edit');
        Livewire::actingAs($this->reviewer)->test(ProposalReview::class, ['proposal' => $this->proposal()])
            ->call('approve')->assertForbidden();
    }

    public function test_permission_setup_is_additive_and_works_with_strict_lazy_loading(): void
    {
        $admin = Role::findOrCreate('admin', 'web');
        Role::findOrCreate('super_admin', 'web');
        $custom = Permission::findOrCreate('custom.permission.to.preserve', 'web');
        $admin->givePermissionTo($custom);
        Model::preventLazyLoading(true);
        try {
            $this->artisan('seo-optimize:permissions')->assertSuccessful();
            $this->assertTrue($admin->fresh()->hasPermissionTo($custom));
            $this->assertTrue($admin->fresh()->hasPermissionTo('admin.seo-optimization.approve'));
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_task_can_be_deleted_from_queue_without_deleting_its_proposal_or_audit_history(): void
    {
        $proposal = $this->proposal();
        $task = SeoOptimizationTask::query()->findOrFail($proposal->task_id);

        Livewire::actingAs($this->reviewer)
            ->test(TasksIndex::class)
            ->call('deleteTask', $task->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($task);
        $this->assertModelExists($proposal->fresh());
        $this->assertTrue($proposal->fresh()->task->trashed());
        $this->assertTrue(SeoOptimizationEvent::query()
            ->where('event', 'task.deleted_from_queue')
            ->where('payload->task_id', $task->id)
            ->exists());
    }

    public function test_task_being_processed_cannot_be_deleted(): void
    {
        $lease = $this->lease();
        $task = SeoOptimizationTask::query()->findOrFail($lease['task_id']);

        Livewire::actingAs($this->reviewer)
            ->test(TasksIndex::class)
            ->call('deleteTask', $task->id)
            ->assertHasErrors(['task']);

        $this->assertNotSoftDeleted($task);
    }

    public function test_bulk_cleanup_deletes_only_finished_tasks_and_requires_edit_access(): void
    {
        $failed = $this->workflow->enqueue($this->page, $this->writer, 'failed-task-'.str()->uuid());
        $failed->update(['status' => 'failed', 'completed_at' => now()]);
        $cancelled = $this->workflow->enqueue($this->page, $this->writer, 'cancelled-task-'.str()->uuid());
        $cancelled->update(['status' => 'cancelled', 'completed_at' => now()]);
        $queued = $this->workflow->enqueue($this->page, $this->writer, 'queued-task-'.str()->uuid());

        Livewire::actingAs($this->reviewer)
            ->test(TasksIndex::class)
            ->call('deleteFinishedTasks')
            ->assertHasNoErrors();

        $this->assertSoftDeleted($failed);
        $this->assertSoftDeleted($cancelled);
        $this->assertNotSoftDeleted($queued);

        $this->reviewer->revokePermissionTo('admin.services.edit');
        Livewire::actingAs($this->reviewer)
            ->test(TasksIndex::class)
            ->call('deleteTask', $queued->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($queued);
    }
}
