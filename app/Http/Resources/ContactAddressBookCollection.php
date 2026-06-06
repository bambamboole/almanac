<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ContactAddressBookCollection extends ResourceCollection
{
    public static $wrap = null;

    public $collects = ContactAddressBookResource::class;
}
