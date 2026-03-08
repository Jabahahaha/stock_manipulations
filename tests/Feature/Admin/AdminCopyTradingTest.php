<?php

use App\Models\CopyTradingSetting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['name' => 'Copier Alice']);
    $this->trader = User::factory()->create(['name' => 'Trader Bob']);
});

// --- Access Control ---

test('non-admin cannot access copy trading oversight', function () {
    $this->actingAs($this->user)
        ->get(route('admin.copyTrading.index'))
        ->assertForbidden();
});

test('guest cannot access copy trading oversight', function () {
    $this->get(route('admin.copyTrading.index'))
        ->assertRedirect(route('login'));
});

// --- Page Load ---

test('admin can view copy trading page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('Copy Trading');
});

test('copy trading page shows empty state', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('No copy trading pairs found.');
});

// --- Data Display ---

test('copy trading page shows pairs', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 500.00,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('Copier Alice')
        ->assertSee('Trader Bob')
        ->assertSee('500.00');
});

test('copy trading page shows active badge', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 200.00,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('Active');
});

test('copy trading page shows inactive badge', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 200.00,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('Inactive');
});

// --- Summary Cards ---

test('copy trading page shows summary cards', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300.00,
        'is_active' => true,
    ]);

    $otherUser = User::factory()->create();
    CopyTradingSetting::create([
        'user_id' => $otherUser->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 200.00,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('Total Pairs')
        ->assertSee('Active Pairs')
        ->assertSee('Total Allocated / Trade');
});

// --- Filters ---

test('admin can filter by active status', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300.00,
        'is_active' => true,
    ]);

    $inactiveUser = User::factory()->create(['name' => 'Inactive Ivan']);
    CopyTradingSetting::create([
        'user_id' => $inactiveUser->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 100.00,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index', ['status' => 'active']))
        ->assertOk()
        ->assertSee('Copier Alice')
        ->assertDontSee('Inactive Ivan');
});

test('admin can filter by inactive status', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300.00,
        'is_active' => true,
    ]);

    $inactiveUser = User::factory()->create(['name' => 'Inactive Ivan']);
    CopyTradingSetting::create([
        'user_id' => $inactiveUser->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 100.00,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee('Inactive Ivan')
        ->assertDontSee('Copier Alice');
});

test('admin can search by copier name', function () {
    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300.00,
        'is_active' => true,
    ]);

    $otherUser = User::factory()->create(['name' => 'Charlie Delta']);
    CopyTradingSetting::create([
        'user_id' => $otherUser->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 100.00,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertSee('Copier Alice')
        ->assertDontSee('Charlie Delta');
});

test('admin can search by trader name', function () {
    $otherTrader = User::factory()->create(['name' => 'Trader Zara']);

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $this->trader->id,
        'amount_per_trade' => 300.00,
        'is_active' => true,
    ]);

    CopyTradingSetting::create([
        'user_id' => $this->user->id,
        'trader_id' => $otherTrader->id,
        'amount_per_trade' => 100.00,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index', ['search' => 'Zara']))
        ->assertOk()
        ->assertSee('Trader Zara')
        ->assertDontSee('Trader Bob');
});

// --- Pagination ---

test('copy trading page paginates results', function () {
    for ($i = 0; $i < 30; $i++) {
        $copier = User::factory()->create();
        CopyTradingSetting::create([
            'user_id' => $copier->id,
            'trader_id' => $this->trader->id,
            'amount_per_trade' => 100.00,
            'is_active' => true,
        ]);
    }

    $this->actingAs($this->admin)
        ->get(route('admin.copyTrading.index'))
        ->assertOk()
        ->assertSee('Next');
});
