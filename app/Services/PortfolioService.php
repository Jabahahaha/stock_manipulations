<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    /**
     * Get all current holdings for a user, computed from transactions.
     */
    public function getHoldings(User $user): Collection
    {
        $positions = $user->transactions()
            ->select('stock_id',
                DB::raw("SUM(CASE WHEN type = 'buy' THEN quantity ELSE 0 END) as total_bought"),
                DB::raw("SUM(CASE WHEN type = 'sell' THEN quantity ELSE 0 END) as total_sold")
            )
            ->groupBy('stock_id')
            ->havingRaw("SUM(CASE WHEN type = 'buy' THEN quantity ELSE 0 END) - SUM(CASE WHEN type = 'sell' THEN quantity ELSE 0 END) > 0")
            ->get();

        return $positions->map(function ($position) use ($user) {
            $quantity = $position->total_bought - $position->total_sold;
            $averageCost = $this->calculateAverageCost($user, $position->stock_id);
            $stock = Stock::find($position->stock_id);

            return (object) [
                'stock_id' => $position->stock_id,
                'symbol' => $stock->symbol,
                'company_name' => $stock->company_name,
                'quantity' => $quantity,
                'average_cost' => $averageCost,
            ];
        });
    }

    /**
     * Get the current quantity of a specific stock for a user.
     */
    public function getQuantity(User $user, int $stockId): float
    {
        $result = $user->transactions()
            ->where('stock_id', $stockId)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'buy' THEN quantity ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN type = 'sell' THEN quantity ELSE 0 END), 0) as net_quantity
            ")
            ->first();

        return (float) ($result->net_quantity ?? 0);
    }

    /**
     * Calculate weighted average cost of all buy transactions for a stock.
     */
    public function calculateAverageCost(User $user, int $stockId): float
    {
        $buys = $user->transactions()
            ->where('stock_id', $stockId)
            ->where('type', 'buy')
            ->get();

        if ($buys->isEmpty()) {
            return 0;
        }

        $totalCost = $buys->sum('total_amount');
        $totalQuantity = $buys->sum('quantity');

        return $totalQuantity > 0 ? round($totalCost / $totalQuantity, 2) : 0;
    }
}
