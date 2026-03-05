<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()
            ->transactions()
            ->with('stock');

        $filter = $request->query('type');
        if (in_array($filter, ['buy', 'sell'])) {
            $query->where('type', $filter);
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();

        return view('transactions.index', compact('transactions', 'filter'));
    }
}
