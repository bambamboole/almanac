<?php

namespace App\Policies;

use App\Models\User;
use App\Support\CalendarAccess;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

class DavCalendarObjectPolicy
{
    public function update(User $user, DavCalendarObject $event): bool
    {
        return CalendarAccess::canWrite($user, $event->calendar);
    }

    public function delete(User $user, DavCalendarObject $event): bool
    {
        return $this->update($user, $event);
    }
}
