<?php

use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['admin', 'auth'],
    'prefix' => 'admin',
    'as' => 'admin.',
], function () {
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');
    Route::post('users/{user}/disconnect-telegram', [UserController::class, 'disconnectTelegram'])
        ->name('users.disconnect-telegram');

    // System Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
});
