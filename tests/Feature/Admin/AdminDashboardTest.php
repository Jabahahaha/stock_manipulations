<?php

use App\Models\CopyTradingSetting;
use App\Models\Follow;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create();
});

test('admin dashboard requires admin access', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin dashboard loads for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard');
});

test('dashboard shows correct user counts', function () {
    // admin + user = 2, plus 3 more
    User::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Total Users')
        ->assertSeeText('5');
});

test('dashboard shows correct trade metrics', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 160.00,
        'total_amount' => 800.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Total Trades')
        ->assertSee('$2,300.00');
});

test('dashboard shows recent trades table', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc.']);

    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 250.00,
        'total_amount' => 1250.00,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Recent Trades')
        ->assertSee($this->user->name)
        ->assertSee('TSLA');
});

test('dashboard shows active copy traders count', function () {
    $trader = User::factory()->create();

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $trader->id,
        'amount_per_trade' => 500,
        'is_active' => true,
    ]);

    CopyTradingSetting::create([
        'user_id' => $trader->id,
        'trader_id' => $this->user->id,
        'amount_per_trade' => 200,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Active Copy Traders');
});

test('dashboard shows banned users count', function () {
    User::factory()->create(['is_banned' => true]);
    User::factory()->create(['is_banned' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Banned Users');
});

test('dashboard shows empty state when no trades', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('No trades yet');
});

test('dashboard sidebar has navigation links', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Users')
        ->assertSee('Transactions')
        ->assertSee('Stocks')
        ->assertSee('Copy Trading')
        ->assertSee('Announcements')
        ->assertSee('Back to App');
});
