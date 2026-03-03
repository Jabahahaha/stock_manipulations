<?php

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
    $this->user->transactions()->create([
        'symbol' => 'AAPL',
        'company_name' => 'Apple Inc',
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
    $this->user->transactions()->create([
        'symbol' => 'TSLA',
        'company_name' => 'Tesla Inc',
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
    // Create older transaction first
    $this->travel(-1)->days();
    $this->user->transactions()->create([
        'symbol' => 'AAPL',
        'company_name' => 'Apple Inc',
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150.00,
        'total_amount' => 1500.00,
    ]);

    // Create newer transaction
    $this->travelBack();
    $this->user->transactions()->create([
        'symbol' => 'TSLA',
        'company_name' => 'Tesla Inc',
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
        $this->user->transactions()->create([
            'symbol' => 'STOCK' . $i,
            'company_name' => 'Company ' . $i,
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
    $otherUser->transactions()->create([
        'symbol' => 'MSFT',
        'company_name' => 'Microsoft Corp',
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
