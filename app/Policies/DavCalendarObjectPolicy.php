<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

class DavCalendarObjectPolicy
{
    public function update(User $user, DavCalendarObject $event): bool
    {
        return $user->hasPermission(Permission::CollectionsManage)
            || ($event->calendar->instanceFor($user)?->isWritable() ?? false);
    }

    public function delete(User $user, DavCalendarObject $event): bool
    {
        return $this->update($user, $event);
    }
}
