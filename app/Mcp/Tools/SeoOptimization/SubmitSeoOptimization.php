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
class SubmitSeoOptimization extends SeoOptimizationTool
{
    protected string $name = 'submit_seo_optimization';

    protected string $ability = 'propose';

    protected string $description = 'Nộp patch thành đề xuất chờ người dùng duyệt. Chỉ field trong writable_fields; giữ nguyên expected_version nhận khi claim. Thiếu dữ kiện ghi missing_facts. Không áp dụng/publish.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->string()->required(),
            'lease_token' => $schema->string()->required(),
            'idempotency_key' => $schema->string()->required(),
            'payload' => $schema->object([
                'expected_version' => $schema->string()->required(),
                'patch' => $schema->object()->required()->description('Object tên field CMS thực tế → chuỗi mới. Chỉ dùng writable_fields trong snapshot; rỗng nếu NEED_DATA.'),
                'notes' => $schema->string()->required()->description('Giải thích thay đổi, keyword/intent và căn cứ.'),
                'claims' => $schema->array()->items($schema->object())->required()->description('Dữ kiện và nguồn theo contract trong brief; không tự bịa dữ kiện.'),
                'missing_facts' => $schema->array()->items($schema->string())->required(),
                'semantic_assessment' => $schema->object()->description('Đánh giá ngữ nghĩa có evidence theo brief, nếu đã thực hiện.'),
            ])->withoutAdditionalProperties()->required(),
        ];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate([
            'task_id' => ['required', 'ulid'],
            'lease_token' => ['required', 'string', 'min:16', 'max:512'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9:_-]+$/'],
            'payload' => ['required', 'array:expected_version,patch,notes,claims,missing_facts,semantic_assessment'],
            'payload.expected_version' => ['required', 'string', 'max:100'],
            'payload.patch' => ['present', 'array', 'max:30'],
            'payload.patch.*' => ['string', 'max:200000'],
            'payload.notes' => ['required', 'string', 'max:10000'],
            'payload.claims' => ['present', 'array', 'max:100'],
            'payload.claims.*' => ['array'],
            'payload.missing_facts' => ['present', 'array', 'max:100'],
            'payload.missing_facts.*' => ['string', 'max:2000'],
            'payload.semantic_assessment' => ['sometimes', 'array'],
        ]);

        return $this->result(function () use ($data, $workflow): array {
            $this->task($data['task_id']);
            $proposal = $workflow->submit(
                $data['task_id'], $data['lease_token'], $data['payload'],
                $this->actor(), (string) $this->credential()->id, $data['idempotency_key'],
            );

            return [
                'proposal' => $proposal->only(['id', 'page_id', 'task_id', 'status', 'source_version', 'created_at', 'content_hash']),
                'approval_mode' => 'human_review_required',
                'public_content_changed' => false,
            ];
        });
    }
}
