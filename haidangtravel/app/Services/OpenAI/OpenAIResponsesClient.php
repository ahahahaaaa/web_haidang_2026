<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIResponsesClient
{
    public function __construct(
        protected string $apiKey,
        protected string $baseUrl,
        protected string $model,
    ) {}

    public function respond(string $instructions, string $input, array $extra = []): array
    {
        $payload = array_merge([
            'model' => $extra['model'] ?? $this->model,
            'instructions' => $instructions,
            'input' => $input,
        ], $extra['payload'] ?? []);

        $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(180)
            ->post('/responses', $payload)
            ->throw()
            ->json();

        if (!is_array($response)) {
            throw new RuntimeException('Invalid response payload from OpenAI.');
        }

        return $response;
    }

    public function outputText(array $response): string
    {
        if (is_string($response['output_text'] ?? null) && $response['output_text'] !== '') {
            return $response['output_text'];
        }

        $chunks = [];
        foreach (($response['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $chunks[] = (string) ($content['text'] ?? '');
                }
            }
        }

        return trim(implode("\n", $chunks));
    }
}
