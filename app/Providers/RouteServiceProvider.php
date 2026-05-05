<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register route related services here if needed.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // In this project bootstrap handles route loading; keep provider minimal.
    }
}
