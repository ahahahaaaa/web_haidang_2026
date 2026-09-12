<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Models\SeoOptimizationBackup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListContentBackups extends SeoOptimizationTool
{
    protected string $name = 'list_content_backups';

    protected string $description = 'Liệt kê backup nội dung bất biến trong phạm vi token. Không trả toàn bộ snapshot để giảm lộ dữ liệu; dùng backup_id để yêu cầu tạo đề xuất khôi phục.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->string()->description('Tùy chọn lọc theo ULID trang.'),
            'limit' => $schema->integer()->min(1)->max(100)->default(25),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate(['page_id' => ['sometimes', 'ulid'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:100']]);

        return $this->result(function () use ($data): array {
            if (isset($data['page_id'])) {
                $this->page($data['page_id']);
            }
            $backups = SeoOptimizationBackup::query()
                ->whereIn('page_id', $this->pages()->select('id'))
                ->when(isset($data['page_id']), fn ($query) => $query->where('page_id', $data['page_id']))
                ->latest('created_at')
                ->limit($data['limit'] ?? 25)
                ->get(['id', 'page_id', 'proposal_id', 'source_version', 'owner_type', 'owner_id', 'checksum', 'created_at']);

            return ['backups' => $backups->toArray()];
        });
    }
}
