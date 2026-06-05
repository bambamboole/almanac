<?php

namespace App\Http\Controllers;

use Bambamboole\LaravelDav\Models\DavCalendarObject;
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

        $userEvents = fn () => DavCalendarObject::query()
            ->whereHas('calendar', fn ($query) => $query->whereBelongsTo($user))
            ->where('component_type', 'VEVENT');

        $todayEvents = $userEvents()
            ->with(['calendar:id,display_name,color'])
            ->where('starts_at', '>=', $startOfToday)
            ->where('starts_at', '<', $startOfTomorrow)
            ->orderBy('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (DavCalendarObject $event): array => [
                'id' => $event->id,
                'summary' => $event->data->summary,
                'location' => $event->data->location,
                'starts_at' => $event->starts_at->toISOString(),
                'ends_at' => $event->ends_at?->toISOString(),
                'all_day' => $event->is_all_day,
                'calendar' => [
                    'name' => $event->calendar->display_name,
                    'color' => $event->calendar->color,
                ],
            ]);

        return Inertia::render('dashboard', [
            'todayEvents' => $todayEvents,
            'stats' => [
                'todayEventCount' => $userEvents()
                    ->where('starts_at', '>=', $startOfToday)
                    ->where('starts_at', '<', $startOfTomorrow)
                    ->count(),
                'weekEventCount' => $userEvents()
                    ->where('starts_at', '>=', now()->startOfWeek())
                    ->where('starts_at', '<', now()->startOfWeek()->addWeek())
                    ->count(),
                'contactCount' => DavCard::query()
                    ->whereHas('addressBook', fn ($query) => $query->whereBelongsTo($user))
                    ->count(),
            ],
        ]);
    }
}
