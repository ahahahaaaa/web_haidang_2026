<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive(false)]
#[IsIdempotent]
class RequestSeoOptimization extends SeoOptimizationTool
{
    protected string $name = 'request_seo_optimization';

    protected string $ability = 'propose';

    protected string $description = 'Đưa một trang vào hàng chờ tạo đề xuất. Không thay đổi nội dung public. Dùng cùng idempotency_key khi thử lại cùng yêu cầu.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->required(),
            'idempotency_key' => $schema->string()->required()->description('Khóa ổn định 8–100 ký tự cho yêu cầu này.'),
        ];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate([
            'page_id' => ['required', 'ulid'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9:_-]+$/'],
        ]);

        return $this->result(fn (): array => [
            'task' => $this->taskData($workflow->enqueue($this->page($data['page_id']), $this->actor(), $data['idempotency_key'])),
            'approval_mode' => 'human_review_required',
        ]);
    }
}
