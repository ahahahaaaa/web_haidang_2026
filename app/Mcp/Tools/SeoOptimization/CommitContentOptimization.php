<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationAutomationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive(true)]
#[IsIdempotent]
class CommitContentOptimization extends SeoOptimizationTool
{
    protected string $name = 'commit_content_optimization';

    protected string $ability = 'automate';

    protected string $description = 'Chốt đề xuất trực tiếp trong CMS. Server giữ preview mặc định; ở policy Luôn publish, server tự ghi khi điểm sau lớn hơn điểm trước. Quyền, source version, facts mới, Media và backup vẫn được kiểm tra.';

    public function schema(JsonSchema $schema): array
    {
        return ['proposal_id' => $schema->string()->required(), 'content_hash' => $schema->string()->required()];
    }

    public function handle(Request $request, OptimizationAutomationService $automation): Response|ResponseFactory
    {
        $data = $request->validate([
            'proposal_id' => ['required', 'ulid'],
            'content_hash' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
        ]);

        return $this->result(fn () => $automation->complete(
            $data['proposal_id'],
            $data['content_hash'],
            $this->actor(),
            $this->credential()->id,
        ));
    }
}
