<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationAutomationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class ClaimNextContentOptimization extends SeoOptimizationTool
{
    protected string $name = 'claim_next_content_optimization';

    protected string $ability = 'automate';

    protected string $description = 'Nhận task Đang chờ do người dùng đưa vào hàng chờ admin trước. Mặc định khi hàng chờ phù hợp đã hết, server mới tự chọn bài public/indexable theo policy; đặt admin_queue_only=true để chỉ xử lý danh sách admin. Trả snapshot cùng lease; task=null nghĩa là hết việc.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'admin_queue_only' => $schema->boolean()->default(false)
                ->description('Chỉ nhận task Đang chờ trong admin; không tự tạo task từ inventory khi hàng chờ hết.'),
        ];
    }

    public function handle(Request $request, OptimizationAutomationService $automation): Response|ResponseFactory
    {
        $data = $request->validate([
            'admin_queue_only' => ['sometimes', 'boolean'],
        ]);

        return $this->result(fn () => [
            'task' => $automation->claimNext(
                $this->actor(),
                $this->credential()->id,
                (bool) ($data['admin_queue_only'] ?? false),
            ),
        ]);
    }
}
