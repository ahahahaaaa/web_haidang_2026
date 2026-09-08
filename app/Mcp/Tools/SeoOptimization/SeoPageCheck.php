<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class SeoPageCheck extends SeoOptimizationTool
{
    protected string $name = 'seo_page_check';

    protected string $ability = 'audit';

    protected string $description = 'Chạy và lưu audit SEO của trang hiện tại. Điểm số không cấp quyền xuất bản; audit thiếu dữ liệu sẽ giữ trạng thái partial/unmapped.';

    public function schema(JsonSchema $schema): array
    {
        return ['page_id' => $schema->string()->required()];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate(['page_id' => ['required', 'ulid']]);

        return $this->result(fn (): array => [
            'audit' => $workflow->audit($this->page($data['page_id']), $this->actor())->only([
                'id', 'page_id', 'source_version', 'strategy_revision', 'status', 'score', 'grade',
                'report', 'rule_version', 'sheet_sync_status', 'created_at',
            ]),
            'approval_mode' => 'human_review_required',
        ]);
    }
}
