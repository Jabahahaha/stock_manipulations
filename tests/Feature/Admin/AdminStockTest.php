<?php

use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['name' => 'Trader Jane']);
    $this->aapl = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);
    $this->tsla = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc.']);
});

// --- Access Control ---

test('non-admin cannot access stocks page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.stocks.index'))
        ->assertForbidden();
});

test('guest cannot access stocks page', function () {
    $this->get(route('admin.stocks.index'))
        ->assertRedirect(route('login'));
});

// --- Stock List ---

test('admin can view stocks page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index'))
        ->assertOk()
        ->assertSee('Stocks');
});

test('stocks page shows all stocks', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index'))
        ->assertOk()
        ->assertSee('AAPL')
        ->assertSee('Apple Inc.')
        ->assertSee('TSLA')
        ->assertSee('Tesla Inc.');
});

test('stocks page shows trade count and volume', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 160.00,
        'total_amount' => 800.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index'))
        ->assertOk()
        ->assertSee('2,300.00');
});

// --- Summary Cards ---

test('stocks page shows most traded stock', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index'))
        ->assertOk()
        ->assertSee('Most Traded');
});

test('stocks page shows total stocks count', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index'))
        ->assertOk()
        ->assertSee('Total Stocks Traded');
});

// --- Search ---

test('admin can search stocks by symbol', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index', ['search' => 'AAPL']))
        ->assertOk()
        ->assertSee('AAPL')
        ->assertDontSee('TSLA');
});

test('admin can search stocks by company name', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index', ['search' => 'Tesla']))
        ->assertOk()
        ->assertSee('TSLA')
        ->assertSee('Tesla Inc.')
        ->assertDontSee('Apple Inc.');
});

test('stocks page shows empty state when no results', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.index', ['search' => 'NONEXISTENT']))
        ->assertOk()
        ->assertSee('No stocks found.');
});

// --- Stock Detail ---

test('non-admin cannot access stock detail page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertForbidden();
});

test('admin can view stock detail page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertOk()
        ->assertSee('AAPL')
        ->assertSee('Apple Inc.');
});

test('stock detail shows volume breakdown', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 160.00,
        'total_amount' => 800.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertOk()
        ->assertSee('Buy Volume')
        ->assertSee('1,500.00')
        ->assertSee('Sell Volume')
        ->assertSee('800.00');
});

test('stock detail shows unique traders count', function () {
    $otherUser = User::factory()->create();

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    Transaction::create([
        'user_id' => $otherUser->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 150.00,
        'total_amount' => 750.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertOk()
        ->assertSee('Unique Traders');
});

test('stock detail shows top traders', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertOk()
        ->assertSee('Top Traders')
        ->assertSee('Trader Jane');
});

test('stock detail shows recent transactions', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->aapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertOk()
        ->assertSee('Recent Transactions')
        ->assertSee('Trader Jane');
});

test('stock detail shows empty state when no trades', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stocks.show', $this->aapl))
        ->assertOk()
        ->assertSee('No trades yet.')
        ->assertSee('No transactions yet.');
});
