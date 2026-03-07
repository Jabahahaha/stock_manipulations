<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Stocks
        $stocks = collect([
            ['symbol' => 'AAPL', 'company_name' => 'Apple Inc.'],
            ['symbol' => 'GOOGL', 'company_name' => 'Alphabet Inc.'],
            ['symbol' => 'MSFT', 'company_name' => 'Microsoft Corporation'],
            ['symbol' => 'TSLA', 'company_name' => 'Tesla Inc.'],
            ['symbol' => 'AMZN', 'company_name' => 'Amazon.com Inc.'],
            ['symbol' => 'NVDA', 'company_name' => 'NVIDIA Corporation'],
            ['symbol' => 'META', 'company_name' => 'Meta Platforms Inc.'],
        ])->map(fn ($s) => Stock::firstOrCreate(['symbol' => $s['symbol']], $s));

        // Filler traders with trades
        $traders = [
            ['name' => 'Warren B.', 'email' => 'warren@example.com'],
            ['name' => 'Cathy Wood', 'email' => 'cathy@example.com'],
            ['name' => 'Jim Simons', 'email' => 'jim@example.com'],
            ['name' => 'Ray Dalio', 'email' => 'ray@example.com'],
            ['name' => 'Nancy Pelosi', 'email' => 'nancy@example.com'],
        ];

        $tradeData = [
            // Warren: value investor, big AAPL and MSFT positions
            0 => [
                ['stock' => 'AAPL', 'type' => 'buy', 'qty' => 20, 'price' => 178.50, 'days_ago' => 30],
                ['stock' => 'AAPL', 'type' => 'buy', 'qty' => 10, 'price' => 182.00, 'days_ago' => 20],
                ['stock' => 'MSFT', 'type' => 'buy', 'qty' => 15, 'price' => 410.00, 'days_ago' => 25],
                ['stock' => 'GOOGL', 'type' => 'buy', 'qty' => 8, 'price' => 155.00, 'days_ago' => 15],
            ],
            // Cathy: tech-heavy, buys TSLA and NVDA
            1 => [
                ['stock' => 'TSLA', 'type' => 'buy', 'qty' => 12, 'price' => 245.00, 'days_ago' => 28],
                ['stock' => 'NVDA', 'type' => 'buy', 'qty' => 10, 'price' => 880.00, 'days_ago' => 22],
                ['stock' => 'TSLA', 'type' => 'sell', 'qty' => 5, 'price' => 260.00, 'days_ago' => 10],
                ['stock' => 'META', 'type' => 'buy', 'qty' => 6, 'price' => 505.00, 'days_ago' => 5],
            ],
            // Jim: diversified, frequent trader
            2 => [
                ['stock' => 'AAPL', 'type' => 'buy', 'qty' => 5, 'price' => 175.00, 'days_ago' => 27],
                ['stock' => 'GOOGL', 'type' => 'buy', 'qty' => 10, 'price' => 150.00, 'days_ago' => 24],
                ['stock' => 'MSFT', 'type' => 'buy', 'qty' => 8, 'price' => 405.00, 'days_ago' => 18],
                ['stock' => 'AAPL', 'type' => 'sell', 'qty' => 5, 'price' => 185.00, 'days_ago' => 12],
                ['stock' => 'NVDA', 'type' => 'buy', 'qty' => 3, 'price' => 900.00, 'days_ago' => 7],
                ['stock' => 'AMZN', 'type' => 'buy', 'qty' => 5, 'price' => 185.00, 'days_ago' => 3],
            ],
            // Ray: macro bets, AMZN and GOOGL
            3 => [
                ['stock' => 'AMZN', 'type' => 'buy', 'qty' => 15, 'price' => 180.00, 'days_ago' => 26],
                ['stock' => 'GOOGL', 'type' => 'buy', 'qty' => 12, 'price' => 152.00, 'days_ago' => 21],
                ['stock' => 'AMZN', 'type' => 'sell', 'qty' => 5, 'price' => 190.00, 'days_ago' => 8],
                ['stock' => 'META', 'type' => 'buy', 'qty' => 4, 'price' => 510.00, 'days_ago' => 2],
            ],
            // Nancy: NVDA and TSLA
            4 => [
                ['stock' => 'NVDA', 'type' => 'buy', 'qty' => 8, 'price' => 870.00, 'days_ago' => 29],
                ['stock' => 'TSLA', 'type' => 'buy', 'qty' => 10, 'price' => 240.00, 'days_ago' => 19],
                ['stock' => 'NVDA', 'type' => 'buy', 'qty' => 5, 'price' => 910.00, 'days_ago' => 9],
                ['stock' => 'AAPL', 'type' => 'buy', 'qty' => 12, 'price' => 180.00, 'days_ago' => 4],
                ['stock' => 'TSLA', 'type' => 'sell', 'qty' => 3, 'price' => 255.00, 'days_ago' => 1],
            ],
        ];

        foreach ($traders as $i => $data) {
            $user = User::factory()->create($data);

            foreach ($tradeData[$i] as $trade) {
                $stock = $stocks->firstWhere('symbol', $trade['stock']);
                $total = $trade['qty'] * $trade['price'];

                Transaction::create([
                    'user_id' => $user->id,
                    'stock_id' => $stock->id,
                    'type' => $trade['type'],
                    'quantity' => $trade['qty'],
                    'price_per_share' => $trade['price'],
                    'total_amount' => $total,
                    'created_at' => now()->subDays($trade['days_ago']),
                    'updated_at' => now()->subDays($trade['days_ago']),
                ]);

                // Adjust balance
                if ($trade['type'] === 'buy') {
                    $user->decrement('balance', $total);
                } else {
                    $user->increment('balance', $total);
                }
            }
        }
    }
}
