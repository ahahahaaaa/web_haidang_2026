<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationAutomationService;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class ReportSeoOptimizationFailure extends SeoOptimizationTool
{
    protected string $name = 'report_seo_optimization_failure';

    protected string $ability = 'propose';

    protected string $description = 'Ghi nhận lỗi của task đang giữ lease để CMS hiển thị. Không chứa token, prompt riêng tư hay dữ liệu khách hàng trong message.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->string()->required(),
            'lease_token' => $schema->string()->required(),
            'message' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow, OptimizationAutomationService $automation): Response|ResponseFactory
    {
        $data = $request->validate([
            'task_id' => ['required', 'ulid'],
            'lease_token' => ['required', 'string', 'min:16', 'max:512'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return $this->result(function () use ($data, $workflow, $automation): array {
            $task = $this->task($data['task_id']);
            if ($task->automation !== null) {
                return ['task' => $this->taskData($automation->fail($task->id, $data['lease_token'], $this->actor(), $this->credential()->id, $data['message']))];
            }

            return ['task' => $this->taskData($workflow->failTask(
                $data['task_id'], $data['lease_token'], $this->actor(),
                (string) $this->credential()->id, $data['message'],
            ))];
        });
    }
}
