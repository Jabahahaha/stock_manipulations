<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/toggle-ban', [UserController::class, 'toggleBan'])->name('users.toggleBan');
    Route::post('/users/{user}/adjust-balance', [UserController::class, 'adjustBalance'])->name('users.adjustBalance');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Placeholders for sidebar navigation — will be replaced in later parts
    Route::get('/stocks', fn () => abort(404))->name('stocks.index');
    Route::get('/copy-trading', fn () => abort(404))->name('copyTrading.index');
    Route::get('/announcements', fn () => abort(404))->name('announcements.index');
});
