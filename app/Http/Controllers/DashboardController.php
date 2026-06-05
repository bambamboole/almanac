<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
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

        $userEvents = fn () => CalendarEvent::query()
            ->whereHas('calendar', fn ($query) => $query->whereBelongsTo($user))
            ->where('component_type', 'VEVENT');

        $todayEvents = $userEvents()
            ->with(['calendar:id,display_name,color'])
            ->where('starts_at', '>=', $startOfToday)
            ->where('starts_at', '<', $startOfTomorrow)
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

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
