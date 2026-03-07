<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('transactions');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function show(User $user, PortfolioService $portfolio)
    {
        $user->loadCount(['transactions', 'followers', 'following']);

        $holdings = $portfolio->getHoldings($user);

        $recentTransactions = $user->transactions()
            ->with('stock')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $copyTradingSettings = $user->copyTradingSettings()->with('trader')->get();

        return view('admin.users.show', compact('user', 'holdings', 'recentTransactions', 'copyTradingSettings'));
    }

    public function toggleBan(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot ban yourself.');
        }

        if ($user->is_admin) {
            return back()->with('error', 'You cannot ban another admin.');
        }

        $user->is_banned = !$user->is_banned;
        $user->save();

        $action = $user->is_banned ? 'banned' : 'unbanned';

        return back()->with('success', "{$user->name} has been {$action}.");
    }

    public function adjustBalance(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:255',
        ]);

        $amount = (float) $request->amount;
        $newBalance = $user->balance + $amount;

        if ($newBalance < 0) {
            return back()->with('error', "Adjustment would result in negative balance (\${$newBalance}).");
        }

        if ($amount > 0) {
            $user->increment('balance', $amount);
        } else {
            $user->decrement('balance', abs($amount));
        }

        $sign = $amount >= 0 ? '+' : '';
        $user->notifications()->create([
            'type' => 'system',
            'title' => 'Balance Adjustment',
            'message' => "Your balance was adjusted by {$sign}\${$amount}. Reason: {$request->reason}",
            'data' => [
                'amount' => $amount,
                'reason' => $request->reason,
            ],
        ]);

        return back()->with('success', "Balance adjusted by {$sign}\${$amount} for {$user->name}.");
    }
}
