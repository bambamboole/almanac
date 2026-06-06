<?php

namespace App\Actions\Dav;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

class CreateDefaultDavCollections
{
    public function handle(User $user): void
    {
        if (! DavCalendarInstance::query()->where('owner_id', $user->id)->where('uri', config('dav.default_calendar_uri'))->exists()) {
            $user->createDavCalendar([
                'uri' => config('dav.default_calendar_uri'),
                'display_name' => 'Personal',
                'components' => ['VEVENT', 'VTODO'],
                'sync_token' => 1,
            ]);
        }

        if (! DavAddressBook::query()->where('owner_id', $user->id)->where('uri', config('dav.default_address_book_uri'))->exists()) {
            $user->createDavAddressBook([
                'uri' => config('dav.default_address_book_uri'),
                'display_name' => 'Personal',
                'sync_token' => 1,
            ]);
        }
    }
}
