<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Services\FinnhubService;
use App\Services\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request, FinnhubService $api, PortfolioService $portfolio)
    {
        $query = $request->input('query');
        $results = [];
        $quote = null;
        $symbol = $request->input('symbol');
        $currentQuantity = 0;

        // If a symbol is selected, fetch its quote; otherwise search
        if ($symbol) {
            $quote = $api->quote($symbol);
            if ($quote) {
                $quote['company_name'] = $request->input('name', $symbol);
            }

            // Compute current quantity from transactions
            $stock = Stock::where('symbol', $symbol)->first();
            if ($stock) {
                $currentQuantity = $portfolio->getQuantity($request->user(), $stock->id);
            }
        } elseif ($query) {
            $results = $api->search($query);
        }

        return view('stocks.index', compact('query', 'results', 'quote', 'symbol', 'currentQuantity'));
    }

    public function buy(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'company_name' => 'required|string',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|numeric|min:0.000001',
        ]);

        $user = $request->user();
        $totalCost = round($request->price * $request->quantity, 2);

        if ($totalCost > $user->balance) {
            return back()->withErrors(['balance' => 'Insufficient balance. You need $' . number_format($totalCost, 2) . ' but only have $' . number_format($user->balance, 2) . '.']);
        }

        DB::transaction(function () use ($user, $request, $totalCost) {
            $user->decrement('balance', $totalCost);

            $stock = Stock::firstOrCreate(
                ['symbol' => $request->symbol],
                ['company_name' => $request->company_name]
            );

            $user->transactions()->create([
                'stock_id' => $stock->id,
                'type' => 'buy',
                'quantity' => $request->quantity,
                'price_per_share' => $request->price,
                'total_amount' => $totalCost,
            ]);
        });

        return back()->with('success', "Bought {$request->quantity} shares of {$request->symbol} for \${$totalCost}.");
    }

    public function sell(Request $request, PortfolioService $portfolio)
    {
        $request->validate([
            'symbol' => 'required|string',
            'company_name' => 'required|string',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|numeric|min:0.000001',
        ]);

        $user = $request->user();
        $stock = Stock::where('symbol', $request->symbol)->first();
        $ownedQuantity = $stock ? $portfolio->getQuantity($user, $stock->id) : 0;

        if ($ownedQuantity < $request->quantity) {
            return back()->withErrors(['quantity' => 'You don\'t own enough shares to sell.']);
        }

        $totalProceeds = round($request->price * $request->quantity, 2);

        DB::transaction(function () use ($user, $request, $stock, $totalProceeds) {
            $user->increment('balance', $totalProceeds);

            $user->transactions()->create([
                'stock_id' => $stock->id,
                'type' => 'sell',
                'quantity' => $request->quantity,
                'price_per_share' => $request->price,
                'total_amount' => $totalProceeds,
            ]);
        });

        return back()->with('success', "Sold {$request->quantity} shares of {$request->symbol} for \${$totalProceeds}.");
    }
}
