<?php

namespace App\Providers;

use App\Services\OpenAI\OpenAIResponsesClient;
use App\Services\Seo\SeoAdminPresenter;
use Illuminate\Support\ServiceProvider;
use Src\Domains\Seo\Repositories\SeoClusterRepository;
use Src\Domains\Seo\Repositories\SeoLinkRepository;
use Src\Domains\Seo\Repositories\SeoPageRepository;
use Src\Domains\Seo\Support\SeoGenerationCoordinator;
use Src\Domains\Seo\Support\SeoPromptFactory;
use Src\Domains\Seo\Support\SeoPublisher;
use Src\Domains\Seo\Support\SeoQaValidator;

class SeoDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('seo_ai.php'), 'seo_ai');

        $this->app->singleton(OpenAIResponsesClient::class, fn () => new OpenAIResponsesClient(
            apiKey: (string) config('services.openai.api_key'),
            baseUrl: (string) config('seo_ai.base_url'),
            model: (string) config('seo_ai.default_model'),
        ));

        $this->app->singleton(SeoPromptFactory::class);
        $this->app->singleton(SeoQaValidator::class);
        $this->app->singleton(SeoPublisher::class);
        $this->app->singleton(SeoAdminPresenter::class);
        $this->app->singleton(SeoClusterRepository::class);
        $this->app->singleton(SeoPageRepository::class);
        $this->app->singleton(SeoLinkRepository::class);

        $this->app->singleton(SeoGenerationCoordinator::class, function ($app) {
            return new SeoGenerationCoordinator(
                client: $app->make(OpenAIResponsesClient::class),
                promptFactory: $app->make(SeoPromptFactory::class),
                pageRepository: $app->make(SeoPageRepository::class),
            );
        });
    }
}
