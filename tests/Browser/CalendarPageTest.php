<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('groups all-day and timed events on the same day together', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user, [
        'display_name' => 'Personal',
    ]);
    $eventDate = today();

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData([
        'summary' => 'All-day planning',
        'startsAt' => $eventDate->copy()->startOfDay(),
        'endsAt' => $eventDate->copy()->addDay()->startOfDay(),
        'isAllDay' => true,
    ]))->create();

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData([
        'summary' => 'Morning focus',
        'startsAt' => $eventDate->copy()->setTime(9, 0),
        'endsAt' => $eventDate->copy()->setTime(10, 0),
        'isAllDay' => false,
    ]))->create();

    $this->actingAs($user);

    $page = visit('/calendar');

    $page->assertSee('Calendar')
        ->assertSee('Organize your events and appointments.')
        ->assertSee('All-day planning')
        ->assertSee('Morning focus')
        ->assertScript(
            "document.querySelectorAll('[data-calendar-day-group]').length",
            1,
        )
        ->assertScript(
            "[...document.querySelectorAll('[data-calendar-day-group]')].filter((section) => section.textContent.includes('All-day planning') && section.textContent.includes('Morning focus')).length",
            1,
        )
        ->assertScript(
            "(() => {
                const button = document.querySelector('.fc-dayGridMonth-button.fc-button-active');
                const probe = document.createElement('span');
                probe.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim();
                document.body.appendChild(probe);
                const secondary = getComputedStyle(probe).backgroundColor;
                const styles = getComputedStyle(button);
                probe.remove();

                return styles.backgroundColor === secondary && styles.color !== secondary;
            })()",
            true,
        )
        ->assertScript(
            "(() => {
                const height = document.querySelector('[data-calendar-view]').getBoundingClientRect().height;

                return height >= 570 && height <= 737;
            })()",
            true,
        )
        ->assertNoJavaScriptErrors();
});
