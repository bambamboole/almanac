<?php

namespace App\Actions\Dav;

use Bambamboole\LaravelDav\Models\DavCredential;

class RevokeDavCredential
{
    public function handle(DavCredential $credential): void
    {
        $credential->delete();
    }
}
