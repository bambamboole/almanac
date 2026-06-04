<?php

namespace App\Http\Controllers\Calendar;

use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController
{
    public function __invoke(Request $request): Response
    {
        $start = now()->subMonths(1)->startOfDay();
        $end = now()->addMonths(3)->endOfDay();

        $calendars = DavCalendar::query()
            ->whereBelongsTo($request->user())
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'description', 'color', 'timezone', 'components'])
            ->map(fn (DavCalendar $calendar): array => [
                'id' => $calendar->id,
                'name' => $calendar->display_name,
                'description' => $calendar->description,
                'color' => $calendar->color,
                'timezone' => $calendar->timezone,
                'components' => $calendar->components,
            ]);

        $events = DavCalendarObject::query()
            ->with(['calendar:id,display_name,color'])
            ->whereHas('calendar', fn ($query) => $query->whereBelongsTo($request->user()))
            ->where('component_type', 'VEVENT')
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->orderBy('starts_at')
            ->get([
                'id',
                'dav_calendar_id',
                'summary',
                'description',
                'location',
                'starts_at',
                'ends_at',
                'is_all_day',
                'status',
                'url',
                'etag',
            ])
            ->map(fn (DavCalendarObject $event): array => [
                'id' => $event->id,
                'calendar_id' => $event->dav_calendar_id,
                'calendar' => [
                    'id' => $event->calendar->id,
                    'name' => $event->calendar->display_name,
                    'color' => $event->calendar->color,
                ],
                'summary' => $event->summary,
                'description' => $event->description,
                'location' => $event->location,
                'starts_at' => $event->starts_at->toISOString(),
                'ends_at' => $event->ends_at->toISOString(),
                'starts_on' => $event->starts_at->toDateString(),
                'ends_on' => $event->ends_at->toDateString(),
                'all_day' => $event->is_all_day,
                'status' => $event->status,
                'url' => $event->url,
                'etag' => $event->etag,
            ]);

        return Inertia::render('calendar/index', [
            'calendars' => $calendars,
            'events' => $events,
            'window' => [
                'start' => $start->toISOString(),
                'end' => $end->toISOString(),
            ],
        ]);
    }
}
