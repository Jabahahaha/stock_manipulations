<?php

use App\Models\Stock;
use App\Models\User;
use App\Services\FinnhubService;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
});

// --- Page & Auth ---

test('watchlist page requires authentication', function () {
    $this->get(route('watchlist.index'))->assertRedirect(route('login'));
});

test('watchlist page loads for authenticated user', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('watchlist.index'))
        ->assertOk()
        ->assertSee('Watchlist');
});

test('watchlist shows empty state when no items', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('watchlist.index'))
        ->assertOk()
        ->assertSee('Your watchlist is empty');
});

// --- Search ---

test('watchlist search returns results', function () {
    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([]);
    $mock->shouldReceive('search')->with('Apple')->andReturn([
        ['1. symbol' => 'AAPL', '2. name' => 'Apple Inc', '3. type' => 'Common Stock', '4. region' => 'United States'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)
        ->get(route('watchlist.index', ['query' => 'Apple']))
        ->assertOk()
        ->assertSee('AAPL')
        ->assertSee('Apple Inc')
        ->assertSee('Add to Watchlist');
});

// --- Add & Remove ---

test('user can add stock to watchlist', function () {
    $this->actingAs($this->user)
        ->post(route('watchlist.store'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
        ])
        ->assertRedirect();

    $stock = Stock::where('symbol', 'AAPL')->first();
    expect($stock)->not->toBeNull();
    $this->assertDatabaseHas('watchlists', [
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
    ]);
});

test('user cannot add duplicate symbol to watchlist', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->watchlists()->create(['stock_id' => $stock->id]);

    $this->actingAs($this->user)
        ->post(route('watchlist.store'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
        ])
        ->assertSessionHasErrors('symbol');
});

test('user can remove stock from watchlist', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $item = $this->user->watchlists()->create(['stock_id' => $stock->id]);

    $this->actingAs($this->user)
        ->delete(route('watchlist.destroy', $item->id))
        ->assertRedirect(route('watchlist.index'));

    $this->assertDatabaseMissing('watchlists', ['id' => $item->id]);
});

test('user cannot remove another users watchlist item', function () {
    $other = User::factory()->create();
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $item = $other->watchlists()->create(['stock_id' => $stock->id]);

    $this->actingAs($this->user)
        ->delete(route('watchlist.destroy', $item->id))
        ->assertNotFound();

    $this->assertDatabaseHas('watchlists', ['id' => $item->id]);
});

// --- Alerts ---

test('user can set price alert', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $item = $this->user->watchlists()->create(['stock_id' => $stock->id]);

    $this->actingAs($this->user)
        ->patch(route('watchlist.updateAlert', $item->id), [
            'alert_price' => 300.00,
            'alert_condition' => 'above',
        ])
        ->assertRedirect();

    $item->refresh();
    expect((float) $item->alert_price)->toEqual(300.00);
    expect($item->alert_condition)->toBe('above');
    expect($item->alert_triggered)->toBeFalsy();
});

test('user can update existing alert', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $item = $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 300.00,
        'alert_condition' => 'above',
        'alert_triggered' => true,
    ]);

    $this->actingAs($this->user)
        ->patch(route('watchlist.updateAlert', $item->id), [
            'alert_price' => 200.00,
            'alert_condition' => 'below',
        ])
        ->assertRedirect();

    $item->refresh();
    expect((float) $item->alert_price)->toEqual(200.00);
    expect($item->alert_condition)->toBe('below');
    expect($item->alert_triggered)->toBeFalsy();
});

test('user can remove alert', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $item = $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 300.00,
        'alert_condition' => 'above',
    ]);

    $this->actingAs($this->user)
        ->delete(route('watchlist.removeAlert', $item->id))
        ->assertRedirect();

    $item->refresh();
    expect($item->alert_price)->toBeNull();
    expect($item->alert_condition)->toBeNull();
});

test('alert validation rejects invalid data', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $item = $this->user->watchlists()->create(['stock_id' => $stock->id]);

    $this->actingAs($this->user)
        ->patch(route('watchlist.updateAlert', $item->id), [
            'alert_price' => -5,
            'alert_condition' => 'invalid',
        ])
        ->assertSessionHasErrors(['alert_price', 'alert_condition']);
});

// --- Prices Endpoint ---

test('prices endpoint requires authentication', function () {
    $this->getJson(route('watchlist.prices'))->assertUnauthorized();
});

test('prices endpoint returns json with live prices', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->watchlists()->create(['stock_id' => $stock->id]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->with(['AAPL'])->andReturn([
        'AAPL' => ['price' => 180.50, 'change' => 2.5, 'change_percent' => '1.40%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $response = $this->actingAs($this->user)
        ->getJson(route('watchlist.prices'))
        ->assertOk();

    $data = $response->json();
    expect($data)->toHaveCount(1);
    expect($data[0]['symbol'])->toBe('AAPL');
    expect($data[0]['current_price'])->toBe(180.50);
});

test('prices endpoint triggers alert when condition met', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $item = $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 150.00,
        'alert_condition' => 'above',
        'alert_triggered' => false,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([
        'AAPL' => ['price' => 160.00, 'change' => 3.0, 'change_percent' => '1.91%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)->getJson(route('watchlist.prices'));

    $item->refresh();
    expect($item->alert_triggered)->toBeTruthy();
});

test('prices endpoint does not re-trigger already triggered alert', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $item = $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 150.00,
        'alert_condition' => 'above',
        'alert_triggered' => true,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([
        'AAPL' => ['price' => 160.00, 'change' => 3.0, 'change_percent' => '1.91%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->user)->getJson(route('watchlist.prices'));

    $item->refresh();
    expect($item->alert_triggered)->toBeTruthy();
});

// --- Artisan Command ---

test('alerts check command triggers alerts', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 150.00,
        'alert_condition' => 'above',
        'alert_triggered' => false,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([
        'AAPL' => ['price' => 160.00, 'change' => 3.0, 'change_percent' => '1.91%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->artisan('alerts:check')
        ->expectsOutputToContain('1 triggered')
        ->assertExitCode(0);

    expect($this->user->watchlists()->first()->alert_triggered)->toBeTruthy();
});

test('alerts check command skips already triggered alerts', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 150.00,
        'alert_condition' => 'above',
        'alert_triggered' => true,
    ]);

    $this->artisan('alerts:check')
        ->expectsOutputToContain('No active alerts')
        ->assertExitCode(0);
});

test('alerts check command handles below condition', function () {
    $stock = Stock::create(['symbol' => 'TSLA', 'company_name' => 'Tesla Inc']);
    $this->user->watchlists()->create([
        'stock_id' => $stock->id,
        'alert_price' => 200.00,
        'alert_condition' => 'below',
        'alert_triggered' => false,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->andReturn([
        'TSLA' => ['price' => 180.00, 'change' => -5.0, 'change_percent' => '-2.70%'],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->artisan('alerts:check')
        ->expectsOutputToContain('1 triggered')
        ->assertExitCode(0);

    expect($this->user->watchlists()->first()->alert_triggered)->toBeTruthy();
});
