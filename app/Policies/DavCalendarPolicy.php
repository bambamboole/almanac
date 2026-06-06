<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;

class DavCalendarPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, DavCalendar $calendar): bool
    {
        return $user->hasPermission(Permission::CollectionsManage)
            || $calendar->instanceFor($user) !== null;
    }

    public function update(User $user, DavCalendar $calendar): bool
    {
        return $calendar->owner_id === $user->id
            || $user->hasPermission(Permission::CollectionsManage);
    }

    public function delete(User $user, DavCalendar $calendar): bool
    {
        return $this->update($user, $calendar);
    }
}
