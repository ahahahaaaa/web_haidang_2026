<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class ClaimSeoOptimization extends SeoOptimizationTool
{
    protected string $name = 'claim_seo_optimization';

    protected string $ability = 'propose';

    protected string $description = 'Nhận tối đa một task được phép cùng lease token, snapshot và brief. task=null nghĩa là chưa có việc; kết thúc lượt chạy. Không ghi lease token vào log hay nội dung.';

    public function schema(JsonSchema $schema): array
    {
        return ['task_id' => $schema->string()->description('ULID task cụ thể; bỏ trống để nhận task tiếp theo trong phạm vi token.')];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate(['task_id' => ['sometimes', 'ulid']]);

        return $this->result(function () use ($data, $workflow): array {
            if (isset($data['task_id'])) {
                $this->task($data['task_id']);
            }

            return [
                'task' => $workflow->claim($this->actor(), (string) $this->credential()->id, $data['task_id'] ?? null),
                'approval_mode' => 'human_review_required',
            ];
        });
    }
}
