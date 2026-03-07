<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CopyTradingSetting;
use App\Models\Follow;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $newUsersThisWeek = User::where('created_at', '>=', now()->subDays(7))->count();
        $bannedUsers = User::where('is_banned', true)->count();

        $totalTrades = Transaction::count();
        $totalVolume = Transaction::sum('total_amount');
        $volumeThisWeek = Transaction::where('created_at', '>=', now()->subDays(7))->sum('total_amount');
        $tradesToday = Transaction::whereDate('created_at', today())->count();

        $activeCopyTraders = CopyTradingSetting::where('is_active', true)->count();
        $totalStocks = Stock::count();
        $totalFollows = Follow::count();

        $recentTrades = Transaction::with(['user', 'stock'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'newUsersThisWeek',
            'bannedUsers',
            'totalTrades',
            'totalVolume',
            'volumeThisWeek',
            'tradesToday',
            'activeCopyTraders',
            'totalStocks',
            'totalFollows',
            'recentTrades',
        ));
    }
}
