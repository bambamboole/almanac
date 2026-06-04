<?php

use App\Models\User;

it('renders the dashboard greeting and stat labels', function () {
    $user = User::factory()->create(['name' => 'Manuel Christlieb']);

    $this->actingAs($user);

    $page = visit('/dashboard');

    $page->assertSee('Manuel')
        ->assertSee('This week')
        ->assertSee('Contacts')
        ->assertSee('Agenda');
});
