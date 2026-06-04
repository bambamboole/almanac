<?php

namespace App\Actions\Dav;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;

class CreateDefaultDavCollections
{
    public function handle(User $user): void
    {
        DavCalendar::query()->firstOrCreate(
            ['user_id' => $user->id, 'uri' => config('dav.default_calendar_uri')],
            ['display_name' => 'Personal', 'components' => ['VEVENT', 'VTODO'], 'sync_token' => 1],
        );

        DavAddressBook::query()->firstOrCreate(
            ['user_id' => $user->id, 'uri' => config('dav.default_address_book_uri')],
            ['display_name' => 'Personal', 'sync_token' => 1],
        );
    }
}
