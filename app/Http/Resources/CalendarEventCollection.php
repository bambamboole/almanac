<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CalendarEventCollection extends ResourceCollection
{
    public static $wrap = null;

    public $collects = CalendarEventResource::class;
}
