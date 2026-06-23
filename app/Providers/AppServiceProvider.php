<?php

namespace App\Providers;

use App\Services\LlmExtractorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LlmExtractorService::class, function ($app) {
            return new LlmExtractorService(config('llm'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
