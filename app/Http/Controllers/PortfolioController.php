<?php

namespace App\Http\Controllers;

use App\Services\FinnhubService;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $request, FinnhubService $api, PortfolioService $portfolio)
    {
        $user = $request->user();
        $holdings = $portfolio->getHoldings($user);

        // Fetch live prices for all held symbols
        $quotes = $api->quotes($holdings->pluck('symbol')->toArray());

        $portfolioItems = $holdings->map(function ($holding) use ($quotes) {
            $currentPrice = $quotes[$holding->symbol]['price'] ?? null;
            $currentValue = $currentPrice ? $currentPrice * $holding->quantity : null;
            $costBasis = $holding->average_cost * $holding->quantity;
            $gainLoss = $currentValue !== null ? $currentValue - $costBasis : null;
            $gainLossPercent = $costBasis > 0 && $gainLoss !== null
                ? ($gainLoss / $costBasis) * 100
                : null;

            return [
                'symbol' => $holding->symbol,
                'company_name' => $holding->company_name,
                'quantity' => $holding->quantity,
                'average_cost' => $holding->average_cost,
                'current_price' => $currentPrice,
                'current_value' => $currentValue,
                'cost_basis' => $costBasis,
                'gain_loss' => $gainLoss,
                'gain_loss_percent' => $gainLossPercent,
            ];
        });

        $totalValue = $portfolioItems->sum('current_value');
        $totalCostBasis = $portfolioItems->sum('cost_basis');
        $totalGainLoss = $portfolioItems->whereNotNull('gain_loss')->sum('gain_loss');
        $totalGainLossPercent = $totalCostBasis > 0 ? ($totalGainLoss / $totalCostBasis) * 100 : 0;
        $cashBalance = $user->balance;
        $accountValue = $totalValue + $cashBalance;

        return view('portfolio.index', compact(
            'portfolioItems',
            'totalValue',
            'totalCostBasis',
            'totalGainLoss',
            'totalGainLossPercent',
            'cashBalance',
            'accountValue',
        ));
    }
}
