<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = $request->user()
            ->transactions()
            ->latest()
            ->paginate(15);

        return view('transactions.index', compact('transactions'));
    }
}
