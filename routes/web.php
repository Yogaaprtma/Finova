<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AiTransactionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LiabilityController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::prefix('auth/{provider}')->whereIn('provider', ['google', 'apple', 'facebook'])->group(function () {
    Route::get('redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::match(['get', 'post'], 'callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('accounts', AccountController::class);
    Route::resource('transactions', TransactionController::class);
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::resource('categories', CategoryController::class);

    Route::patch('assets/{asset}/price', [AssetController::class, 'updatePrice'])->name('assets.price.update');
    Route::post('assets/{asset}/buy', [AssetController::class, 'buy'])->name('assets.buy');
    Route::post('assets/{asset}/sell', [AssetController::class, 'sell'])->name('assets.sell');
    Route::resource('assets', AssetController::class);

    Route::post('liabilities/{liability}/pay', [LiabilityController::class, 'pay'])->name('liabilities.pay');
    Route::post('liabilities/{liability}/increase', [LiabilityController::class, 'increase'])->name('liabilities.increase');
    Route::resource('liabilities', LiabilityController::class);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('recurring-transactions/{recurring_transaction}/toggle', [RecurringTransactionController::class, 'toggle'])->name('recurring-transactions.toggle');
    Route::post('recurring-transactions/{recurring_transaction}/generate-now', [RecurringTransactionController::class, 'generateNow'])->name('recurring-transactions.generate-now');
    Route::resource('recurring-transactions', RecurringTransactionController::class);

    Route::resource('budgets', BudgetController::class);

    Route::post('ai/parse', [AiTransactionController::class, 'parse'])
        ->middleware('throttle:ai-parsing')
        ->name('ai.parse');
    Route::post('ai/confirm', [AiTransactionController::class, 'confirm'])->name('ai.confirm');
    Route::post('ai/reject', [AiTransactionController::class, 'reject'])->name('ai.reject');
});

require __DIR__.'/settings.php';
