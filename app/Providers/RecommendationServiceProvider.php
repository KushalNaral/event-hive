<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RecommendationEngine;
use App\Services\RecommendationLogger;

class RecommendationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RecommendationLogger::class, function ($app) {
            return new RecommendationLogger();
        });

        $this->app->singleton(RecommendationEngine::class, function ($app) {
            return new RecommendationEngine(
                $app->make(RecommendationLogger::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
