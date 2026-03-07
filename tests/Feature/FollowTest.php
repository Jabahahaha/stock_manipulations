<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 10000.00]);
    $this->trader = User::factory()->create(['balance' => 10000.00]);
});

// --- Page Access ---

test('traders page requires authentication', function () {
    $this->get(route('traders.index'))->assertRedirect(route('login'));
});

test('traders page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('traders.index'))
        ->assertOk()
        ->assertSee('Traders');
});

test('trader profile page loads', function () {
    $this->actingAs($this->user)
        ->get(route('traders.show', $this->trader))
        ->assertOk()
        ->assertSee($this->trader->name);
});

// --- Search ---

test('traders page can search users by name', function () {
    $alice = User::factory()->create(['name' => 'Alice Trader']);
    $bob = User::factory()->create(['name' => 'Bob Investor']);

    $this->actingAs($this->user)
        ->get(route('traders.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertSee('Alice Trader')
        ->assertDontSee('Bob Investor');
});

test('traders page excludes current user from trader list', function () {
    $response = $this->actingAs($this->user)
        ->get(route('traders.index'))
        ->assertOk();

    // The user should appear in nav but not in the traders grid
    $traders = User::where('id', '!=', $this->user->id)->get();
    expect($traders->pluck('id'))->not->toContain($this->user->id);
});

// --- Follow / Unfollow ---

test('user can follow another trader', function () {
    $this->actingAs($this->user)
        ->post(route('traders.follow', $this->trader))
        ->assertRedirect();

    expect($this->user->isFollowing($this->trader))->toBeTrue();
});

test('user can unfollow a trader', function () {
    $this->user->following()->attach($this->trader->id);

    $this->actingAs($this->user)
        ->delete(route('traders.unfollow', $this->trader))
        ->assertRedirect();

    expect($this->user->isFollowing($this->trader))->toBeFalse();
});

test('user cannot follow themselves', function () {
    $this->actingAs($this->user)
        ->post(route('traders.follow', $this->user))
        ->assertRedirect();

    expect($this->user->isFollowing($this->user))->toBeFalse();
});

test('following the same user twice does not create duplicate', function () {
    $this->actingAs($this->user)
        ->post(route('traders.follow', $this->trader));

    $this->actingAs($this->user)
        ->post(route('traders.follow', $this->trader));

    expect($this->user->following()->count())->toBe(1);
});

// --- Relationships ---

test('follower count is displayed on traders page', function () {
    $this->user->following()->attach($this->trader->id);

    $this->actingAs($this->user)
        ->get(route('traders.index'))
        ->assertSee('1 follower');
});

test('trader profile shows follower and following counts', function () {
    $other = User::factory()->create();
    $this->user->following()->attach($this->trader->id);
    $this->trader->following()->attach($other->id);

    $this->actingAs($this->user)
        ->get(route('traders.show', $this->trader))
        ->assertSee('1')
        ->assertSee('follower');
});

test('follow button shows on trader profile when not following', function () {
    $this->actingAs($this->user)
        ->get(route('traders.show', $this->trader))
        ->assertSee('Follow');
});

test('unfollow button shows on trader profile when following', function () {
    $this->user->following()->attach($this->trader->id);

    $this->actingAs($this->user)
        ->get(route('traders.show', $this->trader))
        ->assertSee('Unfollow');
});
