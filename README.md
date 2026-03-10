# StockTrader

A paper trading web application for US stocks with real-time market data.

## Features

- **Trading** — Buy and sell US stocks with simulated money
- **Portfolio** — Track holdings, gain/loss, and account value
- **Watchlist** — Monitor stocks with configurable price alerts
- **Transaction History** — Full trade history with summary stats
- **Copy Trading** — Follow and automatically copy other traders
- **Social Feed** — See recent trades from the community
- **Admin Panel** — User management at `/admin` (admin accounts only)

## Tech Stack

- Laravel 12
- Tailwind CSS
- Alpine.js
- SQLite
- Finnhub API (real-time quotes)
- Yahoo Finance (historical charts)

## Setup

```bash
git clone <repo-url> && cd stocktrader
composer setup
```

This runs `composer install`, copies `.env.example`, generates an app key, runs migrations, and builds frontend assets.

### Environment Variables

Add your Finnhub API key to `.env`:

```
FINNHUB_KEY=your_api_key_here
```

Get a free key at [finnhub.io](https://finnhub.io/).

## Running

### Dev Server

```bash
composer dev
```

Starts the Laravel server, queue worker, and Vite dev server concurrently.

### Scheduler (Cron)

The app schedules `alerts:check` every 5 minutes for watchlist price alerts. Add this cron entry on your server:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Tests

```bash
composer test
```

Runs Pint (linter) and Pest (tests).

## Default Accounts

After running migrations with the seeder, the following accounts are available:

| Email | Password | Role |
|---|---|---|
| test@example.com | password | user |
| admin@example.com | password | admin |
