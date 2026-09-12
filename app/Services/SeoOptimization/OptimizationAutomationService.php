<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationAudit;
use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationOutbox;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Support\FrontsiteUrls;
use App\Support\RichText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OptimizationAutomationService
{
    public function __construct(
        private OptimizationAccess $access,
        private OptimizationPolicyService $policies,
        private KeywordBriefResolver $briefs,
        private OptimizationBrief $briefValidator,
        private OptimizationWorkflowService $workflow,
        private PageRegistryService $registry,
        private OptimizationMediaService $media,
    ) {}

    public function claimNext(User $user, string $credentialId, bool $adminQueueOnly = false): ?array
    {
        $credential = $this->credential($user, $credentialId);
        $permittedTypes = array_values(array_intersect(
            $credential->allowed_page_types ?? [],
            array_keys(array_filter(OptimizationAccess::PAGE_PERMISSIONS, fn ($permission): bool => $user->can($permission.'.edit') && $user->can($permission.'.index'))),
        ));
        $this->ensure($permittedTypes !== [], 'Token chưa có phạm vi nội dung phù hợp.');

        while ($adminQueuedTask = SeoOptimizationTask::query()
            ->whereIn('page_id', $this->access->queryFor($user)->whereIn('page_type', $permittedTypes)->select('id'))
            ->whereNull('automation')
            ->where('attempts', '<', config('seo_optimization.max_attempts', 3))
            ->where(fn ($query) => $query->where('status', 'queued')
                ->orWhere(fn ($lease) => $lease->where('status', 'leased')->where('leased_until', '<=', now())))
            ->oldest()
            ->first()) {
            if ($lease = $this->claimTask($adminQueuedTask, $user, $credential)) {
                return $lease;
            }
        }

        if ($adminQueueOnly) {
            return null;
        }

        $policy = $this->policies->current();
        $this->ensure($policy !== null, 'Chưa cấu hình Auto Optimize trên server.');
        $types = array_values(array_intersect($policy->allowed_page_types ?? [], $permittedTypes));
        $this->ensure($types !== [], 'Chưa cấu hình phạm vi tự động phù hợp với token.');

        $pending = SeoOptimizationTask::query()
            ->where('requested_by', $user->id)
            ->whereIn('page_id', $this->access->queryFor($user)->whereIn('page_type', $types)->select('id'))
            ->where('automation->mode', 'direct_cms')
            ->where(fn ($query) => $query->where('status', 'queued')
                ->orWhere(fn ($lease) => $lease->where('status', 'leased')->where('leased_until', '<=', now())))
            ->oldest()
            ->first();

        if ($pending && $lease = $this->claimTask($pending, $user, $credential)) {
            return $lease;
        }

        $this->registry->sync();
        $pages = $this->access->queryFor($user)
            ->whereIn('page_type', $types)
            ->where('classification', 'INDEXABLE')
            ->with(['audits' => fn ($query) => $query->latest()->limit(1)])
            ->get()
            ->sortBy(fn (SeoOptimizationPage $page): float => (float) ($page->audits->first()?->score ?? -1));

        foreach ($pages as $page) {
            if ($this->registry->writableFields($page) === []) {
                continue;
            }

            $brief = $this->briefs->resolve($page, $user);
            $sourceVersion = $this->registry->currentVersion($page);
            $strategyRevision = $this->briefValidator->revision($brief);
            if ($audit = $this->scoreThresholdAudit($page, $sourceVersion, $strategyRevision)) {
                SeoOptimizationEvent::query()->firstOrCreate([
                    'page_id' => $page->id,
                    'event' => 'automation.score_threshold_skipped',
                ], [
                    'actor_id' => $user->id,
                    'payload' => [
                        'score' => $audit->score,
                        'threshold' => $this->scoreThreshold(),
                        'source_version' => $sourceVersion,
                        'strategy_revision' => $strategyRevision,
                        'audit_id' => $audit->id,
                    ],
                ]);

                continue;
            }
            if ($conflict = $this->briefs->conflictingPage($page, $brief)) {
                SeoOptimizationEvent::query()->firstOrCreate([
                    'page_id' => $page->id,
                    'event' => 'automation.keyword_conflict_skipped',
                ], [
                    'actor_id' => $user->id,
                    'payload' => [
                        'primary_keyword' => $brief['primary_keyword'],
                        'search_intent' => $brief['search_intent'],
                        'conflicting_page_id' => $conflict->id,
                        'conflicting_path' => $conflict->path,
                    ],
                ]);

                continue;
            }

            if (SeoOptimizationTask::query()->withTrashed()->where('page_id', $page->id)
                ->where('source_version', $sourceVersion)
                ->where('strategy_revision', $strategyRevision)
                ->exists()) {
                continue;
            }

            $imageUrl = $this->sourceImageUrl($page);
            $imageTargetFields = array_values(array_intersect(
                ['content', 'body', 'description', 'intro_content'],
                $this->registry->writableFields($page),
            ));
            $autoMediaEnabled = (bool) config('seo_optimization.auto_media_enabled', true);
            $imageRequired = $autoMediaEnabled && $imageUrl === '' && $imageTargetFields !== [];
            $task = $this->workflow->enqueue($page->fresh(), $user, 'direct-'.hash('sha256', $page->id.':'.$sourceVersion.':'.$strategyRevision), [
                'mode' => 'direct_cms',
                'policy_revision' => $policy->revision,
                'source_revision' => $sourceVersion,
                'image_required' => $imageRequired,
                'image_handling' => match (true) {
                    ! $autoMediaEnabled => 'disabled',
                    $imageUrl !== '' => 'existing_media_reused',
                    $imageTargetFields === [] => 'no_writable_image_target',
                    default => 'prepare_and_insert',
                },
                'image_target_fields' => $imageTargetFields,
                'image_url' => $imageUrl,
                'image_prompt' => '',
                'image_alt' => $page->title,
            ]);
            SeoOptimizationEvent::query()->create([
                'page_id' => $page->id,
                'actor_id' => $user->id,
                'event' => 'automation.direct_task_created',
                'payload' => ['task_id' => $task->id, 'source_version' => $sourceVersion, 'policy_revision' => $policy->revision],
            ]);

            return $this->claimTask($task, $user, $credential);
        }

        return null;
    }

    public function complete(string $proposalId, string $contentHash, User $user, string $credentialId): array
    {
        $credential = $this->credential($user, $credentialId);
        $proposal = SeoOptimizationProposal::query()->findOrFail($proposalId);
        $task = $proposal->task()->firstOrFail();
        $this->ensure(! $task->trashed(), 'Task đã bị quản trị viên xóa khỏi hàng chờ.');
        $page = $proposal->page()->firstOrFail();
        $this->access->authorize($user, 'propose', $page);
        $this->ensure(
            in_array($page->page_type, $credential->allowed_page_types ?? [], true)
            && ($task->automation['mode'] ?? null) === 'direct_cms'
            && $task->leased_by === $credentialId,
            'Đề xuất không thuộc lượt tối ưu CMS trực tiếp của token.',
        );
        $this->ensure(hash_equals($proposal->content_hash, $contentHash), 'Hash nội dung không khớp đề xuất.');
        $this->ensure(hash_equals($proposal->content_hash, hash('sha256', json_encode([
            $proposal->patch, $proposal->before, $proposal->claims ?? [], $proposal->missing_facts ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))), 'Đề xuất đã bị sửa ngoài luồng.');

        $eventKey = 'automation-complete:'.$proposal->id;
        if ($existing = SeoOptimizationOutbox::query()->where('event_key', $eventKey)->first()) {
            return $this->result($proposal->fresh(), $existing);
        }

        $this->ensure(in_array($proposal->status, ['in_review', 'need_data', 'applied', 'verify_failed'], true), 'Đề xuất không còn trong trạng thái có thể hoàn tất.');
        if ($proposal->status === 'in_review' && ($task->automation['image_required'] ?? false)) {
            $this->assertImage($proposal, $task);
        }

        $proposal = $this->workflow->applyConfigured($proposal, $user, $credentialId);
        $status = match ($proposal->status) {
            'applied' => 'PUBLISHED',
            'verify_failed' => 'FAILED',
            'need_data' => 'NEED_DATA',
            default => 'PREVIEW',
        };
        $event = SeoOptimizationOutbox::query()->firstOrCreate([
            'event_key' => $eventKey,
        ], [
            'destination' => 'seo_optimization_local',
            'status' => 'synced',
            'synced_at' => now(),
            'payload' => [
                'site_id' => $page->site_id,
                'source_revision' => $task->automation['source_revision'] ?? $task->source_version,
                'status' => $status,
                'task_id' => $task->id,
                'proposal_id' => $proposal->id,
                'backup_id' => $proposal->backup?->id,
                'result_url' => $proposal->applied_at
                    ? FrontsiteUrls::canonicalBaseUrl().$page->path
                    : route('admin.seo-optimization.proposals.show', $proposal),
                'last_error' => $status === 'FAILED'
                    ? 'CMS đã ghi; kiểm tra sau áp dụng chưa đạt, không áp dụng lại.'
                    : ($status === 'NEED_DATA' ? implode(' ', $proposal->missing_facts ?? []) : ''),
            ],
        ]);

        return $this->result($proposal, $event);
    }

    public function fail(string $taskId, string $leaseToken, User $user, string $credentialId, string $message): SeoOptimizationTask
    {
        $this->credential($user, $credentialId);
        $task = $this->workflow->failTask($taskId, $leaseToken, $user, $credentialId, $message);
        $this->ensure(($task->automation['mode'] ?? null) === 'direct_cms', 'Task không thuộc luồng tối ưu CMS trực tiếp.');
        SeoOptimizationOutbox::query()->firstOrCreate(['event_key' => 'automation-fail:'.$task->id], [
            'destination' => 'seo_optimization_local',
            'status' => 'synced',
            'synced_at' => now(),
            'payload' => [
                'site_id' => config('seo_optimization.site_id'),
                'source_revision' => $task->automation['source_revision'] ?? $task->source_version,
                'task_id' => $task->id,
                'status' => 'FAILED',
                'last_error' => $task->last_error,
            ],
        ]);

        return $task;
    }

    private function claimTask(SeoOptimizationTask $task, User $user, SeoOptimizationCredential $credential): ?array
    {
        $isDirectAutomation = ($task->automation['mode'] ?? null) === 'direct_cms';
        $this->ensure($task->automation === null || $isDirectAutomation, 'Task không thuộc hàng chờ SEO được hỗ trợ.');
        if ($this->skipTaskAboveScoreThreshold($task, $user)) {
            return null;
        }
        $lease = $this->workflow->claim($user, $credential->id, $task->id);
        if ($lease) {
            $lease['queue_source'] = $isDirectAutomation ? 'automatic_selection' : 'admin_queue';
            $lease['next_action_after_submit'] = $isDirectAutomation ? 'commit_content_optimization' : 'await_human_review';
            if ($isDirectAutomation) {
                $lease['completion_url'] = url('/mcp/seo-optimization/commit');
                $lease['instructions'] .= ' Với task automation trực tiếp: chỉ gọi prepare_seo_image khi image_required=true và chèn URL manifest vào một field thuộc automation.image_target_fields. image_handling=existing_media_reused nghĩa là trang đã có Media nên không buộc chèn lại URL; có thể sửa cover_alt nếu field này nằm trong writable_fields. image_handling=no_writable_image_target nghĩa là adapter không có field nội dung phù hợp và Media không chặn proposal. Nếu cần tạo ảnh, dùng công cụ tạo ảnh của Codex rồi tải file lên upload_url; ảnh tạo bằng AI phải ghi là minh họa. Nội dung và dữ kiện đang có trong snapshot là baseline chính xác: được giữ hoặc diễn đạt rõ hơn mà không cần tài liệu ngoài, claims hay missing_facts. Nếu không được yêu cầu đổi facts, hãy giữ nguyên facts và tiếp tục tối ưu thay vì tạo NEED_DATA. Chỉ dữ kiện mới hoặc bị thay đổi mới cần nguồn. Sau submit gọi commit_content_optimization với proposal_id/content_hash. Ở policy Luôn publish, server tự ghi CMS khi điểm sau lớn hơn điểm trước; policy preview luôn chờ duyệt. Không tự đổi policy.';
            } else {
                $lease['instructions'] .= ' Đây là task do người dùng đưa vào hàng chờ admin. Sau submit_seo_optimization, dừng ở đề xuất chờ duyệt và báo proposal_id; không gọi commit_content_optimization và không tự áp dụng nội dung.';
            }
        }

        return $lease;
    }

    private function skipTaskAboveScoreThreshold(SeoOptimizationTask $task, User $user): bool
    {
        return DB::transaction(function () use ($task, $user): bool {
            $task = SeoOptimizationTask::query()->lockForUpdate()->findOrFail($task->id);
            $claimable = $task->status === 'queued'
                || ($task->status === 'leased' && $task->leased_until?->isPast());
            if (! $claimable) {
                return false;
            }

            $page = $task->page()->firstOrFail();
            $audit = $this->scoreThresholdAudit($page, $task->source_version, $task->strategy_revision);
            if (! $audit) {
                return false;
            }

            $task->update([
                'status' => 'skipped',
                'last_error' => sprintf(
                    'Bỏ qua vì điểm SEO hiện tại %s cao hơn ngưỡng %s.',
                    number_format((float) $audit->score, 1, '.', ''),
                    number_format($this->scoreThreshold(), 1, '.', ''),
                ),
                'leased_by' => null,
                'leased_until' => null,
                'lease_token_hash' => null,
                'completed_at' => now(),
            ]);
            SeoOptimizationEvent::query()->create([
                'page_id' => $page->id,
                'actor_id' => $user->id,
                'event' => 'task.score_threshold_skipped',
                'payload' => [
                    'task_id' => $task->id,
                    'score' => $audit->score,
                    'threshold' => $this->scoreThreshold(),
                    'audit_id' => $audit->id,
                    'source_version' => $task->source_version,
                    'strategy_revision' => $task->strategy_revision,
                ],
            ]);

            return true;
        });
    }

    private function scoreThresholdAudit(SeoOptimizationPage $page, string $sourceVersion, string $strategyRevision): ?SeoOptimizationAudit
    {
        return $page->audits()
            ->where('source_version', $sourceVersion)
            ->where('strategy_revision', $strategyRevision)
            ->where('rule_version', config('seo_optimization.rule_version'))
            ->whereNotNull('score')
            ->where('score', '>', $this->scoreThreshold())
            ->latest()
            ->first();
    }

    private function scoreThreshold(): float
    {
        return (float) config('seo_optimization.processing_score_threshold', 80);
    }

    private function credential(User $user, string $id): SeoOptimizationCredential
    {
        $this->access->authorize($user, 'propose');
        $credential = SeoOptimizationCredential::query()->where('user_id', $user->id)->findOrFail($id);
        $this->ensure(! $credential->revoked_at && (! $credential->expires_at || $credential->expires_at->isFuture())
            && ! array_diff(['automate', 'propose'], $credential->abilities ?? []), 'Token không có quyền tự động hoặc đã hết hạn.');

        return $credential;
    }

    private function sourceImageUrl(SeoOptimizationPage $page): string
    {
        $source = $this->registry->source($page);
        if (! $source || ! method_exists($source, 'getMedia')) {
            return '';
        }

        $media = $source->getMedia('*')->first(fn ($item): bool => str_starts_with((string) $item->mime_type, 'image/')
            && config('filesystems.disks.'.$item->disk.'.visibility') === 'public');

        $url = $media?->getUrl() ?? '';

        return str_starts_with($url, '/') ? FrontsiteUrls::canonicalBaseUrl().$url : $url;
    }

    private function assertImage(SeoOptimizationProposal $proposal, SeoOptimizationTask $task): void
    {
        $manifest = $this->media->assertReadyForTask($task->id);
        if (($manifest['source_type'] ?? '') === 'same_site' && ($task->snapshot['media'] ?? []) !== []) {
            return;
        }
        $url = $manifest['url'] ?? '';
        $found = false;
        foreach ($proposal->patch as $field => $value) {
            if (! in_array($field, ['content', 'body', 'description', 'intro_content'], true) || ! is_string($value)) {
                continue;
            }
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$value);
            $fieldHasAsset = false;
            foreach ($document->getElementsByTagName('img') as $image) {
                if ($image->getAttribute('src') === $url) {
                    $this->ensure(trim($image->getAttribute('alt')) !== '', 'Ảnh được chèn cần alt đúng ngữ cảnh.');
                    $found = true;
                    $fieldHasAsset = true;
                }
            }
            if ($fieldHasAsset && ($manifest['source_type'] ?? '') === 'generated') {
                $this->ensure(Str::contains(Str::lower(RichText::normalizePlain($value)), 'minh họa'), 'Ảnh tạo bằng AI cần ghi rõ là ảnh minh họa trong nội dung.');
            }
        }
        $this->ensure($found, 'Patch chưa chèn đúng ảnh từ manifest Media.');
    }

    private function result(SeoOptimizationProposal $proposal, SeoOptimizationOutbox $event): array
    {
        return [
            'proposal_id' => $proposal->id,
            'status' => $proposal->status,
            'public_content_changed' => $proposal->applied_at !== null,
            'sync_status' => $event->status,
            'backup_id' => $proposal->backup?->id,
            'seo_score_before' => data_get($proposal->qa, 'baseline_seo_gate.score'),
            'seo_score' => data_get($proposal->qa, 'seo_gate.score'),
            'seo_score_delta' => data_get($proposal->qa, 'score_delta'),
            'seo_grade' => data_get($proposal->qa, 'seo_gate.grade'),
            'warnings' => data_get($proposal->qa, 'warnings', []),
            'result_url' => $event->payload['result_url'],
        ];
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['automation' => $message]);
        }
    }
}
