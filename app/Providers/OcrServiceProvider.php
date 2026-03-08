<?php

namespace App\Providers;

use App\Contracts\Ocr\OcrEngine;
use App\Services\Ocr\OcrManager;
use App\Services\Ocr\PaddleOcrService;
use Illuminate\Support\ServiceProvider;

class OcrServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register OCR Manager as singleton
        $this->app->singleton(OcrManager::class, function ($app) {
            return new OcrManager();
        });

        // Bind primary OCR engine
        $this->app->bind(OcrEngine::class, function ($app) {
            $manager = $app->make(OcrManager::class);
            
            if ($manager->isAvailable()) {
                // Return first available engine
                $engines = $manager->getEngines();
                return $engines[0] ?? new PaddleOcrService();
            }
            
            return new PaddleOcrService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/ocr.php' => config_path('ocr.php'),
        ], 'ocr-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\Ocr\StatusCommand::class,
                \App\Console\Commands\Ocr\TestCommand::class,
            ]);
        }
    }
}
