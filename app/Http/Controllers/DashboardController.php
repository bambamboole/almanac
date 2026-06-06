<?php

namespace App\Http\Controllers;

use App\Http\Resources\CalendarEventCollection;
use App\Models\CalendarEvent;
use App\Support\CalendarAccess;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $startOfToday = now()->startOfDay();
        $startOfTomorrow = now()->addDay()->startOfDay();
        $calendarIds = CalendarAccess::accessibleCalendarIds($user);

        $userEvents = fn () => CalendarEvent::query()
            ->whereIn('dav_calendar_id', $calendarIds)
            ->where('component_type', 'VEVENT');

        $todayEvents = $userEvents()
            ->with(['calendar.instances' => fn ($query) => $query->where('owner_id', $user->id)])
            ->where('starts_at', '>=', $startOfToday)
            ->where('starts_at', '<', $startOfTomorrow)
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $todayEventCount = $userEvents()
            ->where('starts_at', '>=', $startOfToday)
            ->where('starts_at', '<', $startOfTomorrow)
            ->count();

        $weekEventCount = $userEvents()
            ->where('starts_at', '>=', now()->startOfWeek())
            ->where('starts_at', '<', now()->startOfWeek()->addWeek())
            ->count();

        $contactCount = DavCard::query()
            ->whereHas('addressBook', fn ($query) => $query->where('owner_id', $user->id))
            ->count();

        return Inertia::render('dashboard', [
            'todayEvents' => new CalendarEventCollection($todayEvents),
            'stats' => $this->stats($todayEventCount, $weekEventCount, $contactCount),
        ]);
    }

    /**
     * @return array{todayEventCount: int, weekEventCount: int, contactCount: int}
     */
    private function stats(int $todayEventCount, int $weekEventCount, int $contactCount): array
    {
        return [
            'todayEventCount' => $todayEventCount,
            'weekEventCount' => $weekEventCount,
            'contactCount' => $contactCount,
        ];
    }
}
