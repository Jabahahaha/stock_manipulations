<?php

use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
    $this->trader = User::factory()->create(['balance' => 10000.00]);
    $this->stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);
});

// --- Page Access ---

test('feed page requires authentication', function () {
    $this->get(route('feed.index'))->assertRedirect(route('login'));
});

test('feed page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee('Feed');
});

// --- Empty States ---

test('feed shows prompt to follow traders when not following anyone', function () {
    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee('No traders followed')
        ->assertSee('Discover Traders');
});

test('feed shows empty state when followed traders have no trades', function () {
    $this->user->following()->attach($this->trader->id);

    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee('No trades yet');
});

// --- Feed Content ---

test('feed shows trades from followed users', function () {
    $this->user->following()->attach($this->trader->id);

    Transaction::create([
        'user_id' => $this->trader->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee($this->trader->name)
        ->assertSee('AAPL')
        ->assertSee('bought');
});

test('feed does not show trades from unfollowed users', function () {
    $stranger = User::factory()->create();

    Transaction::create([
        'user_id' => $stranger->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 150.00,
        'total_amount' => 750.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertDontSee($stranger->name);
});

test('feed does not show own trades', function () {
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 150.00,
        'total_amount' => 750.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee('No traders followed');
});

test('feed shows trades ordered newest first', function () {
    $this->user->following()->attach($this->trader->id);

    $this->travel(-2)->days();

    Transaction::create([
        'user_id' => $this->trader->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 100.00,
        'total_amount' => 500.00,
    ]);

    $this->travelBack();

    $msft = Stock::create(['symbol' => 'MSFT', 'company_name' => 'Microsoft Corporation']);

    Transaction::create([
        'user_id' => $this->trader->id,
        'stock_id' => $msft->id,
        'type' => 'sell',
        'quantity' => 3,
        'price_per_share' => 400.00,
        'total_amount' => 1200.00,
    ]);

    $followedIds = $this->user->following()->pluck('users.id');
    $trades = Transaction::whereIn('user_id', $followedIds)
        ->orderByDesc('created_at')
        ->get();

    expect($trades->first()->stock->symbol)->toBe('MSFT');
    expect($trades->last()->stock->symbol)->toBe('AAPL');
});

test('feed shows sell trades correctly', function () {
    $this->user->following()->attach($this->trader->id);

    Transaction::create([
        'user_id' => $this->trader->id,
        'stock_id' => $this->stock->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 160.00,
        'total_amount' => 800.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee('sold');
});

test('feed shows trades from multiple followed users', function () {
    $trader2 = User::factory()->create(['balance' => 10000.00]);
    $this->user->following()->attach([$this->trader->id, $trader2->id]);

    Transaction::create([
        'user_id' => $this->trader->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $msft = Stock::create(['symbol' => 'MSFT', 'company_name' => 'Microsoft Corporation']);
    Transaction::create([
        'user_id' => $trader2->id,
        'stock_id' => $msft->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 400.00,
        'total_amount' => 2000.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('feed.index'))
        ->assertOk()
        ->assertSee($this->trader->name)
        ->assertSee($trader2->name)
        ->assertSee('AAPL')
        ->assertSee('MSFT');
});
