<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FinnhubService
{
    protected string $baseUrl = 'https://finnhub.io/api/v1';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.finnhub.key');
    }

    /**
     * Search for stocks by keyword.
     */
    public function search(string $keyword): array
    {
        $response = Http::get("{$this->baseUrl}/search", [
            'q' => $keyword,
            'token' => $this->apiKey,
        ]);

        $results = $response->json('result', []);

        // Filter to US stocks only (non-US symbols contain a dot, e.g. VOW.DE)
        // and map to a consistent format
        return collect($results)
            ->filter(fn ($item) => !str_contains($item['symbol'] ?? '', '.'))
            ->map(fn ($item) => [
                '1. symbol' => $item['symbol'] ?? '',
                '2. name' => $item['description'] ?? '',
                '3. type' => $item['type'] ?? '',
                '4. region' => 'United States',
            ])->values()->toArray();
    }

    /**
     * Get a live quote for a stock symbol.
     */
    public function quote(string $symbol): ?array
    {
        $response = Http::get("{$this->baseUrl}/quote", [
            'symbol' => $symbol,
            'token' => $this->apiKey,
        ]);

        $data = $response->json();

        // Finnhub returns c=0 for invalid symbols
        if (empty($data) || ($data['c'] ?? 0) == 0) {
            return null;
        }

        return [
            'symbol' => $symbol,
            'price' => (float) $data['c'],
            'change' => (float) ($data['d'] ?? 0),
            'change_percent' => isset($data['dp']) ? round($data['dp'], 2) . '%' : '0%',
            'volume' => 0,
            'latest_trading_day' => now()->toDateString(),
        ];
    }

    /**
     * Get historical price data via Yahoo Finance chart API.
     */
    public function candles(string $symbol, string $range, string $interval): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
            'range' => $range,
            'interval' => $interval,
        ]);

        $data = $response->json();
        $result = $data['chart']['result'][0] ?? null;

        if (!$result || empty($result['timestamp'])) {
            return null;
        }

        return [
            't' => $result['timestamp'],
            'c' => $result['indicators']['quote'][0]['close'] ?? [],
        ];
    }

    /**
     * Get live quotes for multiple symbols.
     */
    public function quotes(array $symbols): array
    {
        $results = [];

        foreach ($symbols as $symbol) {
            $quote = $this->quote($symbol);
            if ($quote) {
                $results[$symbol] = $quote;
            }
        }

        return $results;
    }
}
