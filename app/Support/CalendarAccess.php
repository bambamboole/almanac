<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Illuminate\Support\Collection;

class CalendarAccess
{
    /**
     * @return Collection<int, int>
     */
    public static function accessibleCalendarIds(User $user): Collection
    {
        return $user->calendarInstances()
            ->pluck('dav_calendar_id');
    }

    public static function canView(User $user, DavCalendar $calendar): bool
    {
        return $user->hasPermission(Permission::CollectionsManage)
            || self::instanceFor($user, $calendar) !== null;
    }

    public static function canWrite(User $user, DavCalendar $calendar): bool
    {
        if ($user->hasPermission(Permission::CollectionsManage)) {
            return true;
        }

        $instance = self::instanceFor($user, $calendar);

        return $instance !== null && in_array($instance->access, self::writeAccessLevels(), true);
    }

    public static function instanceFor(User $user, DavCalendar $calendar): ?DavCalendarInstance
    {
        if ($calendar->relationLoaded('instances')) {
            return $calendar->instances->firstWhere('owner_id', $user->id);
        }

        return $calendar->instances()
            ->where('owner_id', $user->id)
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public static function writeAccessLevels(): array
    {
        return [
            DavCalendarInstance::AccessOwner,
            DavCalendarInstance::AccessReadWrite,
        ];
    }
}
