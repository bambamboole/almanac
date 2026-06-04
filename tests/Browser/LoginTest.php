<?php

use App\Models\User;

it('logs in a user with valid credentials', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login');

    $page->assertSee('Log in')
        ->assertNoJavaScriptErrors()
        ->fill('email', 'ada@example.com')
        ->fill('password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    $this->assertAuthenticated();
});

it('submits the login form when pressing Return in the password field', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login');

    $page->assertSee('Log in')
        ->assertNoJavaScriptErrors()
        ->fill('email', 'ada@example.com')
        ->fill('password', 'password')
        ->keys('input[name="password"]', 'Enter')
        ->assertPathIs('/dashboard');

    $this->assertAuthenticated();
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login');

    $page->fill('email', 'ada@example.com')
        ->fill('password', 'wrong-password')
        ->click('@login-button')
        ->assertPathIs('/login')
        ->assertSee('These credentials do not match our records.');

    $this->assertGuest();
});
