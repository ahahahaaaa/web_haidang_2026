<?php

namespace App\Modules\LegacyMigration\Services;

use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use Illuminate\Support\Facades\DB;
use Throwable;

class LegacyRunCounter
{
    public function refresh(LegacyMigrationRun $run): LegacyMigrationRun
    {
        DB::transaction(function () use ($run): void {
            $snapshot = LegacyMigrationRun::query()->lockForUpdate()->findOrFail($run->id);
            $statusCounts = $snapshot->stagedUrls()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $attributes = [
                'received_chunks' => $snapshot->chunks()->count(),
                'staged_urls' => $snapshot->stagedUrls()->count(),
                'mapped_urls' => (int) ($statusCounts['mapped'] ?? 0),
                'casted_urls' => (int) ($statusCounts['casted'] ?? 0),
                'blocked_urls' => (int) ($statusCounts['blocked'] ?? 0),
                'failed_urls' => (int) ($statusCounts['failed'] ?? 0),
            ];

            if ($snapshot->status !== 'receiving') {
                $terminalCount = (int) ($statusCounts['casted'] ?? 0) + (int) ($statusCounts['blocked'] ?? 0);
                $failedCount = (int) ($statusCounts['failed'] ?? 0);
                $allResolved = ($terminalCount + $failedCount) === $attributes['staged_urls'];

                $attributes = [...$attributes, ...[
                    'status' => match (true) {
                        $allResolved && $failedCount === 0 => 'completed',
                        $allResolved => 'completed_with_errors',
                        default => 'ready_for_mapping',
                    },
                    'finished_at' => $allResolved ? ($snapshot->finished_at ?? now()) : null,
                ]];
            }

            $snapshot->forceFill($attributes)->save();
        }, 5);

        return $run->refresh();
    }

    public function refreshAfterCast(LegacyMigrationRun $run): void
    {
        try {
            $this->refresh($run);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
