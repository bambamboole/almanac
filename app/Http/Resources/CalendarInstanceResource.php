<?php

namespace App\Http\Resources;

use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin DavCalendarInstance
 */
class CalendarInstanceResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: int,
     *     dav_calendar_id: int,
     *     display_name: string,
     *     description: string|null,
     *     color: string|null,
     *     timezone: string|null,
     *     components: array<int, string>,
     *     access: int,
     *     is_owner: bool,
     *     is_shared: bool,
     *     can_write: bool,
     *     transparent: bool,
     *     share_display_name: string|null,
     *     share_invite_status: int|null,
     *     events: array<int, array{
     *         id: int,
     *         dav_calendar_id: int,
     *         etag: string,
     *         starts_at: string|null,
     *         ends_at: string|null,
     *         is_all_day: bool,
     *         calendar: array{id: int, dav_calendar_id: int, display_name: string|null, color: string|null, access: int|null, can_write: bool},
     *         data: array{
     *             uid: string|null,
     *             componentType: string|null,
     *             summary: string|null,
     *             description: string|null,
     *             location: string|null,
     *             status: string|null,
     *             url: string|null,
     *             startsAt: string|null,
     *             endsAt: string|null,
     *             isAllDay: bool,
     *             isRecurring: bool,
     *             timezone: string|null
     *         }
     *     }>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dav_calendar_id' => $this->dav_calendar_id,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'color' => $this->color,
            'timezone' => $this->timezone,
            'components' => $this->components($this->calendar->components),
            'access' => $this->access,
            'is_owner' => $this->access === DavCalendarInstance::AccessOwner,
            'is_shared' => $this->access !== DavCalendarInstance::AccessOwner,
            'can_write' => in_array($this->access, [
                DavCalendarInstance::AccessOwner,
                DavCalendarInstance::AccessReadWrite,
            ], true),
            'transparent' => $this->transparent,
            'share_display_name' => $this->share_display_name,
            'share_invite_status' => $this->share_invite_status,
            'events' => $this->events($request),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $components
     * @return array<int, string>
     */
    private function components(array $components): array
    {
        return collect($components)
            ->filter(fn (mixed $component): bool => is_string($component))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     dav_calendar_id: int,
     *     etag: string,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     is_all_day: bool,
     *     calendar: array{id: int, dav_calendar_id: int, display_name: string|null, color: string|null, access: int|null, can_write: bool},
     *     data: array{
     *         uid: string|null,
     *         componentType: string|null,
     *         summary: string|null,
     *         description: string|null,
     *         location: string|null,
     *         status: string|null,
     *         url: string|null,
     *         startsAt: string|null,
     *         endsAt: string|null,
     *         isAllDay: bool,
     *         isRecurring: bool,
     *         timezone: string|null
     *     }
     * }>
     */
    private function events(Request $request): array
    {
        if (! $this->calendar->relationLoaded('objects')) {
            return [];
        }

        /** @var Collection<int, DavCalendarObject> $events */
        $events = $this->calendar->objects;

        return $events
            ->map(fn (DavCalendarObject $event): array => (new CalendarEventResource($event, $this->resource))->toArray($request))
            ->values()
            ->all();
    }
}
