<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Support\Facades\Route;

// Telegram webhook (public endpoint)
Route::post('/telegram/webhook', [WebhookController::class, 'handle'])
    ->name('telegram.webhook');

// Locale switcher
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');
