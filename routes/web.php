<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
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
});

require __DIR__.'/settings.php';
