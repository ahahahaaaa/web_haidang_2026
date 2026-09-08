<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationAutomationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class ClaimSeoSheetOptimization extends SeoOptimizationTool
{
    protected string $name = 'claim_seo_sheet_optimization';

    protected string $ability = 'automate';

    protected string $description = 'Tự chọn một dòng Google Sheet READY trong phạm vi server và token, chốt revision, nhận snapshot/brief/ảnh và lease. Không có việc trả task=null. Không nhận URL Sheet tùy ý.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request, OptimizationAutomationService $automation): Response|ResponseFactory
    {
        return $this->result(fn () => ['task' => $automation->claimNext($this->actor(), $this->credential()->id)]);
    }
}
