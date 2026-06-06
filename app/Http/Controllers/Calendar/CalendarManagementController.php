<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreCalendarRequest;
use App\Http\Requests\Calendar\UpdateCalendarRequest;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CalendarManagementController extends Controller
{
    public function store(StoreCalendarRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->createDavCalendar([
            'uri' => Str::slug($data['display_name']) ?: (string) Str::uuid(),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'components' => $this->normalizeComponents($data['components']),
            'sync_token' => 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar created.')]);

        return back();
    }

    public function update(UpdateCalendarRequest $request, DavCalendar $calendar): RedirectResponse
    {
        $data = $request->validated();

        $calendar->ownerInstance()->firstOrFail()->updateDavProperties([
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'components' => $this->normalizeComponents($data['components']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar updated.')]);

        return back();
    }

    public function destroy(DavCalendar $calendar): RedirectResponse
    {
        $this->authorize('delete', $calendar);

        $calendar->ownerInstance()->firstOrFail()->deleteDavCollection();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar deleted.')]);

        return back();
    }

    /**
     * @param  array<int, string>  $components
     * @return array<int, string>
     */
    private function normalizeComponents(array $components): array
    {
        return array_values(array_unique(['VEVENT', ...$components]));
    }
}
