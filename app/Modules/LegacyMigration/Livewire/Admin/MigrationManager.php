<?php

namespace App\Modules\LegacyMigration\Livewire\Admin;

use App\Models\User;
use App\Modules\LegacyMigration\Jobs\ProcessLegacyMigrationUrl;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Modules\LegacyMigration\Services\LegacyAutomaticTargetResolver;
use App\Modules\LegacyMigration\Services\LegacyContentCaster;
use App\Modules\LegacyMigration\Services\LegacyMediaRetry;
use App\Modules\LegacyMigration\Services\LegacyPreservedUrlRedirector;
use App\Modules\LegacyMigration\Services\LegacyQueueRecovery;
use App\Modules\LegacyMigration\Services\LegacyRunCounter;
use App\Modules\LegacyMigration\Services\LegacyTargetRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Chuyển dữ liệu website cũ')]
class MigrationManager extends Component
{
    use WithPagination;

    private const PROCESSABLE_STATUSES = ['pending', 'needs_review', 'failed'];

    public ?LegacyMigrationRun $run = null;

    public string $search = '';

    public string $status = '';

    public string $objectType = '';

    public string $eligibilityFilter = '';

    #[Locked]
    public string $failureFilter = '';

    public string $automaticSourceType = 'blog';

    public string $automaticStrategy = 'create_new';

    public string $automaticMappingMode = 'cast_and_redirect';

    public string $automaticMergePolicy = 'overwrite';

    public bool $automaticImportMedia = true;

    /** @var array<int, string> */
    public array $selectedUrlIds = [];

    public ?int $selectedUrlId = null;

    public bool $mappingModalOpen = false;

    public bool $bulkProcessingModalOpen = false;

    public string $mappingMode = 'cast_and_redirect';

    public string $mergePolicy = 'fill_blanks';

    public string $targetType = '';

    public string $targetId = '';

    public string $targetSearch = '';

    public int $redirectCode = 301;

    public function mount(?LegacyMigrationRun $run = null): void
    {
        $this->authorizeSuperAdmin();
        $this->run = $run;
    }

    public function updated(string $property): void
    {
        if (($property === 'status' && $this->status !== 'failed') || $property === 'eligibilityFilter') {
            $this->failureFilter = '';
            $this->resetValidation('failureFilter');
        }

        if ($property === 'eligibilityFilter' && $this->eligibilityFilter !== '') {
            $this->status = '';
            $this->objectType = '';
        }

        if ($property === 'automaticSourceType' && $this->eligibilityFilter !== '') {
            $this->status = '';
            $this->objectType = '';
        }

        if (in_array($property, ['search', 'status', 'objectType', 'eligibilityFilter', 'automaticSourceType'], true)) {
            $this->resetPage();
            $this->clearSelection();
        }

        if ($property === 'targetType') {
            $this->targetId = '';
            $this->targetSearch = '';
        }
    }

    public function updatedAutomaticSourceType(): void
    {
        if ($this->automaticSourceType === 'blog') {
            $this->automaticStrategy = 'create_new';
            $this->automaticMergePolicy = 'overwrite';
            $this->automaticImportMedia = true;

            return;
        }

        $this->automaticStrategy = 'match_slug';
        $this->automaticMergePolicy = 'fill_blanks';
        $this->automaticImportMedia = false;
    }

    public function updatedAutomaticStrategy(): void
    {
        $this->automaticMergePolicy = $this->automaticStrategy === 'create_new' ? 'overwrite' : 'fill_blanks';
    }

    public function selectUrl(int $urlId): void
    {
        $this->authorizeSuperAdmin();
        $url = $this->runUrl($urlId);
        $this->selectedUrlId = $url->id;
        $this->mappingMode = $url->mapping_mode ?: 'cast_and_redirect';
        $this->mergePolicy = $url->merge_policy ?: 'fill_blanks';
        $this->targetType = $url->target_type ?: '';
        $this->targetId = $url->target_id ?: '';
        $this->redirectCode = $url->redirect_code ?: 301;
        $this->targetSearch = '';
        $this->resetValidation();
        $this->mappingModalOpen = true;
        $this->modal('legacy-url-mapping')->show();
    }

    public function toggleUrlSelection(int $urlId, LegacyAutomaticTargetResolver $automation, LegacyMediaRetry $mediaRetry): void
    {
        $this->authorizeSuperAdmin();
        $url = $this->isMediaRetryFilter()
            ? $this->mediaRetryUrlQuery($mediaRetry)->find($urlId)
            : $this->selectableUrl($urlId, $automation);

        if (! $url) {
            return;
        }

        $id = (string) $url->id;
        $selected = $this->normalizedSelectedUrlIds();
        $this->selectedUrlIds = in_array((int) $url->id, $selected, true)
            ? array_values(array_filter($this->selectedUrlIds, fn (string|int $selectedId): bool => (int) $selectedId !== $url->id))
            : [...$this->selectedUrlIds, $id];
    }

