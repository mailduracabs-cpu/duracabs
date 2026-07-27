<?php

namespace App\Providers;

use App\SEO\Rules\HeadingRule;
use App\SEO\Rules\KeywordRule;
use App\SEO\Rules\MetaRule;
use App\SEO\Rules\ReadabilityRule;
use App\SEO\Rules\SlugRule;
use App\SEO\Rules\TitleRule;
use App\SEO\Services\SeoAnalysisService;
use App\SEO\Services\SeoScoreCalculator;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register SEO services in Laravel container.
     */
    public function register(): void
    {
        $this->app->singleton(SeoScoreCalculator::class);

        $this->app->singleton(TitleRule::class);
        $this->app->singleton(MetaRule::class);
        $this->app->singleton(SlugRule::class);
        $this->app->singleton(KeywordRule::class);
        $this->app->singleton(HeadingRule::class);
        $this->app->singleton(ReadabilityRule::class);

        $this->app->singleton(
            SeoAnalysisService::class,
            function ($app): SeoAnalysisService {
                return new SeoAnalysisService(
                    scoreCalculator: $app->make(SeoScoreCalculator::class),
                );
            },
        );
    }

    /**
     * Bootstrap SEO services.
     */
    public function boot(): void
    {
        //
    }
}