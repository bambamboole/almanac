<?php

namespace App\Http\Resources;

use Bambamboole\LaravelDav\Dto\CalendarObjectData;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DavCalendarObject
 */
class CalendarEventResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(DavCalendarObject $resource, private readonly DavCalendarInstance $calendarInstance)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     dav_calendar_id: int,
     *     etag: string,
     *     starts_at: ?string,
     *     ends_at: ?string,
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
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dav_calendar_id' => $this->dav_calendar_id,
            'etag' => $this->etag,
            'starts_at' => $this->starts_at === null ? null : (string) $this->starts_at->toISOString(),
            'ends_at' => $this->ends_at === null ? null : (string) $this->ends_at->toISOString(),
            'is_all_day' => $this->is_all_day,
            'calendar' => [
                'id' => $this->calendarInstance->id,
                'dav_calendar_id' => $this->calendarInstance->dav_calendar_id,
                'display_name' => $this->calendarInstance->display_name,
                'color' => $this->calendarInstance->color,
                'access' => $this->calendarInstance->access,
                'can_write' => $this->calendarInstance->isWritable(),
            ],
            'data' => $this->eventData($this->data),
        ];
    }

    /**
     * @return array{
     *     uid: string|null,
     *     componentType: string|null,
     *     summary: string|null,
     *     description: string|null,
     *     location: string|null,
     *     status: string|null,
     *     url: string|null,
     *     startsAt: string|null,
     *     endsAt: string|null,
     *     isAllDay: bool,
     *     isRecurring: bool,
     *     timezone: string|null
     * }
     */
    private function eventData(CalendarObjectData $data): array
    {
        return [
            'uid' => $data->uid,
            'componentType' => $data->componentType,
            'summary' => $data->summary,
            'description' => $data->description,
            'location' => $data->location,
            'status' => $data->status,
            'url' => $data->url,
            'startsAt' => $data->startsAt?->toISOString(),
            'endsAt' => $data->endsAt?->toISOString(),
            'isAllDay' => $data->isAllDay,
            'isRecurring' => $data->isRecurring,
            'timezone' => $data->timezone,
        ];
    }
}
