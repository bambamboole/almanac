<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

class DavCalendarInstancePolicy
{
    public function view(User $user, DavCalendarInstance $calendarInstance): bool
    {
        return $calendarInstance->owner_id === $user->id
            || $user->hasPermission(Permission::CollectionsManage);
    }

    public function update(User $user, DavCalendarInstance $calendarInstance): bool
    {
        return $this->view($user, $calendarInstance);
    }

    public function updateBackingCalendar(User $user, DavCalendarInstance $calendarInstance): bool
    {
        return $calendarInstance->access === DavCalendarInstance::AccessOwner
            || $user->hasPermission(Permission::CollectionsManage);
    }

    public function delete(User $user, DavCalendarInstance $calendarInstance): bool
    {
        return $this->view($user, $calendarInstance);
    }
}
