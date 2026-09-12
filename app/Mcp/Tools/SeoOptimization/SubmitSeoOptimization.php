<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\ContentWriteContractService;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use App\Services\SeoOptimization\PageRegistryService;
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

    protected string $description = 'Nộp patch tối ưu. Facts đang có trong snapshot được giữ hoặc diễn đạt lại mà không cần tài liệu ngoài; chỉ facts mới hoặc bị thay đổi mới cần source/claim. Không tự đổi policy.';

    public function schema(JsonSchema $schema): array
    {
        $faqItem = $schema->object([
            'question' => $schema->string()->required()->description('Câu hỏi hiển thị, tối đa 500 ký tự.'),
            'answer' => $schema->string()->required()->description('Câu trả lời HTML an toàn, tối đa 5.000 ký tự.'),
        ])->withoutAdditionalProperties();
        $blockFields = collect(ContentWriteContractService::BLOCK_STRING_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $schema->string()])
            ->all();
        $blockFields['items'] = $schema->array()->items($faqItem)->description('Danh sách FAQ; chỉ dùng cho block faq khi content_units cho phép.');
        $blockChange = $schema->object([
            'uuid' => $schema->string()->required()->description('UUID chính xác từ content_units.'),
            'type' => $schema->string()->required()->description('Loại block chính xác từ content_units.'),
            'changes' => $schema->object($blockFields)->withoutAdditionalProperties()->required()
                ->description('Chỉ gửi field được liệt kê cho block tương ứng trong content_units.'),
        ])->withoutAdditionalProperties();
        $patch = $schema->object([
            'title' => $schema->string(),
            'name' => $schema->string(),
            'slug' => $schema->string(),
            'excerpt' => $schema->string(),
            'content' => $schema->string(),
            'description' => $schema->string(),
            'meta_title' => $schema->string(),
            'meta_description' => $schema->string(),
            'cover_alt' => $schema->string(),
            'faq_items' => $schema->array()->items($faqItem)->description('Mảng FAQ; chỉ gửi khi faq_items có trong writable_fields.'),
            'body' => $schema->string(),
            'hero_title' => $schema->string(),
            'hero_excerpt' => $schema->string(),
            'intro_title' => $schema->string(),
            'intro_excerpt' => $schema->string(),
            'block_changes' => $schema->array()->items($blockChange)
                ->description('Chỉ dùng cho LandingPage blocks; không gửi toàn bộ blocks JSON.'),
        ])->withoutAdditionalProperties()->required()->description('Chỉ gửi field có trong writable_fields. Giữ nguyên facts gốc nếu không có yêu cầu thay đổi.');
        $claim = $schema->object([
            'claim' => $schema->string()->required(),
            'source_id' => $schema->string()->required()->description('Dùng page khi facts đã có trong snapshot; nguồn khác dùng cho dữ kiện mới được xác minh.'),
            'quote' => $schema->string()->required()->description('Trích dẫn phải khớp nội dung nguồn sau khi chuẩn hóa.'),
        ])->withoutAdditionalProperties();

        return [
            'task_id' => $schema->string()->required(),
            'lease_token' => $schema->string()->required(),
            'idempotency_key' => $schema->string()->required(),
            'payload' => $schema->object([
                'expected_version' => $schema->string()->required(),
                'patch' => $patch,
                'notes' => $schema->string()->required()->description('Giải thích thay đổi, keyword/intent và căn cứ.'),
                'claims' => $schema->array()->items($claim)->description('Có thể bỏ qua hoặc gửi [] khi chỉ giữ facts gốc; chỉ khai báo claim mới hay bị thay đổi.'),
                'warnings' => $schema->array()->items($schema->string())->description('Cảnh báo không chặn xử lý.'),
                'missing_facts' => $schema->array()->items($schema->string())->description('Chỉ dùng khi yêu cầu thay đổi facts nhưng snapshot/nguồn xác minh không đủ.'),
                'semantic_assessment' => $schema->object()->description('Đánh giá ngữ nghĩa có evidence theo brief, nếu đã thực hiện.'),
            ])->withoutAdditionalProperties()->required(),
        ];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow): Response|ResponseFactory
    {
        $rules = [
            'task_id' => ['required', 'ulid'],
            'lease_token' => ['required', 'string', 'min:16', 'max:512'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9:_-]+$/'],
            'payload' => ['required', 'array:expected_version,patch,notes,claims,warnings,missing_facts,semantic_assessment'],
            'payload.expected_version' => ['required', 'string', 'max:100'],
            'payload.patch' => ['present', 'array:'.implode(',', PageRegistryService::PATCH_FIELDS), 'max:30'],
            'payload.patch.faq_items' => ['sometimes', 'array', 'max:20'],
            'payload.patch.faq_items.*' => ['array:question,answer'],
            'payload.patch.faq_items.*.question' => ['required', 'string', 'max:500'],
            'payload.patch.faq_items.*.answer' => ['required', 'string', 'max:5000'],
            'payload.patch.block_changes' => ['sometimes', 'array', 'max:50'],
            'payload.patch.block_changes.*' => ['array:uuid,type,changes'],
            'payload.patch.block_changes.*.uuid' => ['required', 'string', 'max:100'],
            'payload.patch.block_changes.*.type' => ['required', 'string', 'max:80'],
            'payload.patch.block_changes.*.changes' => ['required', 'array', 'min:1', 'max:25'],
            'payload.notes' => ['required', 'string', 'max:10000'],
            'payload.claims' => ['sometimes', 'array', 'max:100'],
            'payload.claims.*' => ['array:claim,source_id,quote'],
            'payload.claims.*.claim' => ['required', 'string', 'max:2000'],
            'payload.claims.*.source_id' => ['required', 'string', 'max:100'],
            'payload.claims.*.quote' => ['required', 'string', 'max:10000'],
            'payload.warnings' => ['sometimes', 'array', 'max:100'],
            'payload.warnings.*' => ['string', 'max:2000'],
            'payload.missing_facts' => ['sometimes', 'array', 'max:100'],
            'payload.missing_facts.*' => ['string', 'max:2000'],
            'payload.semantic_assessment' => ['sometimes', 'array'],
        ];
        foreach (PageRegistryService::STRING_PATCH_FIELDS as $field) {
            $rules['payload.patch.'.$field] = ['sometimes', 'string', 'max:200000'];
        }
        $data = $request->validate($rules);

        return $this->result(function () use ($data, $workflow): array {
            $this->task($data['task_id']);
            $proposal = $workflow->submit(
                $data['task_id'], $data['lease_token'], $data['payload'],
                $this->actor(), (string) $this->credential()->id, $data['idempotency_key'],
            );

            $isDirectAutomation = ($proposal->task()->firstOrFail()->automation['mode'] ?? null) === 'direct_cms';

            return [
                'proposal' => $proposal->only(['id', 'page_id', 'task_id', 'status', 'source_version', 'created_at', 'content_hash']),
                'warnings' => $proposal->qa['warnings'] ?? [],
                'approval_mode' => $isDirectAutomation ? 'server_policy' : 'human_review_required',
                'next_action' => $isDirectAutomation ? 'commit_content_optimization' : 'await_human_review',
                'public_content_changed' => false,
            ];
        });
    }
}
