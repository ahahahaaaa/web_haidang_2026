<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\ContentCreationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class GetContentCreation extends SeoOptimizationTool
{
    protected string $name = 'get_cms_content_creation';

    protected string $ability = 'create';

    protected string $description = 'Đọc trạng thái, ảnh upload, kết quả tạo draft và editor URL của một task tạo nội dung.';

    public function schema(JsonSchema $schema): array
    {
        return ['task_id' => $schema->string()->required()];
    }

    public function handle(Request $request, ContentCreationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate(['task_id' => ['required', 'ulid']]);

        return $this->result(function () use ($data, $workflow): array {
            $task = $workflow->findForCredential($data['task_id'], $this->actor(), $this->credential());

            return ['task' => $workflow->taskData($task)];
        });
    }
}
