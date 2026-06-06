<?php

namespace App\Actions\Dav;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

class CreateDefaultDavCollections
{
    private const DefaultCalendarUri = 'personal';

    private const DefaultAddressBookUri = 'personal';

    public function handle(User $user): void
    {
        if (! DavCalendarInstance::query()->where('owner_id', $user->id)->where('uri', self::DefaultCalendarUri)->exists()) {
            $user->createDavCalendar([
                'uri' => self::DefaultCalendarUri,
                'display_name' => 'Personal',
                'components' => ['VEVENT', 'VTODO'],
                'sync_token' => 1,
            ]);
        }

        if (! DavAddressBook::query()->where('owner_id', $user->id)->where('uri', self::DefaultAddressBookUri)->exists()) {
            $user->createDavAddressBook([
                'uri' => self::DefaultAddressBookUri,
                'display_name' => 'Personal',
                'sync_token' => 1,
            ]);
        }
    }
}
