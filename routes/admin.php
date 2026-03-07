<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Placeholders for sidebar navigation — will be replaced in later parts
    Route::get('/users', fn () => abort(404))->name('users.index');
    Route::get('/transactions', fn () => abort(404))->name('transactions.index');
    Route::get('/stocks', fn () => abort(404))->name('stocks.index');
    Route::get('/copy-trading', fn () => abort(404))->name('copyTrading.index');
    Route::get('/announcements', fn () => abort(404))->name('announcements.index');
});
