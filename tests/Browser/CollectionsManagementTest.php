<?php

use App\Models\User;

it('does not expose collection management in settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit('/settings/profile');

    $page->assertSee('Settings')
        ->assertNoJavaScriptErrors()
        ->assertSeeIn('nav[aria-label="Settings"]', 'Profile')
        ->assertSeeIn('nav[aria-label="Settings"]', 'Security')
        ->assertSeeIn('nav[aria-label="Settings"]', 'DAV Credentials')
        ->assertSeeIn('nav[aria-label="Settings"]', 'Appearance')
        ->assertDontSee('Collections')
        ->assertDontSee('Manage your calendars.')
        ->assertDontSee('Manage your address books.');
});
