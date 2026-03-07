<?php

namespace App\Http\Controllers;

use App\Models\CopyTradingSetting;
use App\Models\User;
use Illuminate\Http\Request;

class TraderController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('id', '!=', $request->user()->id);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $traders = $query->withCount(['followers', 'following'])
            ->orderByDesc('followers_count')
            ->paginate(20)
            ->withQueryString();

        return view('traders.index', compact('traders', 'search'));
    }

    public function show(Request $request, User $trader)
    {
        $trader->loadCount(['followers', 'following']);

        $isFollowing = $request->user()->isFollowing($trader);

        $recentTrades = $trader->transactions()
            ->with('stock')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $copyTradingSetting = CopyTradingSetting::where('user_id', $request->user()->id)
            ->where('trader_id', $trader->id)
            ->first();

        return view('traders.show', compact('trader', 'isFollowing', 'recentTrades', 'copyTradingSetting'));
    }

    public function follow(Request $request, User $trader)
    {
        if ($trader->id === $request->user()->id) {
            return back()->with('error', 'You cannot follow yourself.');
        }

        $request->user()->following()->syncWithoutDetaching([$trader->id]);

        return back()->with('success', "You are now following {$trader->name}.");
    }

    public function unfollow(Request $request, User $trader)
    {
        $request->user()->following()->detach($trader->id);

        return back()->with('success', "You have unfollowed {$trader->name}.");
    }
}
