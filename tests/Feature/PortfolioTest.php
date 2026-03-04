<?php

use App\Models\Stock;
use App\Models\User;
use App\Services\FinnhubService;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 8000.00]);
});

test('portfolio page requires authentication', function () {
    $this->get(route('portfolio.index'))->assertRedirect(route('login'));
});

test('portfolio page loads for authenticated user', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee('Portfolio');
});

test('portfolio shows empty state when no holdings', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee("You don't own any stocks yet", false);
});

test('portfolio shows holdings with live prices', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 100.00,
        'total_amount' => 1000.00,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->with(['AAPL'])->andReturn([
        'AAPL' => ['symbol' => 'AAPL', 'price' => 150.00, 'change' => 2.0, 'change_percent' => '1.35%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee('AAPL')
        ->assertSee('Apple Inc')
        ->assertSee('$150.00');
});

test('portfolio shows cash balance', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee('$8,000.00');
});

test('portfolio calculates gain and loss', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 200.00,
        'total_amount' => 1000.00,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([
        'TSLA' => ['symbol' => 'TSLA', 'price' => 250.00, 'change' => 5.0, 'change_percent' => '2.04%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    // gain = (250 * 5) - (200 * 5) = 250
    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee('+$250.00');
});

test('dashboard redirects to portfolio', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('portfolio.index'));
});
