<?php

use App\Models\Notification;
use App\Models\Stock;
use App\Models\User;
use App\Models\Watchlist;
use App\Services\FinnhubService;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
});

// --- Page Access ---

test('notifications page requires authentication', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

test('notifications page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Notifications');
});

test('notifications page shows empty state when no notifications', function () {
    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('No notifications');
});

// --- Display ---

test('notifications display correctly', function () {
    Notification::create([
        'user_id' => $this->user->id,
        'type' => 'trade',
        'title' => 'Bought AAPL',
        'message' => 'Bought 10 shares of AAPL at $150.00 per share for $1500.00.',
    ]);

    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Bought AAPL')
        ->assertSee('Bought 10 shares of AAPL');
});

test('unread notifications have mark as read button', function () {
    Notification::create([
        'user_id' => $this->user->id,
        'type' => 'trade',
        'title' => 'Bought AAPL',
        'message' => 'Bought 10 shares.',
        'is_read' => false,
    ]);

    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Mark as read');
});

test('notifications only show for the owning user', function () {
    $otherUser = User::factory()->create();

    Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'trade',
        'title' => 'Other User Trade',
        'message' => 'This belongs to someone else.',
    ]);

    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertDontSee('Other User Trade');
});

// --- Mark as Read ---

test('mark as read works', function () {
    $notification = Notification::create([
        'user_id' => $this->user->id,
        'type' => 'trade',
        'title' => 'Bought AAPL',
        'message' => 'Test',
        'is_read' => false,
    ]);

    $this->actingAs($this->user)
        ->patch(route('notifications.markAsRead', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->is_read)->toBeTrue();
});

test('mark all as read works', function () {
    Notification::create([
        'user_id' => $this->user->id,
        'type' => 'trade',
        'title' => 'Notif 1',
        'message' => 'Test 1',
        'is_read' => false,
    ]);

    Notification::create([
        'user_id' => $this->user->id,
        'type' => 'price_alert',
        'title' => 'Notif 2',
        'message' => 'Test 2',
        'is_read' => false,
    ]);

    $this->actingAs($this->user)
        ->post(route('notifications.markAllRead'))
        ->assertRedirect();

    expect($this->user->notifications()->where('is_read', false)->count())->toBe(0);
});

test('cannot mark another users notification as read', function () {
    $otherUser = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'trade',
        'title' => 'Other',
        'message' => 'Test',
    ]);

    $this->actingAs($this->user)
        ->patch(route('notifications.markAsRead', $notification->id))
        ->assertNotFound();
});

// --- Unread Count Endpoint ---

test('unread count endpoint returns json', function () {
    Notification::create([
        'user_id' => $this->user->id,
        'type' => 'trade',
        'title' => 'Unread',
        'message' => 'Test',
        'is_read' => false,
    ]);

    Notification::create([
        'user_id' => $this->user->id,
        'type' => 'trade',
        'title' => 'Read',
        'message' => 'Test',
        'is_read' => true,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('notifications.unreadCount'))
        ->assertOk()
        ->assertJson(['count' => 1]);
});

// --- Trade Notifications ---

test('buying stock creates notification', function () {
    $this->actingAs($this->user)
        ->post(route('stocks.buy'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 100.00,
            'quantity' => 10,
        ]);

    $notification = Notification::where('user_id', $this->user->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe('trade');
    expect($notification->title)->toBe('Bought AAPL');
    expect($notification->is_read)->toBeFalse();
});

test('selling stock creates notification', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $this->user->transactions()->create([
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 100.00,
        'total_amount' => 1000.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('stocks.sell'), [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc',
            'price' => 150.00,
            'quantity' => 5,
        ]);

    $notification = Notification::where('user_id', $this->user->id)
        ->where('type', 'trade')
        ->first();
    expect($notification)->not->toBeNull();
    expect($notification->title)->toBe('Sold AAPL');
});

// --- Price Alert Notifications ---

test('triggered price alert creates notification', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc']);
    $watchlist = Watchlist::create([
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
        'alert_price' => 145.00,
        'alert_condition' => 'above',
        'alert_triggered' => false,
    ]);

    $mock = Mockery::mock(FinnhubService::class);
    $mock->shouldReceive('quotes')->with(['AAPL'])->andReturn([
        'AAPL' => ['price' => 150.00],
    ]);
    $this->app->instance(FinnhubService::class, $mock);

    $this->artisan('alerts:check')->assertSuccessful();

    $notification = Notification::where('user_id', $this->user->id)
        ->where('type', 'price_alert')
        ->first();
    expect($notification)->not->toBeNull();
    expect($notification->title)->toBe('Price Alert: AAPL');
    expect($notification->data['symbol'])->toBe('AAPL');
    expect($notification->data['price'])->toEqual(150.00);
});
