<?php

namespace App\Actions\Calendar;

use App\Actions\Calendar\Concerns\MapsEventFields;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Support\DtoFactory;

class CreateCalendarObject
{
    use MapsEventFields;

    /**
     * @param  array<string, mixed>  $fields
     */
    public function handle(DavCalendar $calendar, array $fields): DavCalendarObject
    {
        $object = DavCalendarObject::query()->create([
            'dav_calendar_id' => $calendar->id,
            'data' => DtoFactory::calendarObjectData([
                'componentType' => 'VEVENT',
                'timezone' => $calendar->timezone,
                ...$this->mapEventFields($fields),
            ]),
        ]);

        return $object->refresh();
    }
}
