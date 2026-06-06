<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CalendarCollection extends ResourceCollection
{
    public static $wrap = null;

    public $collects = CalendarInstanceResource::class;
}
