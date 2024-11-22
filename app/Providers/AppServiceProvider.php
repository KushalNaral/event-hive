<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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

        if (!defined('ALLOW_ROLES')) {
            define('ALLOW_ROLES', config('app.allow_roles'));
        }

        if(!defined('EVENT_NOTIFICATION_SPAN')){
            define('EVENT_NOTIFICATION_SPAN', config('app.event_confirm_span'));
        }
    }
}
