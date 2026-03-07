<?php

use App\Models\Notification;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['balance' => 10000.00]);
});

// --- Access Control ---

test('non-admin cannot access user management', function () {
    $this->actingAs($this->user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

// --- User List ---

test('admin can view user list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee($this->user->name)
        ->assertSee($this->user->email);
});

test('admin can search users by name', function () {
    $alice = User::factory()->create(['name' => 'Alice Test']);
    $bob = User::factory()->create(['name' => 'Bob Test']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertSee('Alice Test')
        ->assertDontSee('Bob Test');
});

test('admin can search users by email', function () {
    $target = User::factory()->create(['email' => 'findme@example.com']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['search' => 'findme']))
        ->assertOk()
        ->assertSee('findme@example.com');
});

test('user list shows trade count', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 5,
        'price_per_share' => 150,
        'total_amount' => 750,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

// --- User Detail ---

test('admin can view user detail page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $this->user))
        ->assertOk()
        ->assertSee($this->user->name)
        ->assertSee($this->user->email)
        ->assertSee('Adjust Balance');
});

test('user detail shows holdings', function () {
    $stock = Stock::create(['symbol' => 'AAPL', 'company_name' => 'Apple Inc.']);
    Transaction::create([
        'user_id' => $this->user->id,
        'stock_id' => $stock->id,
        'type' => 'buy',
        'quantity' => 10,
        'price_per_share' => 150,
        'total_amount' => 1500,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $this->user))
        ->assertOk()
        ->assertSee('AAPL');
});

// --- Ban / Unban ---

test('admin can ban a user', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.toggleBan', $this->user))
        ->assertRedirect();

    $this->user->refresh();
    expect($this->user->is_banned)->toBeTrue();
});

test('admin can unban a user', function () {
    $this->user->is_banned = true;
    $this->user->save();

    $this->actingAs($this->admin)
        ->post(route('admin.users.toggleBan', $this->user))
        ->assertRedirect();

    $this->user->refresh();
    expect($this->user->is_banned)->toBeFalse();
});

test('admin cannot ban themselves', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.toggleBan', $this->admin))
        ->assertRedirect()
        ->assertSessionHas('error', 'You cannot ban yourself.');

    $this->admin->refresh();
    expect($this->admin->is_banned)->toBeFalse();
});

test('admin cannot ban another admin', function () {
    $otherAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($this->admin)
        ->post(route('admin.users.toggleBan', $otherAdmin))
        ->assertRedirect()
        ->assertSessionHas('error', 'You cannot ban another admin.');

    $otherAdmin->refresh();
    expect($otherAdmin->is_banned)->toBeFalse();
});

// --- Balance Adjustment ---

test('admin can add to user balance', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.adjustBalance', $this->user), [
            'amount' => 500,
            'reason' => 'Bonus credit',
        ])
        ->assertRedirect();

    $this->user->refresh();
    expect((float) $this->user->balance)->toBe(10500.00);
});

test('admin can deduct from user balance', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.adjustBalance', $this->user), [
            'amount' => -200,
            'reason' => 'Fee correction',
        ])
        ->assertRedirect();

    $this->user->refresh();
    expect((float) $this->user->balance)->toBe(9800.00);
});

test('balance adjustment rejects negative resulting balance', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.adjustBalance', $this->user), [
            'amount' => -20000,
            'reason' => 'Too much deduction',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->user->refresh();
    expect((float) $this->user->balance)->toBe(10000.00);
});

test('balance adjustment creates notification for user', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.adjustBalance', $this->user), [
            'amount' => 100,
            'reason' => 'Welcome bonus',
        ]);

    $notification = Notification::where('user_id', $this->user->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe('system');
    expect($notification->title)->toBe('Balance Adjustment');
    expect($notification->message)->toContain('Welcome bonus');
});

test('balance adjustment validates required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.adjustBalance', $this->user), [
            'amount' => '',
            'reason' => '',
        ])
        ->assertSessionHasErrors(['amount', 'reason']);
});
