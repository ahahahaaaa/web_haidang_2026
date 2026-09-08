<?php

namespace Src\Domains\Seo\Support;

use App\Services\OpenAI\OpenAIResponsesClient;
use Src\Domains\Seo\DTOs\SeoBriefData;
use Src\Domains\Seo\DTOs\SeoDraftData;
use Src\Domains\Seo\Models\ContentCluster;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Repositories\SeoPageRepository;

class SeoGenerationCoordinator
{
    public function __construct(
        protected OpenAIResponsesClient $client,
        protected SeoPromptFactory $promptFactory,
        protected SeoPageRepository $pageRepository,
    ) {}

    public function generateBrief(ContentCluster $cluster): SeoBriefData
    {
        $response = $this->client->respond('Return valid JSON only.', $this->promptFactory->brief($cluster));
        return SeoBriefData::fromArray(json_decode($this->client->outputText($response), true, 512, JSON_THROW_ON_ERROR));
    }

    public function generateDraft(SeoPage $page, array $meta, array $schema): SeoDraftData
    {
        $response = $this->client->respond('Return markdown only. No code fences.', $this->promptFactory->draft($page));
        return new SeoDraftData(
            content: $this->client->outputText($response),
            meta: $meta,
            schema: $schema,
            rawResponse: $response,
        );
    }
}
