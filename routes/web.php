<?php

use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('portfolio.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');

    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::post('/stocks/buy', [StockController::class, 'buy'])->name('stocks.buy');
    Route::post('/stocks/sell', [StockController::class, 'sell'])->name('stocks.sell');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::get('/watchlist/prices', [WatchlistController::class, 'prices'])->name('watchlist.prices');
    Route::post('/watchlist', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::patch('/watchlist/{id}/alert', [WatchlistController::class, 'updateAlert'])->name('watchlist.updateAlert');
    Route::delete('/watchlist/{id}/alert', [WatchlistController::class, 'removeAlert'])->name('watchlist.removeAlert');
    Route::delete('/watchlist/{id}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');
});

require __DIR__.'/auth.php';
