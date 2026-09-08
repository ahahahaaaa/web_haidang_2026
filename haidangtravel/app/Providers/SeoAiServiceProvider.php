<?php

namespace App\Providers;

use App\Services\OpenAI\OpenAIResponsesClient;
use App\Services\Seo\SeoGenerationOrchestrator;
use Illuminate\Support\ServiceProvider;

class SeoAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('seo_ai.php'), 'seo_ai');

        $this->app->singleton(OpenAIResponsesClient::class, function () {
            return new OpenAIResponsesClient(
                apiKey: (string) config('services.openai.api_key'),
                baseUrl: (string) config('seo_ai.base_url'),
                model: (string) config('seo_ai.default_model'),
            );
        });

        $this->app->singleton(SeoGenerationOrchestrator::class, function ($app) {
            return new SeoGenerationOrchestrator(
                promptBuilder: $app->make(\App\Services\Seo\SeoPromptBuilder::class),
                client: $app->make(OpenAIResponsesClient::class),
                metaService: $app->make(\App\Services\Seo\SeoMetaService::class),
                schemaService: $app->make(\App\Services\Seo\SeoSchemaService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/seo_ai.php' => config_path('seo_ai.php'),
        ], 'seo-ai-config');
    }
}
