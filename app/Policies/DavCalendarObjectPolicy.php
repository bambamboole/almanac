<?php

namespace App\Policies;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

class DavCalendarObjectPolicy
{
    public function update(User $user, DavCalendarObject $event): bool
    {
        return $event->calendar->owner_id === $user->id;
    }

    public function delete(User $user, DavCalendarObject $event): bool
    {
        return $event->calendar->owner_id === $user->id;
    }
}
