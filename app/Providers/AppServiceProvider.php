<?php

namespace App\Providers;

use App\Services\LlmExtractorService;
use App\Services\SpeakerClassifierService;
use App\Services\SpeechToTextService;
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

        $this->app->bind(SpeechToTextService::class, function ($app) {
            return new SpeechToTextService(config('stt'));
        });

        $this->app->bind(SpeakerClassifierService::class, function ($app) {
            return new SpeakerClassifierService(config('llm'));
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
