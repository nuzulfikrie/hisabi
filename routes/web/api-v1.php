<?php

use App\Http\Controllers\Api\V1\MetricsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('api/v1')->group(function () {
    Route::apiResource('transactions', \App\Http\Controllers\Api\V1\TransactionController::class)
        ->except(['show']);

    Route::get('/brands', [\App\Http\Controllers\Api\V1\BrandController::class, 'index']);
    Route::get('/brands/all', [\App\Http\Controllers\Api\V1\BrandController::class, 'all']);
    Route::post('/brands', [\App\Http\Controllers\Api\V1\BrandController::class, 'store']);
    Route::put('/brands/{id}', [\App\Http\Controllers\Api\V1\BrandController::class, 'update']);
    Route::delete('/brands/{id}', [\App\Http\Controllers\Api\V1\BrandController::class, 'destroy']);

    Route::get('/sms', [\App\Http\Controllers\Api\V1\SmsController::class, 'index']);
    Route::post('/sms', [\App\Http\Controllers\Api\V1\SmsController::class, 'store']);
    Route::put('/sms/{id}', [\App\Http\Controllers\Api\V1\SmsController::class, 'update']);
    Route::delete('/sms/{id}', [\App\Http\Controllers\Api\V1\SmsController::class, 'destroy']);

    Route::get('/categories/all', [\App\Http\Controllers\Api\V1\CategoryController::class, 'all']);
    Route::post('/categories', [\App\Http\Controllers\Api\V1\CategoryController::class, 'store']);
    Route::put('/categories/{id}', [\App\Http\Controllers\Api\V1\CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\Api\V1\CategoryController::class, 'destroy']);

    Route::get('/budgets', [\App\Http\Controllers\Api\V1\BudgetController::class, 'index']);
    Route::post('/ai/chat', [\App\Http\Controllers\Api\V1\AIController::class, 'chat']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\V1\UserController::class, 'updateProfile']);

    Route::prefix('metrics')->group(function () {
        Route::get('/total-income', [MetricsController::class, 'totalIncome']);
        Route::get('/total-expenses', [MetricsController::class, 'totalExpenses']);
        Route::get('/total-savings', [MetricsController::class, 'totalSavings']);
        Route::get('/total-investment', [MetricsController::class, 'totalInvestment']);
        Route::get('/total-cash', [MetricsController::class, 'totalCash']);
        Route::get('/net-worth', [MetricsController::class, 'netNetWorth']);
        Route::get('/net-worth-trend', [MetricsController::class, 'netWorthTrend']);
        Route::get('/total-income-trend', [MetricsController::class, 'totalIncomeTrend']);
        Route::get('/total-expenses-trend', [MetricsController::class, 'totalExpensesTrend']);
        Route::get('/category-trend', [MetricsController::class, 'categoryTrend']);
        Route::get('/category-daily-trend', [MetricsController::class, 'categoryDailyTrend']);
        Route::get('/brand-trend', [MetricsController::class, 'brandTrend']);
        Route::get('/brand-change-rate', [MetricsController::class, 'brandChangeRate']);
        Route::get('/expenses-by-category', [MetricsController::class, 'expensesByCategory']);
        Route::get('/income-by-category', [MetricsController::class, 'incomeByCategory']);
        Route::get('/spending-by-brand', [MetricsController::class, 'spendingByBrand']);
        Route::get('/transactions-count', [MetricsController::class, 'transactionsCount']);
        Route::get('/transactions-by-category', [MetricsController::class, 'transactionsByCategory']);
        Route::get('/transactions-by-brand', [MetricsController::class, 'transactionsByBrand']);
        Route::get('/highest-transaction', [MetricsController::class, 'highestTransaction']);
        Route::get('/lowest-transaction', [MetricsController::class, 'lowestTransaction']);
        Route::get('/average-transaction', [MetricsController::class, 'averageTransaction']);
        Route::get('/transactions-std-dev', [MetricsController::class, 'transactionsStdDev']);
        Route::get('/brand-stats', [MetricsController::class, 'brandStats']);
        Route::get('/category-stats', [MetricsController::class, 'categoryStats']);
        Route::get('/circle-pack', [MetricsController::class, 'circlePack']);
    });
});
