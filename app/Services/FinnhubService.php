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

        // Map to a consistent format
        return collect($results)->map(fn ($item) => [
            '1. symbol' => $item['symbol'] ?? '',
            '2. name' => $item['description'] ?? '',
            '3. type' => $item['type'] ?? '',
            '4. region' => 'United States',
        ])->toArray();
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
