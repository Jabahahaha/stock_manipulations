<?php

namespace App\Http\Controllers;

use App\Models\Holding;
use App\Models\Transaction;
use App\Services\FinnhubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request, FinnhubService $api)
    {
        $query = $request->input('query');
        $results = [];
        $quote = null;
        $symbol = $request->input('symbol');

        // If a symbol is selected, fetch its quote; otherwise search
        if ($symbol) {
            $quote = $api->quote($symbol);
            if ($quote) {
                $quote['company_name'] = $request->input('name', $symbol);
            }
        } elseif ($query) {
            $results = $api->search($query);
        }

        return view('stocks.index', compact('query', 'results', 'quote', 'symbol'));
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

            // Recalculate average cost: ((old_qty * old_avg) + (new_qty * new_price)) / total_qty
            $holding = $user->holdings()->where('symbol', $request->symbol)->first();
            if ($holding) {
                $newQuantity = $holding->quantity + $request->quantity;
                $newAvgCost = (($holding->quantity * $holding->average_cost) + ($request->quantity * $request->price)) / $newQuantity;
                $holding->update([
                    'quantity' => $newQuantity,
                    'average_cost' => round($newAvgCost, 2),
                ]);
            } else {
                $user->holdings()->create([
                    'symbol' => $request->symbol,
                    'company_name' => $request->company_name,
                    'quantity' => $request->quantity,
                    'average_cost' => $request->price,
                ]);
            }

            $user->transactions()->create([
                'symbol' => $request->symbol,
                'company_name' => $request->company_name,
                'type' => 'buy',
                'quantity' => $request->quantity,
                'price_per_share' => $request->price,
                'total_amount' => $totalCost,
            ]);
        });

        return back()->with('success', "Bought {$request->quantity} shares of {$request->symbol} for \${$totalCost}.");
    }

    public function sell(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'company_name' => 'required|string',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|numeric|min:0.000001',
        ]);

        $user = $request->user();
        $holding = $user->holdings()->where('symbol', $request->symbol)->first();

        if (!$holding || $holding->quantity < $request->quantity) {
            return back()->withErrors(['quantity' => 'You don\'t own enough shares to sell.']);
        }

        $totalProceeds = round($request->price * $request->quantity, 2);

        DB::transaction(function () use ($user, $request, $holding, $totalProceeds) {
            $user->increment('balance', $totalProceeds);

            $newQuantity = $holding->quantity - $request->quantity;
            if ($newQuantity <= 0) {
                $holding->delete();
            } else {
                $holding->update(['quantity' => $newQuantity]);
            }

            $user->transactions()->create([
                'symbol' => $request->symbol,
                'company_name' => $request->company_name,
                'type' => 'sell',
                'quantity' => $request->quantity,
                'price_per_share' => $request->price,
                'total_amount' => $totalProceeds,
            ]);
        });

        return back()->with('success', "Sold {$request->quantity} shares of {$request->symbol} for \${$totalProceeds}.");
    }
}
