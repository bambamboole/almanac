<?php

namespace App\Actions\Calendar;

use App\Actions\Concerns\RecordsDavChanges;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCalendarObject
{
    use RecordsDavChanges;

    /**
     * @param  array<string, mixed>  $fields
     */
    public function handle(DavCalendar $calendar, array $fields): DavCalendarObject
    {
        return DB::transaction(function () use ($calendar, $fields): DavCalendarObject {
            $uid = (string) Str::uuid();

            $object = DavCalendarObject::query()->create([
                'dav_calendar_id' => $calendar->id,
                'uri' => $uid.'.ics',
                'uid' => $uid,
                'component_type' => 'VEVENT',
                'timezone' => $calendar->timezone,
                'last_modified_at' => now(),
                ...$fields,
            ]);

            $this->recordCalendarChange($calendar, $object->uri, self::OperationAdd);

            return $object->refresh();
        });
    }
}
