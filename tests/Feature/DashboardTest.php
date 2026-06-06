<?php

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
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
            ->has('auth.user.calendar_instances')
            ->has('auth.user.address_books')
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
    $calendar = davCalendarFor($user, ['color' => '#4F6043']);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Morning planning']))->create([
        'component_type' => 'VEVENT',
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(10, 0),
        'is_all_day' => false,
    ]);

    $book = DavAddressBook::factory()->for($user, 'owner')->create();
    DavCard::factory()->count(3)->for($book, 'addressBook')->create();

    $other = User::factory()->create();
    $otherCal = davCalendarFor($other);
    DavCalendarObject::factory()->for($otherCal, 'calendar')->create([
        'component_type' => 'VEVENT',
        'starts_at' => now()->setTime(11, 0),
        'ends_at' => now()->setTime(12, 0),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('auth.user.calendar_instances', 1)
            ->where('auth.user.calendar_instances.0.events.0.data.summary', 'Morning planning')
            ->has('auth.user.address_books', 1)
            ->where('auth.user.address_books.0.cards_count', 3)
            ->missing('stats')
            ->missing('todayEvents')
        );
});

it('includes shared calendar events in dashboard counts and today agenda', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner, ['display_name' => 'Owner calendar']);
    $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Shared standup']))->create([
        'component_type' => 'VEVENT',
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(10, 0),
        'is_all_day' => false,
    ]);

    $this->actingAs($recipient)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.user.calendar_instances', 1)
            ->where('auth.user.calendar_instances.0.events.0.data.summary', 'Shared standup')
            ->missing('stats')
            ->missing('todayEvents')
        );
});
