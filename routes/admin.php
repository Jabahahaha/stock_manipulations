<?php

use App\Http\Controllers\Admin\CopyTradingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin', 'throttle:60,1'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/toggle-ban', [UserController::class, 'toggleBan'])->name('users.toggleBan');
    Route::post('/users/{user}/adjust-balance', [UserController::class, 'adjustBalance'])->name('users.adjustBalance');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');

    Route::get('/copy-trading', [CopyTradingController::class, 'index'])->name('copyTrading.index');

});
