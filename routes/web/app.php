<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Telegram\SettingsController as TelegramSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
    Route::get('/transactions/scan-receipt', fn () => Inertia::render('Transaction/ScanReceipt'))->name('transactions.scan-receipt');
    Route::get('/brands', [BrandController::class, 'index'])->name('brands');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // User Settings
    Route::get('/user/settings', [UserSettingController::class, 'index'])->name('user.settings');
    Route::post('/user/settings', [UserSettingController::class, 'update'])->name('user.settings.update');

    // Telegram Settings
    Route::get('/settings/telegram', [TelegramSettingsController::class, 'index'])->name('settings.telegram');
    Route::post('/settings/telegram/link', [TelegramSettingsController::class, 'link'])->name('settings.telegram.link');
    Route::post('/settings/telegram/unlink', [TelegramSettingsController::class, 'unlink'])->name('settings.telegram.unlink');
    Route::post('/settings/telegram/generate-code', [TelegramSettingsController::class, 'generateCode'])->name('settings.telegram.generate-code');

    // Session Management
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::delete('/sessions', [SessionController::class, 'destroyAll'])->name('sessions.destroy-all');

    // Reports
    Route::get('/report', [ReportController::class, 'index'])->name('reports.index');

    // Exports
    Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
    Route::prefix('exports')->group(function () {
        Route::get('/transactions', [ExportController::class, 'transactions'])->name('exports.transactions');
        Route::get('/report', [ExportController::class, 'report'])->name('exports.report');
    });
});
