<?php

namespace App\Actions\Dav;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

class CreateDefaultDavCollections
{
    public function handle(User $user): void
    {
        if (! DavCalendarInstance::query()->where('owner_id', $user->id)->where('uri', config('dav.default_calendar_uri'))->exists()) {
            $calendar = DavCalendar::query()->create([
                'owner_id' => $user->id,
                'components' => ['VEVENT', 'VTODO'],
                'sync_token' => 1,
            ]);

            $calendar->instances()->create([
                'owner_id' => $user->id,
                'uri' => config('dav.default_calendar_uri'),
                'display_name' => 'Personal',
            ]);
        }

        DavAddressBook::query()->firstOrCreate(
            ['owner_id' => $user->id, 'uri' => config('dav.default_address_book_uri')],
            ['display_name' => 'Personal', 'sync_token' => 1],
        );
    }
}
