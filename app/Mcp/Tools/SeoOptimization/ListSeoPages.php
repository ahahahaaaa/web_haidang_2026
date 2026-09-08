<?php

namespace App\Mcp\Tools\SeoOptimization;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListSeoPages extends SeoOptimizationTool
{
    protected string $name = 'list_seo_pages';

    protected string $description = 'Liệt kê inventory URL CMS thật trong phạm vi token; phân trang bằng next_after_id. Không tự đồng bộ hay sửa trang.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_type' => $schema->string()->description('Lọc loại trang; vẫn bị giới hạn bởi quyền token.'),
            'classification' => $schema->string()->description('Lọc phân loại inventory nếu cần.'),
            'after_id' => $schema->string()->description('ULID tiếp theo từ next_after_id của lượt trước.'),
            'limit' => $schema->integer()->min(1)->max(100)->default(25),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'page_type' => ['sometimes', 'string', 'max:60'],
            'classification' => ['sometimes', 'string', 'max:60'],
            'after_id' => ['sometimes', 'ulid'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->result(function () use ($data): array {
            $limit = $data['limit'] ?? 25;
            $query = $this->pages();
            foreach (['page_type', 'classification'] as $field) {
                if (isset($data[$field])) {
                    $query->where($field, $data[$field]);
                }
            }
            if (isset($data['after_id'])) {
                $query->where('id', '>', $data['after_id']);
            }
            $rows = $query->orderBy('id')->limit($limit + 1)
                ->get(['id', 'title', 'path', 'page_type', 'classification', 'source_version', 'updated_at']);
            $visible = $rows->take($limit)->values();

            return [
                'pages' => $visible->toArray(),
                'next_after_id' => $rows->count() > $limit ? $visible->last()->id : null,
                'approval_mode' => 'human_review_required',
            ];
        });
    }
}
