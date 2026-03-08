<?php

use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['name' => 'Jane Trader']);
    $this->stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);
});

// --- Access Control ---

test('non-admin cannot access transactions page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.transactions.index'))
        ->assertForbidden();
});

test('guest cannot access transactions page', function () {
    $this->get(route('admin.transactions.index'))
        ->assertRedirect(route('login'));
});

// --- Transaction List ---

test('admin can view transactions page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index'))
        ->assertOk()
        ->assertSee('Transactions');
});

test('transactions page shows trade data', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index'))
        ->assertOk()
        ->assertSee('Jane Trader')
        ->assertSee('AAPL')
        ->assertSee('Apple Inc.');
});

// --- Summary Cards ---

test('transactions page shows volume summary', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 100.00,
        'total_amount' => 1000.00,
    ]);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 120.00,
        'total_amount' => 600.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index'))
        ->assertOk()
        ->assertSee('1,000.00')
        ->assertSee('600.00');
});

// --- Filters ---

test('admin can filter transactions by type buy', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $seller = User::factory()->create(['name' => 'Seller Sam']);
    Transaction::create([
        'user_id' => $seller->id,
        'stock_id' => $this->stock->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 160.00,
        'total_amount' => 800.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index', ['type' => 'buy']))
        ->assertOk()
        ->assertSee('Jane Trader')
        ->assertDontSee('Seller Sam');
});

test('admin can filter transactions by type sell', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $seller = User::factory()->create(['name' => 'Seller Sam']);
    Transaction::create([
        'user_id' => $seller->id,
        'stock_id' => $this->stock->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 160.00,
        'total_amount' => 800.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index', ['type' => 'sell']))
        ->assertOk()
        ->assertSee('Seller Sam')
        ->assertDontSee('Jane Trader');
});

test('admin can search transactions by user name', function () {
    $otherUser = User::factory()->create(['name' => 'Bob Investor']);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    Transaction::create([
        'user_id' => $otherUser->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 150.00,
        'total_amount' => 750.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index', ['search' => 'Jane']))
        ->assertOk()
        ->assertSee('Jane Trader')
        ->assertDontSee('Bob Investor');
});

test('admin can filter transactions by symbol', function () {
    $tsla = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc.']);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $tsla->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 200.00,
        'total_amount' => 1000.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index', ['symbol' => 'TSLA']))
        ->assertOk()
        ->assertSee('TSLA')
        ->assertSee('Tesla Inc.');
});

// --- Empty State ---

test('transactions page shows empty state when no transactions', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index'))
        ->assertOk()
        ->assertSee('No transactions found.');
});

// --- Pagination ---

test('transactions page paginates results', function () {
    for ($i = 0; $i < 30; $i++) {
        Transaction::create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->id,
            'type' => 'buy',
            'quantity' => 1,
            'price_per_share' => 100.00,
            'total_amount' => 100.00,
        ]);
    }

    $response = $this->actingAs($this->admin)
        ->get(route('admin.transactions.index'));

    $response->assertOk();
    // Page should have pagination links since 30 > 25 per page
    $response->assertSee('Next');
});

test('transactions page preserves filters in pagination', function () {
    for ($i = 0; $i < 30; $i++) {
        Transaction::create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->id,
            'type' => 'buy',
            'quantity' => 1,
            'price_per_share' => 100.00,
            'total_amount' => 100.00,
        ]);
    }

    $response = $this->actingAs($this->admin)
        ->get(route('admin.transactions.index', ['type' => 'buy']));

    $response->assertOk();
    // Pagination links should include the type filter
    $response->assertSee('type=buy');
});
