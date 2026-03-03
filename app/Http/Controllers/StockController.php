<?php

namespace App\Http\Controllers;

use App\Services\AlphaVantageService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request, AlphaVantageService $api)
    {
        $query = $request->input('query');
        $results = [];
        $quote = null;

        if ($query) {
            $results = $api->search($query);
        }

        // If a symbol is selected, fetch its live quote
        $symbol = $request->input('symbol');
        if ($symbol) {
            $quote = $api->quote($symbol);
            $quote['company_name'] = $request->input('name', $symbol);
        }

        return view('stocks.index', compact('query', 'results', 'quote'));
    }
}
