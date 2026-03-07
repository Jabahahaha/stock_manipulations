<?php

namespace App\Services;

use App\Models\CopyTradingSetting;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CopyTradingService
{
    public function __construct(private PortfolioService $portfolio) {}

    public function executeCopyTrades(User $trader, Stock $stock, string $type, float $pricePerShare): void
    {
        $copiers = CopyTradingSetting::where('trader_id', $trader->id)
            ->where('is_active', true)
            ->get();

        foreach ($copiers as $setting) {
            $copier = $setting->user;
            $quantity = round($setting->amount_per_trade / $pricePerShare, 6);

            if ($quantity <= 0) {
                continue;
            }

            if ($type === 'buy') {
                $this->executeBuy($copier, $stock, $quantity, $pricePerShare, $trader);
            } else {
                $this->executeSell($copier, $stock, $quantity, $pricePerShare, $trader);
            }
        }
    }

    private function executeBuy(User $copier, Stock $stock, float $quantity, float $price, User $trader): void
    {
        $totalCost = round($price * $quantity, 2);

        if ($totalCost > $copier->balance) {
            $copier->notifications()->create([
                'type' => 'copy_trade',
                'title' => "Copy trade failed — {$stock->symbol}",
                'message' => "Insufficient balance to copy {$trader->name}'s buy of {$stock->symbol}. Needed \${$totalCost}, had \${$copier->balance}.",
                'data' => [
                    'symbol' => $stock->symbol,
                    'trader' => $trader->name,
                    'action' => 'buy',
                    'status' => 'failed',
                ],
            ]);
            return;
        }

        DB::transaction(function () use ($copier, $stock, $quantity, $price, $totalCost) {
            $copier->decrement('balance', $totalCost);

            $copier->transactions()->create([
                'stock_id' => $stock->id,
                'type' => 'buy',
                'quantity' => $quantity,
                'price_per_share' => $price,
                'total_amount' => $totalCost,
            ]);
        });

        $copier->notifications()->create([
            'type' => 'copy_trade',
            'title' => "Copied buy — {$stock->symbol}",
            'message' => "Copied {$trader->name}: bought {$quantity} shares of {$stock->symbol} at \${$price} for \${$totalCost}.",
            'data' => [
                'symbol' => $stock->symbol,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $totalCost,
                'trader' => $trader->name,
                'action' => 'buy',
                'status' => 'success',
            ],
        ]);
    }

    private function executeSell(User $copier, Stock $stock, float $quantity, float $price, User $trader): void
    {
        $ownedQuantity = $this->portfolio->getQuantity($copier, $stock->id);

        if ($ownedQuantity <= 0) {
            return;
        }

        // Sell up to what the copier owns
        $sellQuantity = min($quantity, $ownedQuantity);
        $totalProceeds = round($price * $sellQuantity, 2);

        DB::transaction(function () use ($copier, $stock, $sellQuantity, $price, $totalProceeds) {
            $copier->increment('balance', $totalProceeds);

            $copier->transactions()->create([
                'stock_id' => $stock->id,
                'type' => 'sell',
                'quantity' => $sellQuantity,
                'price_per_share' => $price,
                'total_amount' => $totalProceeds,
            ]);
        });

        $copier->notifications()->create([
            'type' => 'copy_trade',
            'title' => "Copied sell — {$stock->symbol}",
            'message' => "Copied {$trader->name}: sold {$sellQuantity} shares of {$stock->symbol} at \${$price} for \${$totalProceeds}.",
            'data' => [
                'symbol' => $stock->symbol,
                'quantity' => $sellQuantity,
                'price' => $price,
                'total' => $totalProceeds,
                'trader' => $trader->name,
                'action' => 'sell',
                'status' => 'success',
            ],
        ]);
    }
}
