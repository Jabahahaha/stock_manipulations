<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AlphaVantageService
{
    protected string $baseUrl = 'https://www.alphavantage.co/query';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.alpha_vantage.key');
    }

    /**
     * Search for stocks by keyword (company name or symbol).
     */
    public function search(string $keyword): array
    {
        $response = Http::get($this->baseUrl, [
            'function' => 'SYMBOL_SEARCH',
            'keywords' => $keyword,
            'apikey' => $this->apiKey,
        ]);

        return $response->json('bestMatches', []);
    }

    /**
     * Get a live quote for a stock symbol.
     * Returns null if the symbol is not found or API fails.
     */
    public function quote(string $symbol): ?array
    {
        $response = Http::get($this->baseUrl, [
            'function' => 'GLOBAL_QUOTE',
            'symbol' => $symbol,
            'apikey' => $this->apiKey,
        ]);

        $data = $response->json('Global Quote', []);

        if (empty($data)) {
            return null;
        }

        return [
            'symbol' => $data['01. symbol'] ?? $symbol,
            'price' => (float) ($data['05. price'] ?? 0),
            'change' => (float) ($data['09. change'] ?? 0),
            'change_percent' => $data['10. change percent'] ?? '0%',
            'volume' => (int) ($data['06. volume'] ?? 0),
            'latest_trading_day' => $data['07. latest trading day'] ?? null,
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
