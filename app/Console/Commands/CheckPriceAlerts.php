<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Watchlist;
use App\Services\FinnhubService;
use Illuminate\Console\Command;

class CheckPriceAlerts extends Command
{
    protected $signature = 'alerts:check';
    protected $description = 'Check watchlist price alerts against live prices and mark triggered ones';

    public function handle(FinnhubService $api): int
    {
        $alerts = Watchlist::with('stock')
            ->whereNotNull('alert_price')
            ->where('alert_triggered', false)
            ->get();

        if ($alerts->isEmpty()) {
            $this->info('No active alerts to check.');
            return self::SUCCESS;
        }

        $symbols = $alerts->pluck('stock.symbol')->unique()->toArray();
        $quotes = $api->quotes($symbols);

        $triggered = 0;

        foreach ($alerts as $alert) {
            $symbol = $alert->stock->symbol;
            $price = $quotes[$symbol]['price'] ?? null;

            if ($price === null) {
                $this->warn("Could not fetch price for {$symbol}, skipping.");
                continue;
            }

            $isTriggered = false;

            if ($alert->alert_condition === 'above' && $price >= $alert->alert_price) {
                $isTriggered = true;
            } elseif ($alert->alert_condition === 'below' && $price <= $alert->alert_price) {
                $isTriggered = true;
            }

            if ($isTriggered) {
                $alert->update(['alert_triggered' => true]);

                Notification::create([
                    'user_id' => $alert->user_id,
                    'type' => 'price_alert',
                    'title' => "Price Alert: {$symbol}",
                    'message' => "{$symbol} is now \${$price} ({$alert->alert_condition} \${$alert->alert_price})",
                    'data' => [
                        'symbol' => $symbol,
                        'price' => $price,
                        'alert_price' => $alert->alert_price,
                        'condition' => $alert->alert_condition,
                    ],
                ]);

                $triggered++;
                $this->info("ALERT: {$symbol} is now \${$price} ({$alert->alert_condition} \${$alert->alert_price})");
            }
        }

        $this->info("Checked {$alerts->count()} alerts. {$triggered} triggered.");

        return self::SUCCESS;
    }
}
