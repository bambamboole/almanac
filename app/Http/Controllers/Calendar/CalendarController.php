<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Resources\CalendarCollection;
use App\Http\Resources\CalendarEventCollection;
use App\Models\CalendarEvent;
use App\Support\CalendarAccess;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController
{
    public function __invoke(Request $request): Response
    {
        $start = now()->subMonths(1)->startOfDay();
        $end = now()->addMonths(3)->endOfDay();
        $user = $request->user();
        $calendarIds = CalendarAccess::accessibleCalendarIds($user);

        $calendars = DavCalendarInstance::query()
            ->where('owner_id', $user->id)
            ->with('calendar:id,components')
            ->orderBy('display_name')
            ->get();

        $events = CalendarEvent::query()
            ->with(['calendar.instances' => fn ($query) => $query->where('owner_id', $user->id)])
            ->whereIn('dav_calendar_id', $calendarIds)
            ->where('component_type', 'VEVENT')
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('calendar/index', [
            'calendars' => new CalendarCollection($calendars),
            'events' => new CalendarEventCollection($events),
            'window' => $this->windowPayload($start, $end),
        ]);
    }

    /**
     * @return array{start: string, end: string}
     */
    private function windowPayload(CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            'start' => $start->toISOString(),
            'end' => $end->toISOString(),
        ];
    }
}
