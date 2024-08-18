<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('local') || app()->environment('testing')) {
            // Only run in local or testing environments
            if (!Schema::hasTable('oauth_clients')) {
                Artisan::call('passport:install');
            }
        }
    }
}
