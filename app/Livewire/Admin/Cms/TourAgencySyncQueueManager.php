<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Services\Travel\TourAgencyPushSyncRunService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\TourAgencyPushSyncRun;

#[Layout('layouts.app')]
#[Title('Hàng chờ đồng bộ API Master Data DashBoard')]
class TourAgencySyncQueueManager extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $triggerFilter = '';

    public function render()
    {
        $query = $this->filteredRunsQuery();

        return view('livewire.admin.cms.tours.agency-sync-queue', [
            'runs' => (clone $query)
                ->with(['creator', 'tour'])
                ->latest('id')
                ->paginate(20),
            'stats' => $this->statusStats(),
            'statusOptions' => $this->statusOptions(),
            'triggerOptions' => $this->triggerOptions(),
        ]);
    }

    public function runAllExistingNow(TourAgencyPushSyncRunService $runService): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $runs = TourAgencyPushSyncRun::query()
            ->where('status', '!=', TourAgencyPushSyncRun::STATUS_RUNNING)
            ->oldest('id')
            ->get();

        if ($runs->isEmpty()) {
            session()->flash('status', 'Không có hàng chờ nào có thể chạy ngay.');

            return;
        }

        $summary = [
            TourAgencyPushSyncRun::STATUS_SUCCEEDED => 0,
            TourAgencyPushSyncRun::STATUS_FAILED => 0,
            TourAgencyPushSyncRun::STATUS_SKIPPED => 0,
        ];

        foreach ($runs as $run) {
            $result = $runService->execute($run);
            $status = (string) ($result['status'] ?? TourAgencyPushSyncRun::STATUS_FAILED);

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        session()->flash(
            'status',
            sprintf(
                'Đã xử lý ngay %d hàng chờ hiện có: thành công %d, bỏ qua %d, lỗi %d.',
                $runs->count(),
                $summary[TourAgencyPushSyncRun::STATUS_SUCCEEDED],
                $summary[TourAgencyPushSyncRun::STATUS_SKIPPED],
                $summary[TourAgencyPushSyncRun::STATUS_FAILED],
            ),
        );

        $this->resetPage();
    }

    public function runPendingNow(TourAgencyPushSyncRunService $runService): void
    {
        $this->runAllExistingNow($runService);
    }

    public function runNow(int $id, TourAgencyPushSyncRunService $runService): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $run = TourAgencyPushSyncRun::query()->findOrFail($id);

        if ($run->status === TourAgencyPushSyncRun::STATUS_RUNNING) {
            session()->flash('status', 'Hàng chờ này đang chạy, vui lòng đợi kết quả mới nhất.');

            return;
        }

        $result = $runService->execute($run);
        $status = (string) ($result['status'] ?? TourAgencyPushSyncRun::STATUS_FAILED);

        session()->flash('status', 'Đã chạy ngay hàng chờ #'.$run->getKey().' với trạng thái '.$this->statusLabel($status).'.');
    }

    public function deleteSucceeded(int $id): void
    {
        $this->deleteFinished($id);
    }

    public function deleteFinished(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $run = TourAgencyPushSyncRun::query()->findOrFail($id);

        if (! $this->canDeleteRun($run->status)) {
            session()->flash('status', 'Chỉ có thể xóa hàng chờ đã kết thúc: thành công, lỗi hoặc bỏ qua.');

            return;
        }

        $this->logQueueDeleted($run, 'single');

        $run->delete();

        session()->flash('status', 'Đã xóa hàng chờ đã kết thúc #'.$id.' khỏi database.');
        $this->resetPage();
    }

    public function deleteAllSucceeded(): void
    {
        $this->deleteAllFinished();
    }

    public function deleteAllFinished(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $query = TourAgencyPushSyncRun::query()
            ->whereIn('status', $this->deletableStatuses());

        if (! (clone $query)->exists()) {
            session()->flash('status', 'Không có hàng chờ đã kết thúc cần xóa.');

            return;
        }

        $deletedCount = 0;
        $actorId = auth()->id();

        $query
            ->chunkById(100, function ($runs) use (&$deletedCount): void {
                foreach ($runs as $run) {
                    $this->logQueueDeleted($run, 'bulk');
                    $run->delete();
                    $deletedCount++;
                }
            });

        Log::info('tour_sync.master_data_dashboard.queue_bulk_deleted', [
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => 'queue_bulk_deleted',
            'deleted_count' => $deletedCount,
            'deleted_by_user_id' => $actorId,
        ]);

        session()->flash('status', 'Đã xóa '.$deletedCount.' hàng chờ đã kết thúc khỏi database.');
        $this->resetPage();
    }

    public function unlockStaleRunning(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $threshold = now()->subMinutes(15);
        $query = TourAgencyPushSyncRun::query()
            ->where('status', TourAgencyPushSyncRun::STATUS_RUNNING)
            ->where(function (Builder $query) use ($threshold): void {
                $query
                    ->whereNull('started_at')
                    ->orWhere('started_at', '<=', $threshold);
            });

        if (! (clone $query)->exists()) {
            session()->flash('status', 'Không có hàng chờ đang chạy quá 15 phút cần mở khóa.');

            return;
        }

        $unlockedCount = 0;
        $actorId = auth()->id();

        $query
            ->chunkById(100, function ($runs) use (&$unlockedCount): void {
                foreach ($runs as $run) {
                    $run->forceFill([
                        'status' => TourAgencyPushSyncRun::STATUS_FAILED,
                        'last_error' => 'Hàng chờ đang chạy quá 15 phút đã được admin mở khóa để retry.',
                        'finished_at' => now(),
                    ])->save();

                    $this->logQueueUnlocked($run);
                    $unlockedCount++;
                }
            });

        Log::info('tour_sync.master_data_dashboard.queue_bulk_unlocked', [
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => 'queue_bulk_unlocked',
            'unlocked_count' => $unlockedCount,
            'unlocked_by_user_id' => $actorId,
            'stale_after_minutes' => 15,
        ]);

        session()->flash('status', 'Đã mở khóa '.$unlockedCount.' hàng chờ đang chạy kẹt. Các hàng này đã chuyển sang trạng thái lỗi để có thể chạy lại.');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTriggerFilter(): void
    {
        $this->resetPage();
    }

    public function statusLabel(string $status): string
    {
        return $this->statusOptions()[$status] ?? $status;
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            TourAgencyPushSyncRun::STATUS_PENDING => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            TourAgencyPushSyncRun::STATUS_RUNNING => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            TourAgencyPushSyncRun::STATUS_SUCCEEDED => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            TourAgencyPushSyncRun::STATUS_FAILED => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
            TourAgencyPushSyncRun::STATUS_SKIPPED => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
        };
    }

    public function canDeleteRun(string $status): bool
    {
        return in_array($status, $this->deletableStatuses(), true);
    }

    public function triggerLabel(string $trigger): string
    {
        return $this->triggerOptions()[$trigger] ?? $trigger;
    }

    protected function filteredRunsQuery(): Builder
    {
        return TourAgencyPushSyncRun::query()
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->triggerFilter !== '', fn (Builder $query) => $query->where('trigger', $this->triggerFilter))
            ->when($this->search !== '', function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('tour_title', 'like', '%'.$search.'%')
                        ->orWhere('tour_code', 'like', '%'.$search.'%');

                    if (is_numeric($search)) {
                        $nested
                            ->orWhere('id', (int) $search)
                            ->orWhere('tour_id', (int) $search)
                            ->orWhere('source_tour_id', (int) $search);
                    }
                });
            });
    }

    protected function logQueueDeleted(TourAgencyPushSyncRun $run, string $mode): void
    {
        Log::info('tour_sync.master_data_dashboard.queue_deleted', [
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => 'queue_deleted',
            'delete_mode' => $mode,
            'run_id' => $run->getKey(),
            'cms_tour_id' => $run->tour_id,
            'source_tour_id' => $run->source_tour_id,
            'tour_code' => $run->tour_code,
            'tour_title' => $run->tour_title,
            'trigger' => $run->trigger,
            'status' => $run->status,
            'queue_name' => $run->queue_name,
            'deleted_by_user_id' => auth()->id(),
            'finished_at' => optional($run->finished_at)->toIso8601String(),
        ]);
    }

    protected function logQueueUnlocked(TourAgencyPushSyncRun $run): void
    {
        Log::warning('tour_sync.master_data_dashboard.queue_unlocked', [
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => 'queue_unlocked',
            'run_id' => $run->getKey(),
            'cms_tour_id' => $run->tour_id,
            'source_tour_id' => $run->source_tour_id,
            'tour_code' => $run->tour_code,
            'tour_title' => $run->tour_title,
            'trigger' => $run->trigger,
            'status' => $run->status,
            'queue_name' => $run->queue_name,
            'attempts' => (int) $run->attempts,
            'started_at' => optional($run->started_at)->toIso8601String(),
            'unlocked_by_user_id' => auth()->id(),
            'stale_after_minutes' => 15,
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function deletableStatuses(): array
    {
        return [
            TourAgencyPushSyncRun::STATUS_SUCCEEDED,
            TourAgencyPushSyncRun::STATUS_FAILED,
            TourAgencyPushSyncRun::STATUS_SKIPPED,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function statusStats(): array
    {
        $counts = TourAgencyPushSyncRun::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();

        return collect(array_keys($this->statusOptions()))
            ->mapWithKeys(fn (string $status): array => [$status => $counts[$status] ?? 0])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function statusOptions(): array
    {
        return [
            TourAgencyPushSyncRun::STATUS_PENDING => 'Đang chờ',
            TourAgencyPushSyncRun::STATUS_RUNNING => 'Đang chạy',
            TourAgencyPushSyncRun::STATUS_SUCCEEDED => 'Thành công',
            TourAgencyPushSyncRun::STATUS_FAILED => 'Lỗi',
            TourAgencyPushSyncRun::STATUS_SKIPPED => 'Bỏ qua',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function triggerOptions(): array
    {
        return [
            'tour_saved' => 'Lưu tour CMS',
            'manual_picker' => 'Chọn thủ công',
        ];
    }
}