    /** @param array<int, int|string> $urlIds */
    public function togglePageSelection(array $urlIds, LegacyAutomaticTargetResolver $automation, LegacyMediaRetry $mediaRetry): void
    {
        $this->authorizeSuperAdmin();

        if (! $this->run) {
            abort(404);
        }

        $requestedIds = collect($urlIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(100)
            ->values();
        $pageQuery = $this->isMediaRetryFilter()
            ? $this->mediaRetryUrlQuery($mediaRetry)
            : LegacyStagedUrl::query()
                ->where('run_id', $this->run->id)
                ->whereIn('status', self::PROCESSABLE_STATUSES)
                ->where(function (Builder $query) use ($automation): void {
                    foreach (array_keys($automation->sourceTypeLabels()) as $index => $sourceType) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->{$method}(fn (Builder $sourceQuery) => $this->constrainToSourceType($sourceQuery, $sourceType, true));
                    }
                });
        $pageIds = $pageQuery->whereIn('id', $requestedIds)->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $selected = $this->normalizedSelectedUrlIds();
        $allSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];

        $this->selectedUrlIds = $allSelected
            ? array_values(array_filter($this->selectedUrlIds, fn (string|int $id): bool => ! in_array((int) $id, $pageIds, true)))
            : array_values(array_unique([...$this->selectedUrlIds, ...array_map('strval', $pageIds)]));
    }

    public function isUrlSelected(int|string $urlId): bool
    {
        return in_array((int) $urlId, $this->normalizedSelectedUrlIds(), true);
    }

    public function clearSelection(): void
    {
        $this->selectedUrlIds = [];
        $this->resetValidation('bulkSelection');
    }

    public function filterFailedUrls(): void
    {
        $this->authorizeSuperAdmin();
        abort_unless($this->run, 404);
        $this->search = '';
        $this->status = 'failed';
        $this->objectType = '';
        $this->eligibilityFilter = '';
        $this->failureFilter = '';
        $this->resetValidation('failureFilter');
        $this->resetPage();
        $this->clearSelection();
    }

    public function filterFailureGroup(int $urlId, string $errorHash): void
    {
        $this->authorizeSuperAdmin();
        $url = $this->runUrl($urlId);

        if ($url->status !== 'failed' || ! filled($url->error_text)
            || ! hash_equals(hash('sha256', (string) $url->error_text), $errorHash)) {
            $this->addError('failureFilter', 'Nhóm lỗi đã thay đổi hoặc URL đã được xử lý. Hãy chọn lại nhóm lỗi hiện tại.');

            return;
        }

        $this->filterFailedUrls();
        $this->failureFilter = (string) $url->error_text;
    }

    public function clearFailureFilter(): void
    {
        $this->filterFailedUrls();
    }

    public function openBulkProcessing(LegacyAutomaticTargetResolver $automation): void
    {
        $this->authorizeSuperAdmin();

        if (! $this->run) {
            abort(404);
        }

        if ($this->run->status === 'receiving') {
            session()->flash('error', 'Phiên vẫn đang nhận chunk. Hãy finalize trước khi xử lý hàng loạt.');

            return;
        }

        $sourceType = $this->selectedSourceType($automation);

        if ($sourceType === null) {
            return;
        }

        $this->automaticSourceType = $sourceType;
        $this->updatedAutomaticSourceType();
        $this->resetValidation();
        $this->bulkProcessingModalOpen = true;
        $this->modal('legacy-bulk-processing')->show();
    }

    public function saveMapping(LegacyTargetRegistry $targets, LegacyRunCounter $counter): void
    {
        $actor = $this->authorizeSuperAdmin();
        $url = $this->persistSelectedMapping($actor, $targets, $counter);

        if (! $url) {
            return;
        }

        $this->forgetSelectedUrl($url->id);
        session()->flash(
            'status',
            $url->status === 'blocked'
                ? 'Đã đánh dấu URL là blocked; không cast và không redirect.'
                : 'Đã lưu mapping. Dữ liệu chưa thay đổi cho tới khi bấm xử lý URL này.',
        );
    }

    public function saveAndProcessSelected(
        LegacyTargetRegistry $targets,
        LegacyRunCounter $counter,
        LegacyContentCaster $caster,
    ): void {
        $actor = $this->authorizeSuperAdmin();
        $url = $this->persistSelectedMapping($actor, $targets, $counter);

        if (! $url) {
            return;
        }

        if ($url->status === 'blocked') {
            $this->forgetSelectedUrl($url->id);
            session()->flash('status', 'Đã đánh dấu URL là blocked; không cast và không redirect.');
            $this->mappingModalOpen = false;
            $this->modal('legacy-url-mapping')->close();

            return;
        }

        $this->processSelectedUrl($url, $actor, $caster);
    }

    private function persistSelectedMapping(
        User $actor,
        LegacyTargetRegistry $targets,
        LegacyRunCounter $counter,
    ): ?LegacyStagedUrl {
        $url = $this->selectedUrl();

        if ($url->run->status === 'receiving') {
            $this->addError('targetType', 'Phiên đang nhận dữ liệu; chỉ mapping sau khi finalize thành công.');

            return null;
        }

        if (in_array($url->status, ['queued', 'processing'], true)) {
            $this->addError('mappingMode', 'URL đang trong hàng đợi xử lý; không thể thay đổi mapping lúc này.');

            return null;
        }

        $this->validate([
            'mappingMode' => ['required', Rule::in(['cast_preserve_url', 'cast_and_redirect', 'cast_only', 'redirect_only', 'blocked'])],
            'mergePolicy' => ['required', Rule::in(['fill_blanks', 'overwrite'])],
            'targetType' => [Rule::requiredIf($this->mappingMode !== 'blocked'), 'nullable', Rule::in(array_keys($targets->typeLabels()))],
            'targetId' => [Rule::requiredIf($this->mappingMode !== 'blocked'), 'nullable', 'string', 'max:190'],
            'redirectCode' => ['required', Rule::in([301, 302, 307])],
        ]);

        if ($this->mappingMode === 'blocked') {
            try {
                DB::transaction(function () use ($url, $actor): void {
                    $lockedUrl = LegacyStagedUrl::query()
                        ->where('run_id', $url->run_id)
                        ->lockForUpdate()
                        ->findOrFail($url->id);

                    if (in_array($lockedUrl->status, ['queued', 'processing'], true)) {
                        throw new InvalidArgumentException('URL đã được đưa vào hàng đợi xử lý.');
                    }

                    $lockedUrl->forceFill([
                        'mapping_mode' => 'blocked',
                        'target_type' => null,
                        'target_id' => null,
                        'target_route' => null,
                        'target_path' => null,
                        'status' => 'blocked',
                        'mapped_by' => $actor->id,
                        'mapped_at' => now(),
                        'casted_at' => null,
                        'error_text' => null,
                    ])->save();
                });
            } catch (InvalidArgumentException $exception) {
                $this->addError('mappingMode', $exception->getMessage());

                return null;
            }
            $counter->refresh($url->run);

            return $url->refresh();
        }

        $target = $this->targetType === 'system_route'
            ? $this->targetId
            : $targets->resolve($this->targetType, $this->targetId);
        $targetPath = $targets->path($this->targetType, $target);
        $root = $url->rootObject();

        if (str_starts_with($this->mappingMode, 'cast_')) {
            if ($this->targetType === 'system_route') {
                $this->addError('targetType', 'Trang hệ thống chỉ hỗ trợ redirect; không có model để cast timestamp.');

                return null;
            }

            if (! $root) {
                $this->addError('targetType', 'URL chưa có root object để cast.');

                return null;
            }

            if (! $targets->isCompatible($root->object_type, $this->targetType)) {
                $this->addError('targetType', 'Loại page/model đích không tương thích root object nguồn.');

                return null;
            }
        }

        try {
            DB::transaction(function () use ($url, $actor, $targetPath): void {
                $lockedUrl = LegacyStagedUrl::query()
                    ->where('run_id', $url->run_id)
                    ->lockForUpdate()
                    ->findOrFail($url->id);

                if (in_array($lockedUrl->status, ['queued', 'processing'], true)) {
                    throw new InvalidArgumentException('URL đã được đưa vào hàng đợi xử lý.');
                }

                $lockedUrl->forceFill([
                    'mapping_mode' => $this->mappingMode,
                    'merge_policy' => $this->mergePolicy,
                    'timestamp_policy' => str_starts_with($this->mappingMode, 'cast_') ? 'source' : 'preserve',
                    'target_type' => $this->targetType,
                    'target_id' => $this->targetId,
                    'target_route' => $this->targetType === 'system_route' ? $this->targetId : $this->targetType,
                    'target_path' => $targetPath,
                    'redirect_code' => $this->redirectCode,
                    'status' => 'mapped',
                    'mapped_by' => $actor->id,
                    'mapped_at' => now(),
                    'casted_at' => null,
                    'error_text' => null,
                ])->save();
            });
        } catch (InvalidArgumentException $exception) {
            $this->addError('mappingMode', $exception->getMessage());

            return null;
        }
        $counter->refresh($url->run);

        return $url->refresh();
    }

    public function castSelected(LegacyContentCaster $caster): void
    {
        $actor = $this->authorizeSuperAdmin();
        $url = $this->selectedUrl();

        $this->processSelectedUrl($url, $actor, $caster);
    }

    private function processSelectedUrl(LegacyStagedUrl $url, User $actor, LegacyContentCaster $caster): void
    {
        try {
            $caster->cast($url, $actor, importMedia: true);
            session()->flash(
                'status',
                $url->mapping_mode === 'redirect_only'
                    ? 'Đã tạo redirect tới page được chọn; dữ liệu và timestamp đích không thay đổi.'
                    : 'Đã cast mapping và giữ timestamp nguồn thành công.',
            );
            $this->forgetSelectedUrl($url->id);
            $this->mappingModalOpen = false;
            $this->modal('legacy-url-mapping')->close();
        } catch (Throwable $exception) {
            report($exception);
            $message = $url->fresh()->error_text ?: 'Cast thất bại. Chi tiết kỹ thuật đã được ghi vào audit.';
            session()->flash('error', $message);
        }
    }

    public function queueAutomaticProcessing(LegacyAutomaticTargetResolver $automation, LegacyRunCounter $counter): void
    {
        $actor = $this->authorizeSuperAdmin();

        if (! $this->run) {
            abort(404);
        }

        if ($this->run->status === 'receiving') {
            session()->flash('error', 'Phiên vẫn đang nhận chunk. Hãy finalize trước khi xử lý tự động.');

            return;
        }

        if (! $this->validateAutomaticSettings($automation)) {
            return;
        }

        $urlIds = $this->automaticUrlQuery($this->automaticSourceType)
            ->orderByDesc('clicks')
            ->orderBy('id')
            ->pluck('id');
        $queued = $this->queueUrlIds($urlIds->all(), $actor);

        $this->run = $counter->refresh($this->run);
        session()->flash(
            $queued > 0 ? 'status' : 'error',
            $queued > 0
                ? "Đã đưa {$queued} URL vào hàng đợi xử lý tự động."
                : 'Không có URL phù hợp ở trạng thái chờ hoặc lỗi.',
        );
    }

    public function queueMediaRetry(LegacyMediaRetry $mediaRetry, LegacyRunCounter $counter, bool $selectedOnly = false): void
    {
        $actor = $this->authorizeSuperAdmin();
        abort_unless($this->run, 404);
        $this->validate(['eligibilityFilter' => ['required', Rule::in(['media_review', 'image_count_limit'])]]);

        if ($this->run->fresh()->status === 'receiving') {
            $this->addError('mediaRetry', 'Phiên vẫn đang nhận dữ liệu; hãy finalize trước khi tải lại ảnh.');

            return;
        }

        $query = $this->mediaRetryUrlQuery($mediaRetry);
        $selectedIds = $this->normalizedSelectedUrlIds();
        if ($selectedOnly) {
            if ($selectedIds === []) {
                $this->addError('mediaRetry', 'Hãy chọn ít nhất một URL cần tải lại ảnh.');

                return;
            }
            $query->whereIn('id', $selectedIds);
        }
        $ids = $query->orderBy('id')->pluck('id')->all();
        if ($selectedOnly && count($ids) !== count($selectedIds)) {
            $this->addError('mediaRetry', 'Danh sách đã chọn không còn hợp lệ hoặc có URL ngoài bộ lọc/phiên hiện tại. Hãy chọn lại.');

            return;
        }

        try {
            $result = $mediaRetry->queue($this->run, $actor, $ids);
        } catch (InvalidArgumentException $exception) {
            $this->addError('mediaRetry', $exception->getMessage());

            return;
        }
        $this->run = $counter->refresh($this->run);
        $this->clearSelection();
        $queued = $result['queued'];
        $skipped = $result['skipped'];
        session()->flash($queued > 0 ? 'status' : 'error', $queued > 0
            ? "Đã đưa {$queued} URL vào queue tải lại ảnh bằng target cũ; bỏ qua {$skipped} URL đã thay đổi, còn khóa xử lý hoặc target không còn hợp lệ."
            : 'Không có URL đã cast với ảnh cần kiểm tra và target hợp lệ để tải lại.');
    }

    public function queueSelectedProcessing(LegacyAutomaticTargetResolver $automation, LegacyRunCounter $counter): void
    {
        $actor = $this->authorizeSuperAdmin();

        if (! $this->run) {
            abort(404);
        }

        if ($this->run->status === 'receiving') {
            $this->addError('bulkSelection', 'Phiên vẫn đang nhận chunk. Hãy finalize trước khi xử lý hàng loạt.');

            return;
        }

        $sourceType = $this->selectedSourceType($automation);

        if ($sourceType === null) {
            return;
        }

        if ($sourceType !== $this->automaticSourceType) {
            $this->addError('bulkSelection', 'Loại dữ liệu của danh sách đã chọn đã thay đổi. Hãy đóng popup và chọn lại.');

            return;
        }

        if (! $this->validateAutomaticSettings($automation)) {
            return;
        }
        $urlIds = $this->constrainToSourceType(
            $this->selectedUrlQuery(),
            $this->automaticSourceType,
            true,
        )->pluck('legacy_staged_urls.id')->all();

        if (count($urlIds) !== count($this->normalizedSelectedUrlIds())) {
            $this->addError('bulkSelection', 'Một số URL đã chọn không còn hợp lệ hoặc đã được xử lý. Hãy tải lại danh sách và chọn lại.');

            return;
        }

        $queued = $this->queueUrlIds($urlIds, $actor, true);

        if ($queued !== count($urlIds)) {
            $this->addError('bulkSelection', 'Danh sách vừa thay đổi do tiến trình khác. Chưa URL nào trong lần submit này được đưa vào queue.');

            return;
        }

        $this->run = $counter->refresh($this->run);
        $this->clearSelection();
        $this->bulkProcessingModalOpen = false;
        $this->modal('legacy-bulk-processing')->close();
        session()->flash('status', "Đã đưa {$queued} URL đã chọn vào hàng đợi xử lý.");
    }

    /** @param array<int, int|string> $urlIds */
    private function queueUrlIds(array $urlIds, User $actor, bool $requireEveryUrl = false): int
    {
        $requestedIds = collect($urlIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($requestedIds === []) {
            return 0;
        }

        return DB::transaction(function () use ($requestedIds, $actor, $requireEveryUrl): int {
            $claimQuery = LegacyStagedUrl::query()
                ->whereIn('id', $requestedIds)
                ->where('run_id', $this->run?->id)
                ->whereIn('status', self::PROCESSABLE_STATUSES);
            $claimedIds = $this->constrainToSourceType($claimQuery, $this->automaticSourceType, true)
                ->lockForUpdate()
                ->pluck('legacy_staged_urls.id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            if ($requireEveryUrl && count($claimedIds) !== count($requestedIds)) {
                return 0;
            }

            if ($claimedIds === []) {
                return 0;
            }

            LegacyStagedUrl::query()
                ->whereIn('id', $claimedIds)
                ->update([
                    'status' => 'queued',
                    'mapping_mode' => $this->automaticMappingMode,
                    'merge_policy' => $this->automaticMergePolicy,
                    'timestamp_policy' => 'source',
                    'mapped_by' => $actor->id,
                    'error_text' => null,
                    'updated_at' => now(),
                ]);

            foreach ($claimedIds as $urlId) {
                ProcessLegacyMigrationUrl::dispatch(
                    $urlId,
                    (int) $actor->id,
                    $this->automaticSourceType,
                    $this->automaticStrategy,
                    $this->automaticMappingMode,
                    $this->automaticMergePolicy,
                    $this->automaticImportMedia,
                )->afterCommit();

            }

            return count($claimedIds);
        });
    }

    public function recoverStuckUrls(LegacyQueueRecovery $recovery, LegacyRunCounter $counter, LegacyAutomaticTargetResolver $automation): void
    {
        $actor = $this->authorizeSuperAdmin();
        abort_unless($this->run, 404);
        $this->validate([
            'automaticSourceType' => ['required', Rule::in(array_keys($automation->sourceTypeLabels()))],
            'automaticImportMedia' => ['boolean'],
        ]);

        $this->recoverUrls($recovery, $counter, $actor, $this->automaticSourceType);
    }

    public function recoverStuckUrl(int $urlId, LegacyQueueRecovery $recovery, LegacyRunCounter $counter, LegacyAutomaticTargetResolver $automation): void
    {
        $actor = $this->authorizeSuperAdmin();
        $url = $this->runUrl($urlId);
        $sourceType = $url->rootObject()?->object_type;

        if (! $sourceType || ! array_key_exists($sourceType, $automation->sourceTypeLabels())) {
            $this->addError('queueRecovery', 'URL không có root object thuộc loại được hỗ trợ.');

            return;
        }

        $this->recoverUrls($recovery, $counter, $actor, $sourceType, $url->id);
    }

    private function recoverUrls(LegacyQueueRecovery $recovery, LegacyRunCounter $counter, User $actor, string $sourceType, ?int $urlId = null): void
    {
        $this->resetValidation('queueRecovery');

        try {
            $result = $recovery->recover($this->run->fresh(), $actor, $sourceType, $this->automaticImportMedia, $urlId);
        } catch (InvalidArgumentException $exception) {
            $this->addError('queueRecovery', $exception->getMessage());

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('queueRecovery', 'Không thể hoàn tất khôi phục. Kiểm tra log server trước khi thử lại; job đã tạo thành công sẽ không được tạo trùng.');

            return;
        }

        $this->run = $counter->refresh($this->run);
        $this->clearSelection();
        session()->flash('status', sprintf(
            'Đã khôi phục và đưa lại %d URL vào queue. Bỏ qua: %d URL vẫn có job (cần worker xử lý), %d URL đang chạy hoặc vừa cập nhật, %d URL thiếu target hoặc cấu hình hợp lệ. Không import lại hay tạo bản ghi CMS mới.',
            $result['recovered'], $result['existing_jobs'], $result['active'], $result['invalid'],
        ));

        if ($result['errors'] !== []) {
            $this->addError('queueRecovery', implode(' ', $result['errors']));
        }
    }

    public function convertPreservedUrlsToRedirects(LegacyPreservedUrlRedirector $redirector, LegacyRunCounter $counter): void
    {
        $actor = $this->authorizeSuperAdmin();
        abort_unless($this->run, 404);
        $this->resetValidation('preservedRedirect');

        if ($this->run->status === 'receiving') {
            $this->addError('preservedRedirect', 'Phiên chưa finalize; chưa thể chuyển URL cũ sang redirect.');

            return;
        }

        try {
            $result = $redirector->convert($this->run->fresh(), $actor);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('preservedRedirect', 'Không thể chuyển mapping URL. Kiểm tra log server trước khi thử lại.');

            return;
        }

        $this->run = $counter->refresh($this->run);
        session()->flash('status', sprintf(
            'Đã chuyển %d URL nguồn sang redirect 301 về target hiện có. Bỏ qua: %d; lỗi: %d. Không import lại, không tạo bài mới và không đổi content/media/timestamp.',
            $result['converted'], $result['skipped'], $result['failed'],
        ));

        if ($result['errors'] !== []) {
            $this->addError('preservedRedirect', implode(' ', $result['errors']));
        }
    }

    public function render(LegacyTargetRegistry $targets, LegacyRunCounter $counter, LegacyAutomaticTargetResolver $automation, LegacyQueueRecovery $recovery, LegacyMediaRetry $mediaRetry, LegacyPreservedUrlRedirector $redirector)
    {
        $this->authorizeSuperAdmin();

        if (! $this->run) {
            return view('legacy-migration::admin.index', [
                'runs' => LegacyMigrationRun::query()->latest('id')->paginate(20),
                'targetTypes' => $targets->typeLabels(),
                'targetOptions' => [],
                'urls' => null,
                'selectedUrl' => null,
                'selectedObject' => null,
                'automaticSourceTypes' => $automation->sourceTypeLabels(),
                'automaticStrategies' => $automation->strategyLabels($this->automaticSourceType),
                'automaticEligibleCount' => 0,
                'eligibilityCounts' => $this->emptyEligibilityCounts(),
                'failureGroups' => collect(),
                'selectedFailureAudits' => collect(),
                'selectedCount' => 0,
                'processableStatuses' => self::PROCESSABLE_STATUSES,
                'queueRecovery' => null,
                'preservedUrlRedirectCount' => 0,
            ]);
        }

        $this->run = $counter->refresh($this->run);
        $urls = $this->filteredUrlQuery()
            ->with('latestMediaAudit')
            ->select('legacy_staged_urls.*')
            ->addSelect([
                'root_object_type' => LegacyStagedObject::query()
                    ->select('object_type')
                    ->whereColumn('legacy_staged_objects.run_id', 'legacy_staged_urls.run_id')
                    ->whereColumn('legacy_staged_objects.object_key', 'legacy_staged_urls.root_object_key')
                    ->limit(1),
                'root_is_partial' => LegacyStagedObject::query()
                    ->select('is_partial')
                    ->whereColumn('legacy_staged_objects.run_id', 'legacy_staged_urls.run_id')
                    ->whereColumn('legacy_staged_objects.object_key', 'legacy_staged_urls.root_object_key')
                    ->limit(1),
            ])
            ->orderByDesc('clicks')
            ->orderBy('id')
            ->paginate(25);
        $selectedUrl = $this->selectedUrlId
            ? LegacyStagedUrl::query()->with('latestMediaAudit')->where('run_id', $this->run->id)->find($this->selectedUrlId)
            : null;
        $failureGroups = LegacyStagedUrl::query()
            ->where('run_id', $this->run->id)
            ->where('status', 'failed')
            ->whereNotNull('error_text')
            ->where('error_text', '<>', '')
            ->select('error_text')
            ->selectRaw('MIN(id) as sample_url_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('MAX(updated_at) as last_failed_at')
            ->groupBy('error_text')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        $selectedFailureAudits = $selectedUrl
            ? LegacyCastAudit::query()
                ->where('staged_url_id', $selectedUrl->id)
                ->where('status', 'failed')
                ->latest('id')
                ->limit(20)
                ->get(['id', 'action', 'error_text', 'after_json', 'created_at'])
            : collect();
        $mediaRetryIds = $this->isMediaRetryFilter()
            ? $this->mediaRetryUrlQuery($mediaRetry)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()
            : [];

        return view('legacy-migration::admin.index', [
            'runs' => null,
            'urls' => $urls,
            'selectedUrl' => $selectedUrl,
            'selectedObject' => $selectedUrl?->rootObject(),
            'targetTypes' => $targets->typeLabels(),
            'targetOptions' => $this->targetType !== '' ? $targets->options($this->targetType, $this->targetSearch) : [],
            'automaticSourceTypes' => $automation->sourceTypeLabels(),
            'automaticStrategies' => $automation->strategyLabels($this->automaticSourceType),
            'automaticEligibleCount' => $this->automaticUrlQuery($this->automaticSourceType)->count(),
            'eligibilityCounts' => $this->eligibilityCounts($this->automaticSourceType),
            'failureGroups' => $failureGroups,
            'selectedFailureAudits' => $selectedFailureAudits,
            'selectedCount' => count($this->normalizedSelectedUrlIds()),
            'processableStatuses' => self::PROCESSABLE_STATUSES,
            'queueRecovery' => $recovery->summary($this->run, $this->automaticSourceType),
            'preservedUrlRedirectCount' => $redirector->count($this->run),
            'mediaRetryMode' => $this->isMediaRetryFilter(),
            'mediaRetryCount' => count($mediaRetryIds),
            'pageMediaRetryIds' => array_values(array_intersect($urls->pluck('id')->all(), $mediaRetryIds)),
            'mediaRetryUnavailableReason' => $this->isMediaRetryFilter() ? $mediaRetry->unavailableReason() : null,
        ]);
    }

    private function validateAutomaticSettings(LegacyAutomaticTargetResolver $automation): bool
    {
        $sourceTypes = array_keys($automation->sourceTypeLabels());
        $strategies = array_keys($automation->strategyLabels($this->automaticSourceType));
        $this->validate([
            'automaticSourceType' => ['required', Rule::in($sourceTypes)],
            'automaticStrategy' => ['required', Rule::in($strategies)],
            'automaticMappingMode' => ['required', Rule::in(['cast_preserve_url', 'cast_and_redirect', 'cast_only'])],
            'automaticMergePolicy' => ['required', Rule::in(['fill_blanks', 'overwrite'])],
            'automaticImportMedia' => ['boolean'],
        ]);

        if ($this->automaticImportMedia && $this->automaticSourceType !== 'blog') {
            $this->addError('automaticImportMedia', 'Tải Media tự động hiện chỉ áp dụng cho bài blog.');

            return false;
        }

        return true;
    }

    private function selectedSourceType(LegacyAutomaticTargetResolver $automation): ?string
    {
        $selectedIds = $this->normalizedSelectedUrlIds();

        if ($selectedIds === []) {
            $this->addError('bulkSelection', 'Hãy chọn ít nhất một URL cần xử lý.');

            return null;
        }

        $urls = $this->selectedUrlQuery()->get(['id', 'root_object_key']);

        if ($urls->count() !== count($selectedIds)) {
            $this->addError('bulkSelection', 'Danh sách có URL không thuộc phiên này hoặc không còn ở trạng thái có thể xử lý.');

            return null;
        }

        $roots = LegacyStagedObject::query()
            ->where('run_id', $this->run?->id)
            ->whereIn('object_key', $urls->pluck('root_object_key')->filter()->all())
            ->get()
            ->keyBy('object_key');
        $sourceTypes = [];
        $supportedTypes = array_keys($automation->sourceTypeLabels());

        foreach ($urls as $url) {
            $root = $roots->get($url->root_object_key);

            if (! $root || $root->is_partial || ! in_array($root->object_type, $supportedTypes, true)) {
                $this->addError('bulkSelection', 'Mỗi URL đã chọn phải có root object đầy đủ thuộc loại được hỗ trợ.');

                return null;
            }

            $sourceTypes[$root->object_type] = true;
        }

        if (count($sourceTypes) !== 1) {
            $this->addError('bulkSelection', 'Chỉ xử lý đồng loạt các URL cùng một loại dữ liệu. Hãy lọc theo loại rồi chọn lại.');

            return null;
        }

        return (string) array_key_first($sourceTypes);
    }

    private function selectedUrlQuery(): Builder
    {
        return LegacyStagedUrl::query()
            ->where('run_id', $this->run?->id)
            ->whereIn('id', $this->normalizedSelectedUrlIds())
            ->whereIn('status', self::PROCESSABLE_STATUSES);
    }

    /** @return array<int, int> */
    private function normalizedSelectedUrlIds(): array
    {
        return collect($this->selectedUrlIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(5000)
            ->values()
            ->all();
    }

    private function forgetSelectedUrl(int $urlId): void
    {
        $this->selectedUrlIds = array_values(array_filter(
            $this->selectedUrlIds,
            fn (string|int $selectedId): bool => (int) $selectedId !== $urlId,
        ));
    }

    private function selectableUrl(int $urlId, LegacyAutomaticTargetResolver $automation): ?LegacyStagedUrl
    {
        if (! $this->run) {
            abort(404);
        }

        $query = LegacyStagedUrl::query()
            ->where('run_id', $this->run->id)
            ->whereKey($urlId)
            ->whereIn('status', self::PROCESSABLE_STATUSES)
            ->where(function (Builder $query) use ($automation): void {
                foreach (array_keys($automation->sourceTypeLabels()) as $index => $sourceType) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}(fn (Builder $sourceQuery) => $this->constrainToSourceType($sourceQuery, $sourceType, true));
                }
            });

        return $query->first();
    }

    private function automaticUrlQuery(string $sourceType): Builder
    {
        return $this->constrainToSourceType(
            LegacyStagedUrl::query()
                ->where('run_id', $this->run?->id)
                ->whereIn('status', ['pending', 'needs_review', 'failed']),
            $sourceType,
            true,
        );
    }

    private function isMediaRetryFilter(): bool
    {
        return in_array($this->eligibilityFilter, ['media_review', 'image_count_limit'], true);
    }

    private function filteredUrlQuery(): Builder
    {
        return LegacyStagedUrl::query()
            ->where('run_id', $this->run?->id)
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $nested) => $nested
                    ->where('normalized_path', 'like', '%'.$this->search.'%')
                    ->orWhere('root_object_key', 'like', '%'.$this->search.'%')))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->failureFilter !== '', fn (Builder $query) => $query
                ->where('status', 'failed')->where('error_text', $this->failureFilter))
            ->when($this->objectType !== '', fn (Builder $query) => $this->constrainToSourceType($query, $this->objectType))
            ->when($this->eligibilityFilter !== '', fn (Builder $query) => $this->applyEligibilityFilter(
                $query, $this->eligibilityFilter, $this->automaticSourceType,
            ));
    }

    private function mediaRetryUrlQuery(LegacyMediaRetry $mediaRetry): Builder
    {
        return $this->filteredUrlQuery()->whereIn('id', $mediaRetry->query($this->run)->select('id'));
    }

    private function applyEligibilityFilter(Builder $query, string $filter, string $sourceType): Builder
    {
        return match ($filter) {
            'eligible' => $this->constrainToSourceType(
                $query->whereIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES),
                $sourceType,
                true,
            ),
            'ineligible' => $query->where(function (Builder $nested) use ($sourceType): void {
                $nested->whereNotIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES)
                    ->orWhere(function (Builder $processable) use ($sourceType): void {
                        $processable->whereIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES)
                            ->whereNotExists(fn (QueryBuilder $root) => $this->matchingRootSubquery($root)
                                ->where('legacy_root.object_type', $sourceType)
                                ->where('legacy_root.is_partial', false));
                    });
            }),
            'different_type' => $this->constrainToDifferentSourceType(
                $query->whereIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES),
                $sourceType,
            ),
            'missing_root' => $query
                ->whereIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES)
                ->whereNotExists(fn (QueryBuilder $root) => $this->matchingRootSubquery($root)),
            'partial_root' => $query
                ->whereIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES)
                ->whereExists(fn (QueryBuilder $root) => $this->matchingRootSubquery($root)
                    ->where('legacy_root.object_type', $sourceType)
                    ->where('legacy_root.is_partial', true)),
            'processed' => $query->whereNotIn('legacy_staged_urls.status', self::PROCESSABLE_STATUSES),
            'media_review' => $query->whereHas('latestMediaAudit', fn (Builder $audit) => $audit->where('status', 'warning')),
            'image_count_limit' => $query->withImageCountLimitWarning(),
            default => $query,
        };
    }

    /** @return array{total: int, eligible: int, ineligible: int, different_type: int, missing_root: int, partial_root: int, processed: int} */
    private function eligibilityCounts(string $sourceType): array
    {
        if (! $this->run) {
            return $this->emptyEligibilityCounts();
        }

        $statuses = self::PROCESSABLE_STATUSES;
        $urlTable = (new LegacyStagedUrl)->getTable();
        $rootTable = (new LegacyStagedObject)->getTable();
        $row = DB::table($urlTable.' as eligibility_url')
            ->leftJoin($rootTable.' as eligibility_root', function (JoinClause $join): void {
                $join->on('eligibility_root.run_id', '=', 'eligibility_url.run_id')
                    ->on('eligibility_root.object_key', '=', 'eligibility_url.root_object_key');
            })
            ->where('eligibility_url.run_id', $this->run->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN eligibility_url.status IN (?, ?, ?) AND eligibility_root.id IS NOT NULL AND eligibility_root.is_partial = 0 AND eligibility_root.object_type = ? THEN 1 ELSE 0 END) as eligible',
                [...$statuses, $sourceType],
            )
            ->selectRaw(
                'SUM(CASE WHEN eligibility_url.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as processed',
                $statuses,
            )
            ->selectRaw(
                'SUM(CASE WHEN eligibility_url.status IN (?, ?, ?) AND eligibility_root.id IS NULL THEN 1 ELSE 0 END) as missing_root',
                $statuses,
            )
            ->selectRaw(
                'SUM(CASE WHEN eligibility_url.status IN (?, ?, ?) AND eligibility_root.id IS NOT NULL AND eligibility_root.object_type = ? AND eligibility_root.is_partial = 1 THEN 1 ELSE 0 END) as partial_root',
                [...$statuses, $sourceType],
            )
            ->selectRaw(
                'SUM(CASE WHEN eligibility_url.status IN (?, ?, ?) AND eligibility_root.id IS NOT NULL AND eligibility_root.object_type <> ? THEN 1 ELSE 0 END) as different_type',
                [...$statuses, $sourceType],
            )
            ->first();

        $total = (int) ($row?->total ?? 0);
        $eligible = (int) ($row?->eligible ?? 0);

        return [
            'total' => $total,
            'eligible' => $eligible,
            'ineligible' => max(0, $total - $eligible),
            'different_type' => (int) ($row?->different_type ?? 0),
            'missing_root' => (int) ($row?->missing_root ?? 0),
            'partial_root' => (int) ($row?->partial_root ?? 0),
            'processed' => (int) ($row?->processed ?? 0),
        ];
    }

    /** @return array{total: int, eligible: int, ineligible: int, different_type: int, missing_root: int, partial_root: int, processed: int} */
    private function emptyEligibilityCounts(): array
    {
        return [
            'total' => 0,
            'eligible' => 0,
            'ineligible' => 0,
            'different_type' => 0,
            'missing_root' => 0,
            'partial_root' => 0,
            'processed' => 0,
        ];
    }

    private function constrainToDifferentSourceType(Builder $query, string $sourceType): Builder
    {
        return $query->whereExists(function (QueryBuilder $root) use ($sourceType): void {
            $this->matchingRootSubquery($root)
                ->where('legacy_root.object_type', '<>', $sourceType);
        });
    }

    private function matchingRootSubquery(QueryBuilder $root): QueryBuilder
    {
        return $root->selectRaw('1')
            ->from((new LegacyStagedObject)->getTable().' as legacy_root')
            ->whereColumn('legacy_root.run_id', 'legacy_staged_urls.run_id')
            ->whereColumn('legacy_root.object_key', 'legacy_staged_urls.root_object_key');
    }

    private function constrainToSourceType(Builder $query, string $sourceType, bool $fullOnly = false): Builder
    {
        return $query->whereExists(function (QueryBuilder $root) use ($sourceType, $fullOnly): void {
            $this->matchingRootSubquery($root)
                ->where('legacy_root.object_type', $sourceType)
                ->when($fullOnly, fn ($query) => $query->where('legacy_root.is_partial', false));
        });
    }

    private function selectedUrl(): LegacyStagedUrl
    {
        if (! $this->selectedUrlId) {
            throw new InvalidArgumentException('Chưa chọn URL để mapping.');
        }

        return $this->runUrl($this->selectedUrlId);
    }

    private function runUrl(int $urlId): LegacyStagedUrl
    {
        if (! $this->run) {
            abort(404);
        }

        return LegacyStagedUrl::query()->with('run')->where('run_id', $this->run->id)->findOrFail($urlId);
    }

    private function authorizeSuperAdmin(): User
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->hasRole('super_admin'), 403);

        return $actor;
    }
}
