<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DavCredentialCollection extends ResourceCollection
{
    public static $wrap = null;

    public $collects = DavCredentialResource::class;
}
