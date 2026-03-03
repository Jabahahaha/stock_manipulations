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

        return view('watchlist.index', compact('items'));
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

    public function destroy(Request $request, $id)
    {
        $item = $request->user()->watchlists()->findOrFail($id);
        $symbol = $item->symbol;
        $item->delete();

        return redirect()->route('watchlist.index')->with('success', $symbol . ' removed from watchlist.');
    }
}
