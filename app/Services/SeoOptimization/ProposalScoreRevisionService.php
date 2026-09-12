<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalScoreRevisionService
{
    public function __construct(
        private OptimizationAccess $access,
        private PageRegistryService $registry,
        private PageSnapshotService $snapshots,
        private KeywordBriefResolver $briefResolver,
        private OptimizationBrief $briefs,
        private PageAuditService $audits,
        private ContentPatchValidator $patches,
        private OptimizationWorkflowService $workflow,
    ) {}

    /** @return array{proposal: SeoOptimizationProposal, retained_count: int, ignored_count: int, applied: bool, message: string} */
    public function revise(SeoOptimizationProposal $proposal, array $requestedPatch, User $user, bool $autoApply = false): array
    {
        $this->humanOnly();
        $proposal->loadMissing('page', 'task');
        $page = $proposal->page;
        $this->access->authorize($user, 'approve', $page);
        if ($autoApply) {
            $this->access->authorize($user, 'apply', $page);
            $this->ensure($proposal->created_by !== $user->id, 'Người tạo đề xuất không được tự duyệt và áp dụng đề xuất của mình.');
        }
        $this->ensure(! $proposal->applied_at, 'Không thể sửa đề xuất đã áp dụng.');
        $this->ensure(in_array($proposal->status, ['in_review', 'need_data', 'approved', 'stale'], true), 'Trạng thái đề xuất không còn cho phép chỉnh sửa.');
        $this->ensure(! isset($proposal->qa['rollback_of']), 'Đề xuất hoàn tác phải giữ nguyên nội dung từ backup đã xác minh.');
        $this->assertRequestedScope($proposal->patch ?? [], $requestedPatch);

        $originalContentHash = (string) $proposal->content_hash;
        $snapshot = $this->snapshots->capture($page);
        $storedBrief = $page->fresh()->keyword_brief ?? [];
        $brief = $this->briefResolver->completeAuditInput($page, $storedBrief);
        $strategyRevision = $this->briefs->revision($storedBrief);
        $baselineReport = $this->audits->evaluate($snapshot, $brief);
        $validatedRequested = $this->validateRequestedPatch($requestedPatch, $snapshot, $brief);
        $selection = $this->selectImprovingUnits(
            $validatedRequested['patch'],
            $snapshot,
            $brief,
            $baselineReport,
        );

        $acceptedPatch = $selection['patch'];
        if ($acceptedPatch !== []) {
            $validatedAccepted = $this->patches->validate($acceptedPatch, $snapshot, $brief);
            $acceptedPatch = $validatedAccepted['patch'];
            $before = $validatedAccepted['before'];
            $missingFacts = $validatedAccepted['missing_facts'];
            $warnings = $validatedAccepted['warnings'];
            $finalReport = $this->audits->evaluate($this->audits->candidate($snapshot, $acceptedPatch), $brief);
        } else {
            $before = [];
            $missingFacts = [];
            $warnings = [];
            $finalReport = $baselineReport;
        }

        $beforeScore = $baselineReport['score'] ?? null;
        $afterScore = $finalReport['score'] ?? null;
        $scoreDelta = is_numeric($beforeScore) && is_numeric($afterScore)
            ? round((float) $afterScore - (float) $beforeScore, 1)
            : null;
        $status = match (true) {
            $acceptedPatch === [] && is_numeric($beforeScore) => 'rejected',
            $missingFacts !== [] => 'need_data',
            default => 'in_review',
        };
        $rejectionReason = $status === 'rejected'
            ? 'Không có phần chỉnh sửa nào làm tăng tổng điểm SEO so với nội dung CMS hiện tại.'
            : null;

        $proposal = DB::transaction(function () use (
            $proposal,
            $user,
            $page,
            $originalContentHash,
            $snapshot,
            $strategyRevision,
            $acceptedPatch,
            $before,
            $missingFacts,
            $warnings,
            $baselineReport,
            $finalReport,
            $scoreDelta,
            $selection,
            $status,
            $rejectionReason,
        ): SeoOptimizationProposal {
            $lockedPage = SeoOptimizationPage::query()->lockForUpdate()->findOrFail($page->id);
            $lockedProposal = SeoOptimizationProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $this->ensure(hash_equals($originalContentHash, (string) $lockedProposal->content_hash), 'Đề xuất đã được chỉnh sửa ở phiên làm việc khác; hãy tải lại trang.');
            $this->ensure(hash_equals((string) $snapshot['version'], $this->registry->currentVersion($lockedPage)), 'Nội dung CMS đã thay đổi trong lúc chấm điểm; hãy tải lại và chấm lại.');
            $this->ensure(hash_equals($strategyRevision, $this->briefs->revision($lockedPage->fresh()->keyword_brief ?? [])), 'Brief SEO đã thay đổi trong lúc chấm điểm; hãy tải lại và chấm lại.');

            $qa = $lockedProposal->qa ?? [];
            $revision = [
                'revised_by' => $user->id,
                'revised_at' => now()->toIso8601String(),
                'decision' => $selection['decision'],
                'score_before' => $baselineReport['score'] ?? null,
                'score_after' => $finalReport['score'] ?? null,
                'score_delta' => $scoreDelta,
                'retained_units' => $selection['retained_units'],
                'ignored_units' => $selection['ignored_units'],
                'dimension_changes' => $this->dimensionChanges($baselineReport, $finalReport),
            ];
            $history = is_array($qa['editor_revisions'] ?? null) ? $qa['editor_revisions'] : [];
            $history[] = $revision;
            $qa = [
                ...$qa,
                'warnings' => $warnings,
                'requires_human' => true,
                'baseline_seo_gate' => $this->scoreSummary($baselineReport),
                'seo_gate' => $finalReport,
                'score_delta' => $scoreDelta,
                'editor_revision' => $revision,
                'editor_revisions' => array_slice($history, -20),
                'semantic_status' => 'stale_after_editor_revision',
                'fact_review_required' => $warnings !== [] || $missingFacts !== [],
                'publish_decision' => $status === 'rejected' ? 'rejected_score_not_improved' : 'awaiting_human_score_gate',
            ];
            unset($qa['approval_hash'], $qa['human_reviewed_at'], $qa['authorization']);

            $lockedProposal->fill([
                'source_version' => $snapshot['version'],
                'strategy_revision' => $strategyRevision,
                'status' => $status,
                'patch' => $acceptedPatch,
                'before' => $before,
                'qa' => $qa,
                'missing_facts' => $missingFacts,
                'content_hash' => $this->hash([$acceptedPatch, $before, $lockedProposal->claims ?? [], $missingFacts]),
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => $rejectionReason,
            ])->save();

            SeoOptimizationEvent::query()->create([
                'page_id' => $lockedPage->id,
                'proposal_id' => $lockedProposal->id,
                'actor_id' => $user->id,
                'event' => 'proposal.editor_rescored',
                'payload' => [
                    'score_before' => $baselineReport['score'] ?? null,
                    'score_after' => $finalReport['score'] ?? null,
                    'score_delta' => $scoreDelta,
                    'retained_units' => array_column($selection['retained_units'], 'key'),
                    'ignored_units' => array_column($selection['ignored_units'], 'key'),
                    'status' => $status,
                ],
            ]);

            return $lockedProposal->refresh();
        });

        $retainedCount = count($selection['retained_units']);
        $ignoredCount = count($selection['ignored_units']);
        if (! $autoApply
            || $proposal->status !== 'in_review'
            || $proposal->missing_facts !== []
            || data_get($proposal->qa, 'editor_revision.decision') === 'score_unavailable_manual_review') {
            return [
                'proposal' => $proposal,
                'retained_count' => $retainedCount,
                'ignored_count' => $ignoredCount,
                'applied' => false,
                'message' => $this->resultMessage($proposal, $retainedCount, $ignoredCount, false),
            ];
        }

        $proposal = $this->workflow->approve($proposal, $user);
        $beforeScore = data_get($proposal->qa, 'baseline_seo_gate.score');
        $afterScore = data_get($proposal->qa, 'seo_gate.score');
        $scoreDelta = is_numeric($beforeScore) && is_numeric($afterScore)
            ? round((float) $afterScore - (float) $beforeScore, 1)
            : null;
        if ($scoreDelta === null || $scoreDelta <= 0) {
            $proposal = $this->reopenAfterRebase($proposal, $user, $scoreDelta);

            return [
                'proposal' => $proposal,
                'retained_count' => $retainedCount,
                'ignored_count' => $ignoredCount,
                'applied' => false,
                'message' => 'Đã chấm lại nhưng điểm không còn tăng trên phiên bản CMS mới nhất; đề xuất được giữ lại để rà soát, chưa áp dụng.',
            ];
        }

        $proposal = $this->authorizeScoreImprovement($proposal, $user, $scoreDelta);
        $proposal = $this->workflow->apply($proposal, $user);

        return [
            'proposal' => $proposal,
            'retained_count' => $retainedCount,
            'ignored_count' => $ignoredCount,
            'applied' => $proposal->applied_at !== null,
            'message' => $this->resultMessage($proposal, $retainedCount, $ignoredCount, $proposal->applied_at !== null),
        ];
    }

    /** @return array{patch: array<string, mixed>, retained_units: array<int, array<string, mixed>>, ignored_units: array<int, array<string, mixed>>, decision: string} */
    private function selectImprovingUnits(array $requestedPatch, array $snapshot, array $brief, array $baselineReport): array
    {
        $baselineScore = $baselineReport['score'] ?? null;
        $units = $this->atomicUnits($requestedPatch);
        if (! is_numeric($baselineScore)) {
            return [
                'patch' => $requestedPatch,
                'retained_units' => [],
                'ignored_units' => [],
                'decision' => 'score_unavailable_manual_review',
            ];
        }

        $acceptedPatch = [];
        $retained = [];
        $currentScore = (float) $baselineScore;

        while ($units !== []) {
            $bestKey = null;
            $bestScore = $currentScore;
            foreach ($units as $key => $unit) {
                $candidatePatch = $this->mergePatch($acceptedPatch, $unit['patch']);
                $report = $this->audits->evaluate($this->audits->candidate($snapshot, $candidatePatch), $brief);
                $score = $report['score'] ?? null;
                if (is_numeric($score) && (float) $score > $bestScore) {
                    $bestKey = $key;
                    $bestScore = (float) $score;
                }
            }

            if ($bestKey === null) {
                break;
            }

            $previousScore = $currentScore;
            $acceptedPatch = $this->mergePatch($acceptedPatch, $units[$bestKey]['patch']);
            $currentScore = $bestScore;
            $retained[] = [
                'key' => $bestKey,
                'score_before' => round($previousScore, 1),
                'score_after' => round($currentScore, 1),
                'score_delta' => round($currentScore - $previousScore, 1),
            ];
            unset($units[$bestKey]);
        }

        $ignored = [];
        foreach ($units as $key => $unit) {
            $report = $this->audits->evaluate(
                $this->audits->candidate($snapshot, $this->mergePatch($acceptedPatch, $unit['patch'])),
                $brief,
            );
            $score = $report['score'] ?? null;
            $ignored[] = [
                'key' => $key,
                'score_if_kept' => $score,
                'score_delta' => is_numeric($score) ? round((float) $score - $currentScore, 1) : null,
                'reason' => is_numeric($score) ? 'Tổng điểm không tăng.' : 'Không đủ dữ liệu để chứng minh điểm tăng.',
            ];
        }

        return [
            'patch' => $acceptedPatch,
            'retained_units' => $retained,
            'ignored_units' => $ignored,
            'decision' => $acceptedPatch === [] ? 'rejected_no_score_gain' : 'retained_score_improvements_only',
        ];
    }

    /** @return array<string, array{patch: array<string, mixed>}> */
    private function atomicUnits(array $patch): array
    {
        $units = [];
        foreach ($patch as $field => $value) {
            if ($field !== ContentWriteContractService::BLOCK_CHANGES) {
                $units[$field] = ['patch' => [$field => $value]];

                continue;
            }

            foreach ($value as $change) {
                foreach ($change['changes'] ?? [] as $blockField => $blockValue) {
                    $key = ContentWriteContractService::BLOCK_CHANGES.'.'.$change['uuid'].'.'.$blockField;
                    $units[$key] = ['patch' => [ContentWriteContractService::BLOCK_CHANGES => [[
                        'uuid' => $change['uuid'],
                        'type' => $change['type'],
                        'changes' => [$blockField => $blockValue],
                    ]]]];
                }
            }
        }

        return $units;
    }

    /** @return array<string, mixed> */
    private function mergePatch(array $base, array $addition): array
    {
        foreach ($addition as $field => $value) {
            if ($field !== ContentWriteContractService::BLOCK_CHANGES) {
                $base[$field] = $value;

                continue;
            }

            foreach ($value as $change) {
                $index = collect($base[$field] ?? [])->search(fn (array $current): bool => (string) ($current['uuid'] ?? '') === (string) ($change['uuid'] ?? '')
                    && (string) ($current['type'] ?? '') === (string) ($change['type'] ?? '')
                );
                if ($index === false) {
                    $base[$field][] = $change;
                } else {
                    $base[$field][$index]['changes'] = [
                        ...($base[$field][$index]['changes'] ?? []),
                        ...($change['changes'] ?? []),
                    ];
                }
            }
        }

        return $base;
    }

    private function assertRequestedScope(array $originalPatch, array $requestedPatch): void
    {
        $this->ensure($requestedPatch === [] || ! array_is_list($requestedPatch), 'Dữ liệu chỉnh sửa phải là object theo tên trường.');
        $this->ensure(array_diff(array_keys($requestedPatch), array_keys($originalPatch)) === [], 'Không được thêm trường ngoài phạm vi đề xuất ban đầu.');
        $originalBlocks = collect($originalPatch[ContentWriteContractService::BLOCK_CHANGES] ?? [])
            ->mapWithKeys(fn (array $change): array => [($change['uuid'] ?? '').'|'.($change['type'] ?? '') => array_keys($change['changes'] ?? [])]);

        $requestedBlocks = $requestedPatch[ContentWriteContractService::BLOCK_CHANGES] ?? [];
        $this->ensure(is_array($requestedBlocks), 'Danh sách thay đổi block không hợp lệ.');
        foreach ($requestedBlocks as $change) {
            $this->ensure(is_array($change) && is_array($change['changes'] ?? null), 'Cấu trúc thay đổi block không hợp lệ.');
            $key = ($change['uuid'] ?? '').'|'.($change['type'] ?? '');
            $this->ensure($originalBlocks->has($key), 'Không được thêm block ngoài phạm vi đề xuất ban đầu.');
            $this->ensure(array_diff(array_keys($change['changes'] ?? []), $originalBlocks->get($key, [])) === [], 'Không được thêm field block ngoài phạm vi đề xuất ban đầu.');
        }
    }

    /** @return array{patch: array<string, mixed>, before: array<string, mixed>, missing_facts: array<int, string>, warnings: array<int, string>} */
    private function validateRequestedPatch(array $patch, array $snapshot, array $brief): array
    {
        if ($patch === []) {
            return ['patch' => [], 'before' => [], 'missing_facts' => [], 'warnings' => []];
        }

        try {
            return $this->patches->validate($patch, $snapshot, $brief);
        } catch (ValidationException $exception) {
            if (($exception->errors()['patch'][0] ?? null) === 'Nội dung đề xuất không khác dữ liệu hiện tại.') {
                return ['patch' => [], 'before' => [], 'missing_facts' => [], 'warnings' => []];
            }

            throw $exception;
        }
    }

    private function authorizeScoreImprovement(SeoOptimizationProposal $proposal, User $user, float $scoreDelta): SeoOptimizationProposal
    {
        return DB::transaction(function () use ($proposal, $user, $scoreDelta): SeoOptimizationProposal {
            $proposal = SeoOptimizationProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $this->ensure($proposal->status === 'approved' && $proposal->approved_by === $user->id, 'Đề xuất không còn ở trạng thái đã duyệt để tự áp dụng.');
            $qa = $proposal->qa ?? [];
            $qa['requires_human'] = false;
            $qa['score_delta'] = $scoreDelta;
            $qa['publish_decision'] = 'human_editor_score_improved';
            $qa['authorization'] = [
                'kind' => 'human_score_improvement',
                'authorized_by' => $user->id,
                'authorized_at' => now()->toIso8601String(),
            ];
            $proposal->update(['qa' => $qa]);

            return $proposal->refresh();
        });
    }

    private function reopenAfterRebase(SeoOptimizationProposal $proposal, User $user, ?float $scoreDelta): SeoOptimizationProposal
    {
        return DB::transaction(function () use ($proposal, $user, $scoreDelta): SeoOptimizationProposal {
            $proposal = SeoOptimizationProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $qa = $proposal->qa ?? [];
            $qa['requires_human'] = true;
            $qa['score_delta'] = $scoreDelta;
            $qa['publish_decision'] = 'preview_score_not_improved_after_rebase';
            unset($qa['approval_hash'], $qa['authorization']);
            $proposal->update(['status' => 'in_review', 'approved_by' => null, 'approved_at' => null, 'qa' => $qa]);
            SeoOptimizationEvent::query()->create([
                'page_id' => $proposal->page_id,
                'proposal_id' => $proposal->id,
                'actor_id' => $user->id,
                'event' => 'proposal.auto_apply_skipped',
                'payload' => ['reason' => 'score_not_improved_after_rebase', 'score_delta' => $scoreDelta],
            ]);

            return $proposal->refresh();
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function dimensionChanges(array $before, array $after): array
    {
        $beforeDimensions = collect($before['dimensions'] ?? [])->keyBy('dimension');

        return collect($after['dimensions'] ?? [])->map(function (array $dimension) use ($beforeDimensions): array {
            $previous = $beforeDimensions->get($dimension['dimension'], []);
            $beforeScore = $previous['score'] ?? null;
            $afterScore = $dimension['score'] ?? null;

            return [
                'dimension' => $dimension['dimension'],
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'score_delta' => is_numeric($beforeScore) && is_numeric($afterScore)
                    ? round((float) $afterScore - (float) $beforeScore, 1)
                    : null,
            ];
        })->values()->all();
    }

    /** @return array<string, mixed> */
    private function scoreSummary(array $report): array
    {
        $summary = collect($report)->only([
            'status', 'score', 'grade', 'score_band', 'acceptance_status', 'assessed_dimensions',
            'total_dimensions', 'assessment_coverage', 'missing_dimensions', 'rule_version', 'scope',
        ])->all();
        $summary['dimensions'] = collect($report['dimensions'] ?? [])->map(fn (array $dimension): array => collect($dimension)->only([
            'rule_id', 'dimension', 'weight', 'score', 'status', 'evidence',
        ])->all())->values()->all();

        return $summary;
    }

    private function resultMessage(SeoOptimizationProposal $proposal, int $retained, int $ignored, bool $applied): string
    {
        if ($applied) {
            return "Đã giữ {$retained} phần làm tăng điểm, bỏ qua {$ignored} phần không tăng điểm và áp dụng lên CMS kèm backup.";
        }
        if ($proposal->status === 'rejected') {
            return "Không có phần nào làm tăng tổng điểm; đã bỏ qua {$ignored} phần và không thay đổi CMS.";
        }
        if ($proposal->status === 'need_data') {
            return "Đã giữ {$retained} phần có lợi và bỏ qua {$ignored} phần không tăng điểm; còn dữ kiện cần xác minh nên chưa áp dụng.";
        }
        if (data_get($proposal->qa, 'editor_revision.decision') === 'score_unavailable_manual_review') {
            return 'Đã lưu nội dung chỉnh sửa nhưng chưa đủ dữ liệu để tính tổng điểm; chưa tự áp dụng.';
        }

        return "Đã chấm lại: giữ {$retained} phần làm tăng điểm, bỏ qua {$ignored} phần không tăng điểm. Đề xuất đang chờ duyệt.";
    }

    private function hash(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function humanOnly(): void
    {
        abort_if(request()->attributes->has('seo_optimization_credential'), 403, 'MCP không được sửa, duyệt hoặc áp dụng đề xuất trong CMS.');
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['proposal' => $message]);
        }
    }
}
