<?php

namespace App\Actions\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class BulkAuditSeoPages
{
    public function __construct(
        private OptimizationAccess $access,
        private OptimizationWorkflowService $workflow,
    ) {}

    /**
     * Audit every page from an already authorized server-side query.
     *
     * A failed page is reported and skipped so the remaining selected URLs can
     * still receive a fresh audit.
     *
     * @return array{selected:int,audited:int,scored:int,partial:int,failed:int}
     */
    public function handle(Builder $query, User $user): array
    {
        $this->humanOnly();
        $this->access->authorize($user, 'audit');

        $result = [
            'selected' => 0,
            'audited' => 0,
            'scored' => 0,
            'partial' => 0,
            'failed' => 0,
        ];

        (clone $query)
            ->reorder()
            ->select('seo_optimization_pages.id')
            ->orderBy('seo_optimization_pages.id')
            ->chunkById(25, function (Collection $rows) use ($user, &$result): void {
                foreach ($rows as $row) {
                    $result['selected']++;

                    try {
                        $page = SeoOptimizationPage::query()->findOrFail($row->getKey());
                        $this->access->authorize($user, 'audit', $page);
                        $audit = $this->workflow->audit($page, $user);

                        $result['audited']++;
                        if ($audit->score === null) {
                            $result['partial']++;
                        } else {
                            $result['scored']++;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $result['failed']++;
                    }
                }
            }, 'seo_optimization_pages.id', 'id');

        return $result;
    }

    private function humanOnly(): void
    {
        abort_if(request()->attributes->has('seo_optimization_credential'), 403, 'MCP không được chạy kiểm tra hàng loạt trong CMS.');
    }
}
