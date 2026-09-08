<?php

namespace App\Services\Seo;

use App\Models\ContentCluster;
use App\Models\SeoPage;
use App\Services\OpenAI\OpenAIResponsesClient;

class SeoGenerationOrchestrator
{
    public function __construct(
        protected SeoPromptBuilder $promptBuilder,
        protected OpenAIResponsesClient $client,
        protected SeoMetaService $metaService,
        protected SeoSchemaService $schemaService,
    ) {
    }

    public function generateBrief(ContentCluster $cluster): array
    {
        $instructions = 'Return valid JSON only.';
        $input = $this->promptBuilder->buildBriefPrompt($cluster);

        $response = $this->client->createTextResponse($instructions, $input);
        $text = $this->client->extractOutputText($response);

        return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    }

    public function generateDraft(SeoPage $page): array
    {
        $instructions = 'Return markdown only. No code fences.';
        $input = $this->promptBuilder->buildDraftPrompt($page);

        $response = $this->client->createTextResponse($instructions, $input);
        $markdown = $this->client->extractOutputText($response);

        $meta = $this->metaService->generateForPage($page);
        $schema = $this->schemaService->generateForPage($page, [
            'business_name' => config('seo_ai.business_name'),
            'url' => config('app.url'),
        ]);

        return [
            'content' => $markdown,
            'meta' => $meta,
            'schema' => $schema,
            'raw_response' => $response,
        ];
    }
}
