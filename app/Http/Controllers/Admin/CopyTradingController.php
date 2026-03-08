<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CopyTradingSetting;
use Illuminate\Http\Request;

class CopyTradingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = CopyTradingSetting::with(['user', 'trader']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('trader', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $settings = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $totalPairs = CopyTradingSetting::count();
        $activePairs = CopyTradingSetting::where('is_active', true)->count();
        $totalAllocated = CopyTradingSetting::where('is_active', true)->sum('amount_per_trade');

        return view('admin.copy-trading.index', compact(
            'settings', 'search', 'status',
            'totalPairs', 'activePairs', 'totalAllocated',
        ));
    }
}
