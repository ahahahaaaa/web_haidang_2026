<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\OptimizationMediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class PrepareSeoImage extends SeoOptimizationTool
{
    protected string $name = 'prepare_seo_image';

    protected string $ability = 'propose';

    protected string $description = 'Chuẩn bị ảnh cho task CMS trực tiếp đang leased: chưa có ảnh trả prompt để Codex render/upload; có URL thì nhập ảnh thật hoặc tái sử dụng Media cùng site. Ảnh mới được chuẩn hóa sang WebP trước khi lưu Media. Chỉ thêm Media thư viện, không sửa nội dung public. Cần token automate và quyền Media.';

    /**
     * @return array<int, string>
     */
    public static function requiredAbilities(): array
    {
        return ['propose', 'automate'];
    }

    public function schema(JsonSchema $schema): array
    {
        return ['task_id' => $schema->string()->required(), 'lease_token' => $schema->string()->required()];
    }

    public function handle(Request $request, OptimizationMediaService $media): Response|ResponseFactory
    {
        $data = $request->validate(['task_id' => ['required', 'ulid'], 'lease_token' => ['required', 'string', 'size:64']]);

        return $this->result(fn (): array => $media->prepare($data['task_id'], $data['lease_token'], $this->actor(), (string) $this->credential()->id));
    }
}
