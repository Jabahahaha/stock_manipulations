<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $followedIds = $request->user()->following()->pluck('users.id');

        $trades = Transaction::with(['user', 'stock'])
            ->whereIn('user_id', $followedIds)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('feed.index', compact('trades'));
    }
}
