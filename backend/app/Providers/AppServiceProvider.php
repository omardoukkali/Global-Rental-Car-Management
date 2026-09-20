<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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
        // Tests chain several failed logins: without this they would get
        // a 429 instead of the expected 401.
        if (app()->environment('testing')) {
            RateLimiter::for('api', fn () => Limit::none());
        }
    }
}
