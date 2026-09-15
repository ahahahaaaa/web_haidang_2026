<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\ContentCreationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive(false)]
#[IsIdempotent]
class StartContentCreation extends SeoOptimizationTool
{
    protected string $name = 'start_cms_content_creation';

    protected string $ability = 'create';

    protected string $description = 'Mở task tạo nội dung CMS mới, trả contract và upload URL. Chưa tạo record CMS hoặc thay đổi public.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'content_type' => $schema->string()->required()->description('Loại lấy từ list_cms_content_creation_types.'),
            'brief' => $schema->object([
                'request' => $schema->string()->required(),
                'primary_keyword' => $schema->string()->required(),
                'search_intent' => $schema->string()->required(),
                'secondary_keywords' => $schema->array()->items($schema->string()),
                'entities' => $schema->array()->items($schema->string()),
                'required_topics' => $schema->array()->items($schema->string()),
                'facts' => $schema->array()->items($schema->string()),
                'image_requirements' => $schema->string(),
            ])->withoutAdditionalProperties()->required(),
            'idempotency_key' => $schema->string()->required()->description('Khóa ổn định 8–100 ký tự cho cùng yêu cầu.'),
        ];
    }

    public function handle(Request $request, ContentCreationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate([
            'content_type' => ['required', 'string', 'max:40'],
            'brief' => ['required', 'array:request,primary_keyword,search_intent,secondary_keywords,entities,required_topics,facts,image_requirements'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9:_-]+$/'],
        ]);

        return $this->result(fn (): array => $workflow->start(
            $this->actor(), $this->credential(), $data['content_type'], $data['brief'], $data['idempotency_key'],
        ));
    }
}
