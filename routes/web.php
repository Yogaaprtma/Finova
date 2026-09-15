<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('accounts', App\Http\Controllers\AccountController::class);
    Route::resource('transactions', App\Http\Controllers\TransactionController::class);
});

require __DIR__ . '/settings.php';
