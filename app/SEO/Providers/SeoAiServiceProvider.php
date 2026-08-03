<?php

declare(strict_types=1);

namespace App\SEO\Providers;

use App\SEO\AI\AiManager;
use App\SEO\AI\AiProviderInterface;
use App\SEO\AI\GeminiProvider;
use App\SEO\AI\OpenAiProvider;
use App\SEO\Prompts\FaqPrompt;
use App\SEO\Prompts\MetaPrompt;
use App\SEO\Prompts\RewritePrompt;
use App\SEO\Prompts\SeoContentPrompt;
use App\SEO\Services\SeoAiWriterService;
use App\SEO\Services\SeoSuggestionService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class SeoAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path('seo-ai.php'),
            'seo-ai',
        );

        /*
        |--------------------------------------------------------------------------
        | Provider Classes
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(GeminiProvider::class);
        $this->app->singleton(OpenAiProvider::class);

        /*
        |--------------------------------------------------------------------------
        | Default Provider Interface Binding
        |--------------------------------------------------------------------------
        |
        | AiProviderInterface ko current default provider se resolve karega.
        |
        */

        $this->app->bind(
            AiProviderInterface::class,
            function (Application $app): AiProviderInterface {
                return $app
                    ->make(AiManager::class)
                    ->defaultProvider();
            },
        );

        /*
        |--------------------------------------------------------------------------
        | AI Manager
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            AiManager::class,
            function (Application $app): AiManager {
                return new AiManager($app);
            },
        );

        /*
        |--------------------------------------------------------------------------
        | Prompt Builders
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(SeoContentPrompt::class);
        $this->app->singleton(MetaPrompt::class);
        $this->app->singleton(FaqPrompt::class);
        $this->app->singleton(RewritePrompt::class);

        /*
        |--------------------------------------------------------------------------
        | SEO AI Services
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(SeoAiWriterService::class);

        $this->app->singleton(
            SeoSuggestionService::class,
            function (Application $app): SeoSuggestionService {
                return new SeoSuggestionService(
    $app->make(\App\SEO\AI\AiManager::class),
    $app->make(\App\SEO\Prompts\SeoContentPrompt::class),
    $app->make(\App\SEO\Prompts\MetaPrompt::class),
    $app->make(\App\SEO\Prompts\FaqPrompt::class),
    $app->make(\App\SEO\Prompts\RewritePrompt::class),
    $app->make(\App\SEO\Services\SeoAnalysisService::class),
);
            },
        );
    }

    public function boot(): void
    {
        //
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            GeminiProvider::class,
            OpenAiProvider::class,
            AiProviderInterface::class,
            AiManager::class,
            SeoContentPrompt::class,
            MetaPrompt::class,
            FaqPrompt::class,
            RewritePrompt::class,
            SeoAiWriterService::class,
            SeoSuggestionService::class,
        ];
    }
}