<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\ContentCreationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive(true)]
#[IsIdempotent]
class SubmitContentCreation extends SeoOptimizationTool
{
    protected string $name = 'submit_cms_content_creation';

    protected string $ability = 'create';

    protected string $description = 'Kiểm tra payload theo loại bài rồi tạo record CMS draft/inactive. Danh mục không có draft sẽ dừng ở chờ xác nhận thủ công.';

    public function schema(JsonSchema $schema): array
    {
        $placement = $schema->object([
            'ref' => $schema->string()->required(),
            'media_id' => $schema->integer()->required(),
            'slot' => $schema->string()->required()->description('cover, avatar, content, gallery hoặc landing_block theo contract.'),
            'alt' => $schema->string()->required(),
            'caption' => $schema->string(),
            'title' => $schema->string(),
            'block_uuid' => $schema->string(),
            'item_uuid' => $schema->string(),
        ])->withoutAdditionalProperties();

        return [
            'task_id' => $schema->string()->required(),
            'lease_token' => $schema->string()->required(),
            'payload' => $schema->object()->required()->description('Object field đúng contract đã trả khi start; không gửi field server_only.'),
            'media_placements' => $schema->array()->items($placement)->description('Để [] khi bài không dùng ảnh.'),
        ];
    }

    public function handle(Request $request, ContentCreationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate([
            'task_id' => ['required', 'ulid'],
            'lease_token' => ['required', 'string', 'size:64'],
            'payload' => ['required', 'array'],
            'media_placements' => ['sometimes', 'array', 'max:60'],
        ]);

        return $this->result(fn (): array => $workflow->submit(
            $this->actor(), $this->credential(), $data['task_id'], $data['lease_token'],
            $data['payload'], $data['media_placements'] ?? [],
        ));
    }
}
