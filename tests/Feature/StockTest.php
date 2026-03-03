<?php

use App\Models\Holding;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinnhubService;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
});

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

test('stocks page shows quote when symbol selected', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('search')->andReturn([]);
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
        ->get(route('stocks.index', ['symbol' => 'AAPL', 'name' => 'Apple Inc', 'query' => 'AAPL']))
        ->assertOk()
        ->assertSee('$150.00');
});

test('stocks page handles null quote gracefully', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('search')->andReturn([]);
    $mock->shouldReceive('quote')->with('INVALID')->andReturn(null);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('stocks.index', ['symbol' => 'INVALID', 'query' => 'INVALID']))
        ->assertOk();
});

test('user can buy stock', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 100.00,
            'quantity' => 10,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((float) $this->user->fresh()->balance)->toEqual(9000.00);

    $holding = Holding::where('user_id', $this->user->id)->where('symbol', 'AAPL')->first();
    expect((float) $holding->quantity)->toEqual(10.0);
    expect((float) $holding->average_cost)->toEqual(100.0);

    $tx = Transaction::where('user_id', $this->user->id)->first();
    expect($tx->type)->toBe('buy');
    expect((float) $tx->total_amount)->toEqual(1000.0);
});

test('buying stock updates existing holding with new average cost', function () {
    $this->user->holdings()->create([
        'symbol' => 'AAPL',
        'company_name' => 'Apple Inc',
        'quantity' => 10,
        'average_cost' => 100.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 200.00,
            'quantity' => 10,
        ]);

    $holding = $this->user->holdings()->where('symbol', 'AAPL')->first();
    expect((float) $holding->quantity)->toEqual(20.0);
    expect((float) $holding->average_cost)->toEqual(150.0);
});

test('user cannot buy stock with insufficient balance', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 100.00,
            'quantity' => 200,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('balance');

    expect((float) $this->user->fresh()->balance)->toEqual(10000.00);
    expect(Holding::where('user_id', $this->user->id)->count())->toBe(0);
});

test('user can sell stock', function () {
    $this->user->holdings()->create([
        'symbol' => 'AAPL',
        'company_name' => 'Apple Inc',
        'quantity' => 10,
        'average_cost' => 100.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((float) $this->user->fresh()->balance)->toEqual(10750.00);
    $holding = $this->user->holdings()->where('symbol', 'AAPL')->first();
    expect((float) $holding->quantity)->toEqual(5.0);
});

test('selling all shares removes holding', function () {
    $this->user->holdings()->create([
        'symbol' => 'AAPL',
        'company_name' => 'Apple Inc',
        'quantity' => 10,
        'average_cost' => 100.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 10,
        ]);

    expect(Holding::where('user_id', $this->user->id)->count())->toBe(0);
    expect((float) $this->user->fresh()->balance)->toEqual(11500.00);
});

test('user cannot sell more shares than owned', function () {
    $this->user->holdings()->create([
        'symbol' => 'AAPL',
        'company_name' => 'Apple Inc',
        'quantity' => 5,
        'average_cost' => 100.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 10,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('quantity');

    expect((float) $this->user->holdings()->where('symbol', 'AAPL')->first()->quantity)->toEqual(5.0);
});

test('user cannot sell stock they dont own', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('quantity');
});

test('buy creates transaction record', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'TSLA',
            'company_name' => 'Tesla Inc',
            'price' => 250.00,
            'quantity' => 2,
        ]);

    $tx = Transaction::where('user_id', $this->user->id)->first();
    expect($tx->symbol)->toBe('TSLA');
    expect($tx->company_name)->toBe('Tesla Inc');
    expect($tx->type)->toBe('buy');
    expect((float) $tx->price_per_share)->toEqual(250.0);
    expect((float) $tx->total_amount)->toEqual(500.0);
});

test('sell creates transaction record', function () {
    $this->user->holdings()->create([
        'symbol' => 'TSLA',
        'company_name' => 'Tesla Inc',
        'quantity' => 5,
        'average_cost' => 200.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'TSLA',
            'company_name' => 'Tesla Inc',
            'price' => 300.00,
            'quantity' => 3,
        ]);

    $tx = Transaction::where('user_id', $this->user->id)->where('type', 'sell')->first();
    expect($tx->symbol)->toBe('TSLA');
    expect($tx->type)->toBe('sell');
    expect((float) $tx->price_per_share)->toEqual(300.0);
    expect((float) $tx->total_amount)->toEqual(900.0);
});

test('new user starts with 10000 balance', function () {
    $user = User::factory()->create();
    expect((float) $user->fresh()->balance)->toEqual(10000.00);
});
