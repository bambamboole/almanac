<?php

namespace App\Http\Controllers\Calendar;

use App\Actions\Calendar\CreateCalendarObject;
use App\Actions\Calendar\DeleteCalendarObject;
use App\Actions\Calendar\UpdateCalendarObject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\DeleteCalendarEventRequest;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CalendarEventController extends Controller
{
    public function store(StoreCalendarEventRequest $request, CreateCalendarObject $action): RedirectResponse
    {
        $calendar = DavCalendar::query()->findOrFail($request->integer('calendar_id'));

        $action->handle($calendar, $request->safe()->except('calendar_id'));

        return Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')])->back();
    }

    public function update(UpdateCalendarEventRequest $request, DavCalendarObject $event, UpdateCalendarObject $action): RedirectResponse
    {
        $action->handle($event, $request->safe()->except('expected_etag'), $request->string('expected_etag')->value());

        return Inertia::flash('toast', ['type' => 'success', 'message' => __('Event updated.')])->back();
    }

    public function destroy(DeleteCalendarEventRequest $request, DavCalendarObject $event, DeleteCalendarObject $action): RedirectResponse
    {
        $action->handle($event, $request->string('expected_etag')->value());

        return Inertia::flash('toast', ['type' => 'success', 'message' => __('Event deleted.')])->back();
    }
}
