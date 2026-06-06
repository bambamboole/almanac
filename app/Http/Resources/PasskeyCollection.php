<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PasskeyCollection extends ResourceCollection
{
    public static $wrap = null;

    public $collects = PasskeyResource::class;
}
