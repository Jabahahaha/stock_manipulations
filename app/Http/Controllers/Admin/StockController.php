<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Stock::withCount('transactions')
            ->withSum('transactions as total_volume', 'total_amount');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('symbol', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderByDesc('transactions_count')->paginate(25)->withQueryString();

        $totalStocks = Stock::count();
        $mostTraded = Stock::withCount('transactions')
            ->orderByDesc('transactions_count')
            ->first();
        $highestVolume = Stock::withSum('transactions as total_volume', 'total_amount')
            ->orderByDesc('total_volume')
            ->first();

        return view('admin.stocks.index', compact(
            'stocks', 'search', 'totalStocks', 'mostTraded', 'highestVolume',
        ));
    }

    public function show(Stock $stock)
    {
        $stock->loadCount('transactions');

        $totalVolume = $stock->transactions()->sum('total_amount');
        $buyVolume = $stock->transactions()->where('type', 'buy')->sum('total_amount');
        $sellVolume = $stock->transactions()->where('type', 'sell')->sum('total_amount');
        $uniqueTraders = $stock->transactions()->distinct('user_id')->count('user_id');

        $recentTransactions = $stock->transactions()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $topTraders = $stock->transactions()
            ->select('user_id', DB::raw('SUM(total_amount) as volume'), DB::raw('COUNT(*) as trades'))
            ->groupBy('user_id')
            ->orderByDesc('volume')
            ->limit(10)
            ->with('user')
            ->get();

        return view('admin.stocks.show', compact(
            'stock', 'totalVolume', 'buyVolume', 'sellVolume',
            'uniqueTraders', 'recentTransactions', 'topTraders',
        ));
    }
}
