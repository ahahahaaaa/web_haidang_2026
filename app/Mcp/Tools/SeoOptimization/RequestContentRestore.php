<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Models\SeoOptimizationBackup;
use App\Services\SeoOptimization\OptimizationWorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive(false)]
#[IsIdempotent]
class RequestContentRestore extends SeoOptimizationTool
{
    protected string $name = 'request_content_restore';

    protected string $ability = 'propose';

    protected string $description = 'Xác minh checksum backup và tạo đề xuất khôi phục chờ người có quyền duyệt. Tool không tự áp dụng, không tự publish và không ghi đè khi bài đã có thay đổi mới.';

    public function schema(JsonSchema $schema): array
    {
        return ['backup_id' => $schema->string()->required()];
    }

    public function handle(Request $request, OptimizationWorkflowService $workflow): Response|ResponseFactory
    {
        $data = $request->validate(['backup_id' => ['required', 'ulid']]);

        return $this->result(function () use ($data, $workflow): array {
            $backup = SeoOptimizationBackup::query()->whereIn('page_id', $this->pages()->select('id'))->findOrFail($data['backup_id']);
            $this->page((string) $backup->page_id);
            $proposal = $workflow->requestBackupRestore($backup, $this->actor());

            return ['proposal' => $proposal->only(['id', 'page_id', 'status', 'source_version', 'qa', 'content_hash']), 'approval_mode' => 'human_review_required'];
        });
    }
}
