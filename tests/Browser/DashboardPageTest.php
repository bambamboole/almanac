<?php

use App\Models\User;

it('renders the dashboard shell and stat labels', function () {
    $user = User::factory()->create([
        'name' => 'Manuel Christlieb',
        'email' => 'manuel@example.com',
    ]);

    $this->actingAs($user);

    $page = visit('/dashboard');

    $page->assertSee('Manuel')
        ->assertSee('manuel@example.com')
        ->assertSee('Settings')
        ->assertSee('Log out')
        ->assertSee('This week')
        ->assertSee('Contacts')
        ->assertSee('Agenda');
});
