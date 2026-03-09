<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Services\CopyTradingService;
use App\Services\FinnhubService;
use App\Services\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request, FinnhubService $api)
    {
        $query = $request->input('query');
        $results = $query ? $api->search($query) : [];

        return view('stocks.index', compact('query', 'results'));
    }

    public function show(string $symbol, FinnhubService $api, PortfolioService $portfolio)
    {
        $quote = $api->quote($symbol);
        $stock = Stock::where('symbol', $symbol)->first();
        $companyName = $stock?->company_name ?? $symbol;
        $currentQuantity = $stock ? $portfolio->getQuantity(auth()->user(), $stock->id) : 0;
        $isWatched = $stock && auth()->user()->watchlists()->where('stock_id', $stock->id)->exists();

        if ($quote) {
            $quote['company_name'] = $companyName;
        }

        return view('stocks.show', compact('symbol', 'quote', 'currentQuantity', 'isWatched', 'companyName'));
    }

    public function quote(string $symbol, FinnhubService $api)
    {
        $data = $api->quote($symbol);

        if (!$data) {
            return response()->json(['error' => 'Quote unavailable'], 404);
        }

        return response()->json([
            'price' => $data['price'],
            'change' => $data['change'],
            'change_percent' => $data['change_percent'],
        ]);
    }

    public function history(string $symbol, Request $request, FinnhubService $api)
    {
        $range = $request->query('range', '3M');

        [$yahooRange, $interval] = match ($range) {
            '1W' => ['5d', '60m'],
            '1M' => ['1mo', '1d'],
            '1Y' => ['1y', '1wk'],
            default => ['3mo', '1d'],
        };

        $data = $api->candles($symbol, $yahooRange, $interval);

        if (!$data) {
            return response()->json(['error' => 'No data available'], 404);
        }

        // Transform to array of {x: timestamp_ms, y: close_price}
        $points = [];
        foreach ($data['t'] as $i => $timestamp) {
            if (($data['c'][$i] ?? null) === null) {
                continue;
            }
            $points[] = [
                'x' => $timestamp * 1000,
                'y' => round($data['c'][$i], 2),
            ];
        }

        return response()->json($points);
    }

    public function buy(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'company_name' => 'required|string',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|numeric|min:0.000001|max:999999',
        ]);

        $user = $request->user();
        $totalCost = round($request->price * $request->quantity, 2);

        if ($totalCost > $user->balance) {
            return redirect()->route('stocks.show', $request->symbol)
                ->withErrors(['balance' => 'Insufficient balance. You need $' . number_format($totalCost, 2) . ' but only have $' . number_format($user->balance, 2) . '.']);
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

            $user->notifications()->create([
                'type' => 'trade',
                'title' => "Bought {$request->symbol}",
                'message' => "Bought {$request->quantity} shares of {$request->symbol} at \${$request->price} per share for \${$totalCost}.",
                'data' => [
                    'symbol' => $request->symbol,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'total' => $totalCost,
                    'action' => 'buy',
                ],
            ]);
        });

        $stock = Stock::where('symbol', $request->symbol)->first();
        app(CopyTradingService::class)->executeCopyTrades($user, $stock, 'buy', $request->price);

        return redirect()->route('stocks.show', $request->symbol)
            ->with('success', "Bought {$request->quantity} shares of {$request->symbol} for \${$totalCost}.");
    }

    public function sell(Request $request, PortfolioService $portfolio)
    {
        $request->validate([
            'symbol' => 'required|string',
            'company_name' => 'required|string',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|numeric|min:0.000001|max:999999',
        ]);

        $user = $request->user();
        $stock = Stock::where('symbol', $request->symbol)->first();
        $ownedQuantity = $stock ? $portfolio->getQuantity($user, $stock->id) : 0;

        if ($ownedQuantity < $request->quantity) {
            return redirect()->route('stocks.show', $request->symbol)
                ->withErrors(['quantity' => 'You don\'t own enough shares to sell.']);
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

            $user->notifications()->create([
                'type' => 'trade',
                'title' => "Sold {$request->symbol}",
                'message' => "Sold {$request->quantity} shares of {$request->symbol} at \${$request->price} per share for \${$totalProceeds}.",
                'data' => [
                    'symbol' => $request->symbol,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'total' => $totalProceeds,
                    'action' => 'sell',
                ],
            ]);
        });

        app(CopyTradingService::class)->executeCopyTrades($user, $stock, 'sell', $request->price);

        return redirect()->route('stocks.show', $request->symbol)
            ->with('success', "Sold {$request->quantity} shares of {$request->symbol} for \${$totalProceeds}.");
    }
}
