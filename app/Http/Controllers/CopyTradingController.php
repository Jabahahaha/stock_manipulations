<?php

namespace App\Http\Controllers;

use App\Models\CopyTradingSetting;
use App\Models\User;
use Illuminate\Http\Request;

class CopyTradingController extends Controller
{
    public function store(Request $request, User $trader)
    {
        if ($trader->id === $request->user()->id) {
            return back()->with('error', 'You cannot copy-trade yourself.');
        }

        $request->validate([
            'amount_per_trade' => 'required|numeric|min:1|max:999999',
        ]);

        CopyTradingSetting::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'trader_id' => $trader->id,
            ],
            [
                'amount_per_trade' => $request->amount_per_trade,
                'is_active' => true,
            ]
        );

        // Auto-follow if not already following
        $request->user()->following()->syncWithoutDetaching([$trader->id]);

        return back()->with('success', "Copy-trading enabled for {$trader->name} at \${$request->amount_per_trade} per trade.");
    }

    public function destroy(Request $request, User $trader)
    {
        CopyTradingSetting::where('user_id', $request->user()->id)
            ->where('trader_id', $trader->id)
            ->delete();

        return back()->with('success', "Copy-trading disabled for {$trader->name}.");
    }
}
