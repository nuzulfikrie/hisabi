<?php

namespace App\Providers;

use App\Contracts\Telegram\MessageParser;
use App\Services\Telegram\SimpleMessageParser;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MessageParser::class, SimpleMessageParser::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\Telegram\SetWebhookCommand::class,
            ]);
        }
    }
}
