<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'stock']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($symbol = $request->input('symbol')) {
            $query->whereHas('stock', function ($q) use ($symbol) {
                $q->where('symbol', 'like', "%{$symbol}%");
            });
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $buyVolume = Transaction::where('type', 'buy')->sum('total_amount');
        $sellVolume = Transaction::where('type', 'sell')->sum('total_amount');
        $tradesToday = Transaction::whereDate('created_at', today())->count();

        return view('admin.transactions.index', compact(
            'transactions', 'type', 'search', 'symbol',
            'buyVolume', 'sellVolume', 'tradesToday',
        ));
    }
}
