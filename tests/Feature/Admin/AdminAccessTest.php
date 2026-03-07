<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

// --- Admin Middleware ---

test('guest is redirected to login from admin routes', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('non-admin user gets 403 on admin routes', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can access admin dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Dashboard');
});

// --- Banned Middleware ---

test('banned user is logged out and redirected', function () {
    $banned = User::factory()->create(['is_banned' => true]);

    $this->actingAs($banned)
        ->get(route('portfolio.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('banned user redirect includes error flash', function () {
    $banned = User::factory()->create(['is_banned' => true]);

    $this->actingAs($banned)
        ->get(route('portfolio.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Your account has been banned.');
});

test('non-banned user can access app normally', function () {
    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertOk();
});

// --- Admin Panel Link ---

test('admin sees admin panel link in navigation', function () {
    $this->actingAs($this->admin)
        ->get(route('portfolio.index'))
        ->assertSee('Admin Panel');
});

test('regular user does not see admin panel link', function () {
    $this->actingAs($this->user)
        ->get(route('portfolio.index'))
        ->assertDontSee('Admin Panel');
});
