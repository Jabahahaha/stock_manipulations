<?php

use App\Models\CopyTradingSetting;
use App\Models\Notification;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CopyTradingService;
use App\Services\FinnhubService;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
    $this->trader = User::factory()->create(['balance' => 10000.00]);
    $this->stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);
});

// --- Settings Management ---

test('copy trading settings page requires authentication', function () {
    $this->post(route('copy-trading.store', $this->trader))
        ->assertRedirect(route('login'));
});

test('user can enable copy trading for a trader', function () {
    $this->actingAs($this->user)
        ->post(route('copy-trading.store', $this->trader), [
            'amount_per_trade' => 500,
        ])
        ->assertRedirect();

    $setting = CopyTradingSetting::where('user_id', $this->user->id)
        ->where('trader_id', $this->trader->id)
        ->first();

    expect($setting)->not->toBeNull();
    expect($setting->amount_per_trade)->toBe('500.00');
    expect($setting->is_active)->toBeTrue();
});

test('enabling copy trading auto-follows the trader', function () {
    $this->actingAs($this->user)
        ->post(route('copy-trading.store', $this->trader), [
            'amount_per_trade' => 500,
        ]);

    expect($this->user->isFollowing($this->trader))->toBeTrue();
});

test('user can update copy trading amount', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 200,
    ]);

    $this->actingAs($this->user)
        ->post(route('copy-trading.store', $this->trader), [
            'amount_per_trade' => 750,
        ])
        ->assertRedirect();

    $setting = CopyTradingSetting::where('user_id', $this->user->id)
        ->where('trader_id', $this->trader->id)
        ->first();

    expect($setting->amount_per_trade)->toBe('750.00');
});

test('user can disable copy trading', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 500,
    ]);

    $this->actingAs($this->user)
        ->delete(route('copy-trading.destroy', $this->trader))
        ->assertRedirect();

    expect(CopyTradingSetting::where('user_id', $this->user->id)->count())->toBe(0);
});

test('user cannot copy trade themselves', function () {
    $this->actingAs($this->user)
        ->post(route('copy-trading.store', $this->user), [
            'amount_per_trade' => 500,
        ])
        ->assertRedirect();

    expect(CopyTradingSetting::count())->toBe(0);
});

test('amount per trade is validated', function () {
    $this->actingAs($this->user)
        ->post(route('copy-trading.store', $this->trader), [
            'amount_per_trade' => 0,
        ])
        ->assertSessionHasErrors('amount_per_trade');

    $this->actingAs($this->user)
        ->post(route('copy-trading.store', $this->trader), [
            'amount_per_trade' => '',
        ])
        ->assertSessionHasErrors('amount_per_trade');
});

// --- Copy Trade Execution ---

test('copy trading executes buy when trader buys', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300,
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'buy', 150.00);

    $this->user->refresh();

    // 300 / 150 = 2 shares, cost = 300.00
    $transaction = Transaction::where('user_id', $this->user->id)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->type)->toBe('buy');
    expect((float) $transaction->quantity)->toBe(2.0);
    expect((float) $transaction->total_amount)->toBe(300.00);
    expect((float) $this->user->balance)->toBe(9700.00);
});

test('copy trading executes sell when trader sells', function () {
    // Give copier some shares first
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 140.00,
        'total_amount' => 1400.00,
    ]);
    $this->user->decrement('balance', 1400);

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300,
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'sell', 150.00);

    $this->user->refresh();

    // 300 / 150 = 2 shares sold
    $sellTx = Transaction::where('user_id', $this->user->id)->where('type', 'sell')->first();
    expect($sellTx)->not->toBeNull();
    expect((float) $sellTx->quantity)->toBe(2.0);
    expect((float) $this->user->balance)->toBe(8900.00); // 10000 - 1400 + 300
});

test('copy trading sell is capped at owned quantity', function () {
    // Give copier only 1 share
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 1,
        'price_per_share' => 140.00,
        'total_amount' => 140.00,
    ]);
    $this->user->decrement('balance', 140);

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300, // would be 2 shares, but only owns 1
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'sell', 150.00);

    $sellTx = Transaction::where('user_id', $this->user->id)->where('type', 'sell')->first();
    expect((float) $sellTx->quantity)->toBe(1.0);
});

test('copy trading skips sell when copier has no shares', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300,
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'sell', 150.00);

    expect(Transaction::where('user_id', $this->user->id)->count())->toBe(0);
});

test('copy trading fails with insufficient balance and sends notification', function () {
    $this->user->balance = 50;
    $this->user->save();

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300,
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'buy', 150.00);

    expect(Transaction::where('user_id', $this->user->id)->count())->toBe(0);

    $notification = Notification::where('user_id', $this->user->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe('copy_trade');
    expect($notification->title)->toContain('failed');
});

test('copy trading creates success notification', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300,
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'buy', 150.00);

    $notification = Notification::where('user_id', $this->user->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe('copy_trade');
    expect($notification->title)->toContain('Copied buy');
});

test('inactive copy trading settings are skipped', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300,
        'is_active' => false,
    ]);

    $service = app(CopyTradingService::class);
    $service->executeCopyTrades($this->trader, $this->stock, 'buy', 150.00);

    expect(Transaction::where('user_id', $this->user->id)->count())->toBe(0);
});

// --- Integration: Copy trade triggered via stock buy/sell ---

test('buying stock triggers copy trades for copiers', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 150,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quote')->andReturn(['c' => 150.00, 'dp' => 1.5, 'd' => 2.25, 'h' => 152, 'l' => 148, 'o' => 149, 'pc' => 147.75]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->trader)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'price' => 150.00,
            'quantity' => 10,
        ])
        ->assertRedirect();

    // Copier should have 1 share (150 / 150)
    $copierTx = Transaction::where('user_id', $this->user->id)->where('type', 'buy')->first();
    expect($copierTx)->not->toBeNull();
    expect((float) $copierTx->quantity)->toBe(1.0);
});

test('selling stock triggers copy trades for copiers', function () {
    // Give both trader and copier shares
    Transaction::create([
        'user_id' => $this->trader->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 140.00,
        'total_amount' => 1400.00,
    ]);
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $this->stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 140.00,
        'total_amount' => 700.00,
    ]);

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 150,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quote')->andReturn(['c' => 150.00, 'dp' => 1.5, 'd' => 2.25, 'h' => 152, 'l' => 148, 'o' => 149, 'pc' => 147.75]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->actingAs($this->trader)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'price' => 150.00,
            'quantity' => 5,
        ])
        ->assertRedirect();

    $copierSell = Transaction::where('user_id', $this->user->id)->where('type', 'sell')->first();
    expect($copierSell)->not->toBeNull();
    expect((float) $copierSell->quantity)->toBe(1.0);
});

// --- UI ---

test('trader profile shows copy trading form', function () {
    $this->actingAs($this->user)
        ->get(route('traders.show', $this->trader))
        ->assertOk()
        ->assertSee('Copy Trading')
        ->assertSee('Start Copying');
});

test('trader profile shows active copy trading status', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 500,
    ]);

    $this->actingAs($this->user)
        ->get(route('traders.show', $this->trader))
        ->assertOk()
        ->assertSee('Stop Copying')
        ->assertSee('500.00');
});
