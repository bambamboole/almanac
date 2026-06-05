<?php

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->admin()->create();

    $response = $this
        ->actingAs($user)
        ->get('/dashboard');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role.id', $user->role_id)
            ->where('auth.user.role.name', 'admin')
            ->where('auth.user.role.permissions', collect(Permission::cases())->map->value->all())
            ->missing('auth.permissions'));
});

test('team management routes are not available', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/teams');

    $response->assertNotFound();
});

it('shows today/week/contact counts and today agenda scoped to the user', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for($user)->create(['color' => '#4F6043']);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Morning planning']))->create([
        'component_type' => 'VEVENT',
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(10, 0),
        'is_all_day' => false,
    ]);

    $book = DavAddressBook::factory()->for($user)->create();
    DavCard::factory()->count(3)->for($book, 'addressBook')->create();

    $other = User::factory()->create();
    $otherCal = DavCalendar::factory()->for($other)->create();
    DavCalendarObject::factory()->for($otherCal, 'calendar')->create([
        'component_type' => 'VEVENT',
        'starts_at' => now()->setTime(11, 0),
        'ends_at' => now()->setTime(12, 0),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.todayEventCount', 1)
            ->where('stats.weekEventCount', 1)
            ->where('stats.contactCount', 3)
            ->has('todayEvents', 1)
            ->where('todayEvents.0.data.summary', 'Morning planning')
        );
});
