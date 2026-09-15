<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Services\SeoOptimization\ContentCreationRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
class ListContentCreationTypes extends SeoOptimizationTool
{
    protected string $name = 'list_cms_content_creation_types';

    protected string $ability = 'create';

    protected string $description = 'Liệt kê loại nội dung CMS Codex được phép tạo, field contract, media slots và chế độ draft/manual review.';

    public function handle(Request $request, ContentCreationRegistry $registry): Response|ResponseFactory
    {
        return $this->result(function () use ($registry): array {
            $credential = $this->credential();
            $user = $this->actor();
            $contracts = collect($registry->types())
                ->filter(fn (string $type): bool => in_array($type, $credential->allowed_page_types ?? [], true))
                ->map(fn (string $type): array => $registry->get($type))
                ->filter(fn (array $contract): bool => $user->can($contract['permission']))
                ->values()
                ->all();

            return ['contract_version' => ContentCreationRegistry::CONTRACT_VERSION, 'types' => $contracts];
        });
    }
}
