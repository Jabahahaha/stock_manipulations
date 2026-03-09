<?php

use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CopyTradingController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TraderController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('portfolio.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');

    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::post('/stocks/buy', [StockController::class, 'buy'])->name('stocks.buy');
    Route::post('/stocks/sell', [StockController::class, 'sell'])->name('stocks.sell');
    Route::get('/stocks/{symbol}', [StockController::class, 'show'])->where('symbol', '[A-Z0-9]{1,10}')->name('stocks.show');
    Route::get('/stocks/{symbol}/history', [StockController::class, 'history'])->where('symbol', '[A-Z0-9]{1,10}')->name('stocks.history');
    Route::get('/stocks/{symbol}/quote', [StockController::class, 'quote'])->where('symbol', '[A-Z0-9]{1,10}')->name('stocks.quote');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::get('/watchlist/prices', [WatchlistController::class, 'prices'])->name('watchlist.prices');
    Route::post('/watchlist', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::patch('/watchlist/{id}/alert', [WatchlistController::class, 'updateAlert'])->name('watchlist.updateAlert');
    Route::delete('/watchlist/{id}/alert', [WatchlistController::class, 'removeAlert'])->name('watchlist.removeAlert');
    Route::delete('/watchlist/{id}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');

    Route::get('/feed', [FeedController::class, 'index'])->name('feed.index');

    Route::get('/traders', [TraderController::class, 'index'])->name('traders.index');
    Route::get('/traders/{trader}', [TraderController::class, 'show'])->name('traders.show');
    Route::post('/traders/{trader}/follow', [TraderController::class, 'follow'])->name('traders.follow');
    Route::delete('/traders/{trader}/follow', [TraderController::class, 'unfollow'])->name('traders.unfollow');
    Route::post('/traders/{trader}/copy', [CopyTradingController::class, 'store'])->name('copy-trading.store');
    Route::delete('/traders/{trader}/copy', [CopyTradingController::class, 'destroy'])->name('copy-trading.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
});

require __DIR__.'/auth.php';
