<?php

namespace App\Models;

use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Database\Eloquent\Attributes\Hidden;

/**
 * Read-side projection of a DAV calendar object: hides the internal payload
 * columns and the verbatim `data.raw` iCalendar so responses carry only the
 * typed CalendarObjectData the frontend consumes.
 */
#[Hidden(['calendar_data', 'size'])]
class CalendarEvent extends DavCalendarObject
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        unset($array['data']['raw']);

        return $array;
    }
}
