<?php

namespace App\Actions\SeoOptimization;

use App\Models\SeoOptimizationAsset;
use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteOptimizationTasks
{
    public function __construct(private OptimizationAccess $access) {}

    public function deleteOne(SeoOptimizationTask $task, User $user): string
    {
        $this->humanOnly();

        return DB::transaction(function () use ($task, $user): string {
            $task = SeoOptimizationTask::query()->lockForUpdate()->findOrFail($task->id);
            $page = $task->page()->firstOrFail();
            $this->access->authorize($user, 'propose', $page);
            $this->ensure($task->canBeDeletedFromQueue(), 'Không thể xóa task khi Codex đang xử lý. Hãy hủy hoặc đợi phiên xử lý kết thúc.');

            $this->recordDeletion($task, $user, 'single');
            $task->delete();

            return (string) $task->id;
        });
    }

    public function deleteFinished(User $user): int
    {
        $this->humanOnly();
        $pageIds = $this->access->queryFor($user, 'propose')->select('id');
        $deletedCount = 0;

        SeoOptimizationTask::query()
            ->whereIn('page_id', $pageIds)
            ->finished()
            ->select('id')
            ->chunkById(100, function ($taskIds) use ($user, &$deletedCount): void {
                $deletedCount += DB::transaction(function () use ($taskIds, $user): int {
                    $tasks = SeoOptimizationTask::query()
                        ->whereKey($taskIds->pluck('id'))
                        ->finished()
                        ->with('page')
                        ->oldest('id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($tasks as $task) {
                        $this->access->authorize($user, 'propose', $task->page);
                        $this->recordDeletion($task, $user, 'finished_bulk');
                        $task->delete();
                    }

                    return $tasks->count();
                });
            });

        return $deletedCount;
    }

    private function recordDeletion(SeoOptimizationTask $task, User $user, string $mode): void
    {
        SeoOptimizationEvent::query()->create([
            'page_id' => $task->page_id,
            'actor_id' => $user->id,
            'event' => 'task.deleted_from_queue',
            'payload' => [
                'task_id' => $task->id,
                'status' => $task->status,
                'delete_mode' => $mode,
                'proposal_count' => SeoOptimizationProposal::query()->where('task_id', $task->id)->count(),
                'asset_count' => SeoOptimizationAsset::query()->where('task_id', $task->id)->count(),
                'source_version' => $task->source_version,
            ],
        ]);
    }

    private function humanOnly(): void
    {
        abort_if(request()->attributes->has('seo_optimization_credential'), 403, 'MCP không được xóa hàng chờ.');
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['task' => $message]);
        }
    }
}
