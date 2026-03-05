<?php

use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinnhubService;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
});

// --- Search Page ---

test('stocks page requires authentication', function () {
    $this->get(route('stocks.index'))->assertRedirect(route('login'));
});

test('stocks page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('stocks.index'))
        ->assertOk()
        ->assertSee('Stock Search');
});

test('stocks page shows search results', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('search')->with('AAPL')->andReturn([
        ['1. symbol' => 'AAPL', '2. name' => 'Apple Inc', '3. type' => 'Equity', '4. region' => 'United States'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('stocks.index', ['query' => 'AAPL']))
        ->assertOk()
        ->assertSee('Apple Inc')
        ->assertSee('View Quote');
});

// --- Detail Page ---

test('stock detail page requires authentication', function () {
    $this->get(route('stocks.show', 'AAPL'))->assertRedirect(route('login'));
});

test('stock detail page shows quote and trade forms', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quote')->with('AAPL')->andReturn([
        'symbol' => 'AAPL',
        'price' => 150.00,
        'change' => 2.50,
        'change_percent' => '1.69%',
        'volume' => 50000000,
        'latest_trading_day' => '2026-03-03',
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('stocks.show', 'AAPL'))
        ->assertOk()
        ->assertSee('$150.00')
        ->assertSee('Buy')
        ->assertSee('Sell');
});

test('stock detail page handles null quote gracefully', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quote')->with('INVALID')->andReturn(null);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('stocks.show', 'INVALID'))
        ->assertOk()
        ->assertSee('Could not fetch quote');
});

test('stock detail page shows watchlist button', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quote')->with('AAPL')->andReturn([
        'symbol' => 'AAPL',
        'price' => 150.00,
        'change' => 2.50,
        'change_percent' => '1.69%',
        'volume' => 50000000,
        'latest_trading_day' => '2026-03-03',
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('stocks.show', 'AAPL'))
        ->assertOk()
        ->assertSee('Add to Watchlist');
});

test('stock detail page shows watching badge when stock is in watchlist', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->watchlists()->create(['stock_id' => $stock->id]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quote')->with('AAPL')->andReturn([
        'symbol' => 'AAPL',
        'price' => 150.00,
        'change' => 2.50,
        'change_percent' => '1.69%',
        'volume' => 50000000,
        'latest_trading_day' => '2026-03-03',
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('stocks.show', 'AAPL'))
        ->assertOk()
        ->assertSee('Watching');
});

// --- History ---

test('stock history endpoint returns candle data as json', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('candles')->andReturn([
        't' => [1709500800, 1709587200],
        'c' => [150.00, 152.50],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->getJson(route('stocks.history', 'AAPL'))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['y' => 150.00])
        ->assertJsonFragment(['y' => 152.50]);
});

test('stock history endpoint returns 404 when no data', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('candles')->andReturn(null);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->getJson(route('stocks.history', 'INVALID'))
        ->assertNotFound();
});

// --- Buy ---

test('user can buy stock', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 100.00,
            'quantity' => 10,
        ])
        ->assertRedirect(route('stocks.show', 'AAPL'))
        ->assertSessionHas('success');

    expect((float) $this->user->fresh()->balance)->toEqual(9000.00);

    $tx = Transaction::where('user_id', $this->user->id)->first();
    $tx->load('stock');
    expect($tx->stock->symbol)->toBe('AAPL');
    expect($tx->type)->toBe('buy');
    expect((float) $tx->quantity)->toEqual(10.0);
    expect((float) $tx->total_amount)->toEqual(1000.0);
});

test('buying stock twice accumulates transactions', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 100.00,
            'quantity' => 10,
        ]);

    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 200.00,
            'quantity' => 10,
        ]);

    $stock = Stock::where('symbol', 'AAPL')->first();
    $txCount = Transaction::where('user_id', $this->user->id)
        ->where('stock_id', $stock->id)
        ->where('type', 'buy')
        ->count();
    expect($txCount)->toBe(2);
    expect((float) $this->user->fresh()->balance)->toEqual(7000.00);
});

test('user cannot buy stock with insufficient balance', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 100.00,
            'quantity' => 200,
        ])
        ->assertRedirect(route('stocks.show', 'AAPL'))
        ->assertSessionHasErrors('balance');

    expect((float) $this->user->fresh()->balance)->toEqual(10000.00);
    expect(Transaction::where('user_id', $this->user->id)->count())->toBe(0);
});

// --- Sell ---

test('user can sell stock', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 100.00,
        'total_amount' => 1000.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 5,
        ])
        ->assertRedirect(route('stocks.show', 'AAPL'))
        ->assertSessionHas('success');

    expect((float) $this->user->fresh()->balance)->toEqual(10750.00);
});

test('selling all shares works correctly', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 100.00,
        'total_amount' => 1000.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 10,
        ]);

    expect((float) $this->user->fresh()->balance)->toEqual(11500.00);

    $sellTx = Transaction::where('user_id', $this->user->id)->where('type', 'sell')->first();
    expect((float) $sellTx->quantity)->toEqual(10.0);
});

test('user cannot sell more shares than owned', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 100.00,
        'total_amount' => 500.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 10,
        ])
        ->assertRedirect(route('stocks.show', 'AAPL'))
        ->assertSessionHasErrors('quantity');
});

test('user cannot sell stock they dont own', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 1,
        ])
        ->assertRedirect(route('stocks.show', 'AAPL'))
        ->assertSessionHasErrors('quantity');
});

// --- Transaction Records ---

test('buy creates transaction record', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'TSLA',
            'company_name' => 'Tesla Inc',
            'price' => 250.00,
            'quantity' => 2,
        ]);

    $tx = Transaction::where('user_id', $this->user->id)->first();
    $tx->load('stock');
    expect($tx->stock->symbol)->toBe('TSLA');
    expect($tx->stock->company_name)->toBe('Tesla Inc');
    expect($tx->type)->toBe('buy');
    expect((float) $tx->price_per_share)->toEqual(250.0);
    expect((float) $tx->total_amount)->toEqual(500.0);
});

test('sell creates transaction record', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 200.00,
        'total_amount' => 1000.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'TSLA',
            'company_name' => 'Tesla Inc',
            'price' => 300.00,
            'quantity' => 3,
        ]);

    $tx = Transaction::where('user_id', $this->user->id)->where('type', 'sell')->first();
    $tx->load('stock');
    expect($tx->stock->symbol)->toBe('TSLA');
    expect($tx->type)->toBe('sell');
    expect((float) $tx->price_per_share)->toEqual(300.0);
    expect((float) $tx->total_amount)->toEqual(900.0);
});

test('new user starts with 10000 balance', function () {
    $user = User::factory()->create();
    expect((float) $user->fresh()->balance)->toEqual(10000.00);
});
