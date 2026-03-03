<?php

namespace App\Http\Controllers;

use App\Services\FinnhubService;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index(Request $request, FinnhubService $api)
    {
        $watchlist = $request->user()->watchlists()->latest()->get();

        $quotes = $api->quotes($watchlist->pluck('symbol')->toArray());

        $items = $watchlist->map(function ($item) use ($quotes) {
            $quote = $quotes[$item->symbol] ?? null;

            return [
                'id' => $item->id,
                'symbol' => $item->symbol,
                'company_name' => $item->company_name,
                'current_price' => $quote['price'] ?? null,
                'change' => $quote['change'] ?? null,
                'change_percent' => $quote['change_percent'] ?? null,
                'alert_price' => $item->alert_price,
                'alert_condition' => $item->alert_condition,
                'alert_triggered' => $item->alert_triggered,
            ];
        });

        // Search for stocks to add
        $query = $request->input('query');
        $searchResults = [];
        if ($query) {
            $searchResults = $api->search($query);
        }

        return view('watchlist.index', compact('items', 'query', 'searchResults'));
    }

    public function prices(Request $request, FinnhubService $api)
    {
        $watchlist = $request->user()->watchlists()->get();
        $quotes = $api->quotes($watchlist->pluck('symbol')->toArray());

        $items = $watchlist->map(function ($item) use ($quotes) {
            $quote = $quotes[$item->symbol] ?? null;
            $price = $quote['price'] ?? null;

            // Check if alert should trigger
            if ($price !== null && $item->alert_price && !$item->alert_triggered) {
                $triggered = false;
                if ($item->alert_condition === 'above' && $price >= $item->alert_price) {
                    $triggered = true;
                } elseif ($item->alert_condition === 'below' && $price <= $item->alert_price) {
                    $triggered = true;
                }
                if ($triggered) {
                    $item->update(['alert_triggered' => true]);
                }
            }

            return [
                'id' => $item->id,
                'symbol' => $item->symbol,
                'current_price' => $price,
                'change' => $quote['change'] ?? null,
                'change_percent' => $quote['change_percent'] ?? null,
                'alert_triggered' => $item->alert_triggered,
            ];
        });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'company_name' => 'required|string',
        ]);

        $user = $request->user();

        if ($user->watchlists()->where('symbol', $request->symbol)->exists()) {
            return back()->withErrors(['symbol' => $request->symbol . ' is already on your watchlist.']);
        }

        $user->watchlists()->create([
            'symbol' => $request->symbol,
            'company_name' => $request->company_name,
        ]);

        return back()->with('success', $request->symbol . ' added to watchlist.');
    }

    public function updateAlert(Request $request, $id)
    {
        $request->validate([
            'alert_price' => 'required|numeric|min:0.01',
            'alert_condition' => 'required|in:above,below',
        ]);

        $item = $request->user()->watchlists()->findOrFail($id);

        $item->update([
            'alert_price' => $request->alert_price,
            'alert_condition' => $request->alert_condition,
            'alert_triggered' => false,
        ]);

        return back()->with('success', 'Alert set for ' . $item->symbol . ': ' . $request->alert_condition . ' $' . number_format($request->alert_price, 2));
    }

    public function removeAlert(Request $request, $id)
    {
        $item = $request->user()->watchlists()->findOrFail($id);

        $item->update([
            'alert_price' => null,
            'alert_condition' => null,
            'alert_triggered' => false,
        ]);

        return back()->with('success', 'Alert removed for ' . $item->symbol . '.');
    }

    public function destroy(Request $request, $id)
    {
        $item = $request->user()->watchlists()->findOrFail($id);
        $symbol = $item->symbol;
        $item->delete();

        return redirect()->route('watchlist.index')->with('success', $symbol . ' removed from watchlist.');
    }
}
