<?php

use App\Models\Stock;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
});

test('transactions page requires authentication', function () {
    $this->get(route('transactions.index'))->assertRedirect(route('login'));
});

test('transactions page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('Transaction History');
});

test('transactions page shows empty state when no transactions', function () {
    $this->actingAs($this->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('No transactions yet');
});

test('transactions page shows buy transactions', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('AAPL')
        ->assertSee('Apple Inc')
        ->assertSee('Buy')
        ->assertSee('$150.00')
        ->assertSee('$1,500.00');
});

test('transactions page shows sell transactions', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'sell',
        'quantity' => 5,
        'price_per_share' => 250.00,
        'total_amount' => 1250.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('TSLA')
        ->assertSee('Sell')
        ->assertSee('$1,250.00');
});

test('transactions are ordered newest first', function () {
    $stockAapl = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $stockTsla = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);

    // Create older transaction first
    $this->travel(-1)->days();
    $this->user->transactions()->create([
        'stock_id' => $stockAapl->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    // Create newer transaction
    $this->travelBack();
    $this->user->transactions()->create([
        'stock_id' => $stockTsla->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 250.00,
        'total_amount' => 1250.00,
    ]);

    // TSLA (newer) should appear before AAPL (older)
    $this->actingAs($this->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSeeInOrder(['TSLA', 'AAPL']);
});

test('transactions are paginated', function () {
    // Create 20 transactions (more than the 15 per page)
    for ($i = 0; $i < 20; $i++) {
        $stock = Stock::create(['symbol' => 'STK' . $i, 'company_name' => 'Company ' . $i]);
        $this->user->transactions()->create([
            'stock_id' => $stock->id,
            'type' => 'buy',
            'quantity' => 1,
            'price_per_share' => 100.00,
            'total_amount' => 100.00,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->get(route('transactions.index'));

    $response->assertOk();

    // Page 1 should have 15 items, page 2 should exist
    $content = $response->getContent();
    expect(substr_count($content, '<tr>'))->toBe(16); // 1 header + 15 data rows
});

test('user cannot see other users transactions', function () {
    $otherUser = User::factory()->create();
    $stock = Stock::create(['symbol' => 'MSFT', 'company_name' => 'Microsoft Corp']);
    $otherUser->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 300.00,
        'total_amount' => 3000.00,
    ]);

    $this->actingAs($this->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertDontSee('MSFT')
        ->assertDontSee('Microsoft Corp');
});
