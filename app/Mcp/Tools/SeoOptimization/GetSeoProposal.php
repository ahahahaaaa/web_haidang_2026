<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Models\SeoOptimizationProposal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetSeoProposal extends SeoOptimizationTool
{
    protected string $name = 'get_seo_proposal';

    protected string $description = 'Đọc đề xuất và trạng thái duyệt trong phạm vi quyền của token. Không thể duyệt, áp dụng hoặc xuất bản qua tool này.';

    public function schema(JsonSchema $schema): array
    {
        return ['proposal_id' => $schema->string()->required()];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate(['proposal_id' => ['required', 'ulid']]);

        return $this->result(function () use ($data): array {
            $proposal = SeoOptimizationProposal::query()
                ->whereIn('page_id', $this->pages()->select('id'))->findOrFail($data['proposal_id']);
            $this->page((string) $proposal->page_id);

            return [
                'proposal' => $proposal->only([
                    'id', 'page_id', 'task_id', 'status', 'source_version', 'patch',
                    'notes', 'claims', 'missing_facts', 'qa', 'created_at', 'updated_at', 'content_hash',
                ]),
                'approval_mode' => 'human_review_required',
            ];
        });
    }
}
