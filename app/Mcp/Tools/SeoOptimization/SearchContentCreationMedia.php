<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\ContentCreationMediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class SearchContentCreationMedia extends SeoOptimizationTool
{
    protected string $name = 'search_cms_content_media';

    protected string $ability = 'create';

    protected string $description = 'Tìm ảnh có sẵn trong Media Library công khai để tái sử dụng theo media_id; không tải hoặc tạo ảnh mới.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Tên hoặc tên file; để trống lấy ảnh mới nhất.'),
            'limit' => $schema->integer()->default(20),
        ];
    }

    public function handle(Request $request, ContentCreationMediaService $media): Response|ResponseFactory
    {
        $data = $request->validate(['query' => ['sometimes', 'nullable', 'string', 'max:255'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:50']]);

        return $this->result(function () use ($data, $media): array {
            $user = $this->actor();
            abort_unless($user->can('admin.media.index'), 403);

            return ['media' => $media->search((string) ($data['query'] ?? ''), (int) ($data['limit'] ?? 20))];
        });
    }
}
