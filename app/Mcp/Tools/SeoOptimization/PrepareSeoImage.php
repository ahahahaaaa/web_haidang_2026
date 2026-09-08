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

    protected string $description = 'Chuẩn bị ảnh cho task Google Sheet đang leased: image_url trống trả brief tạo ảnh để Codex render/upload; URL có sẵn nhập ảnh thật hoặc tái sử dụng Media cùng site. Chỉ thêm Media thư viện, không sửa nội dung public. Cần token automate và quyền Media.';

    public function shouldRegister(): bool
    {
        $credential = $this->httpRequest->attributes->get('seo_optimization_credential');

        return parent::shouldRegister() && in_array('automate', $credential->abilities ?? [], true);
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
