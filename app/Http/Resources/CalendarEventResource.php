<?php

namespace App\Http\Resources;

use App\Models\CalendarEvent;
use Bambamboole\LaravelDav\Dto\CalendarObjectData;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CalendarEvent
 */
class CalendarEventResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: int,
     *     dav_calendar_id: int,
     *     etag: string,
     *     starts_at: string,
     *     ends_at: string,
     *     is_all_day: bool,
     *     calendar: array{id: int, display_name: string, color: string|null, access: int|null, can_write: bool},
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
        $instance = $this->calendarInstance($request);

        return [
            'id' => $this->id,
            'dav_calendar_id' => $this->dav_calendar_id,
            'etag' => $this->etag,
            'starts_at' => $this->requiredIsoString($this->starts_at),
            'ends_at' => $this->requiredIsoString($this->ends_at),
            'is_all_day' => $this->is_all_day,
            'calendar' => [
                'id' => $this->calendar->id,
                'display_name' => $this->string($instance?->display_name),
                'color' => $this->nullableString($instance?->color),
                'access' => $instance?->access,
                'can_write' => $this->canWrite($instance),
            ],
            'data' => $this->eventData($this->data),
        ];
    }

    private function calendarInstance(Request $request): ?DavCalendarInstance
    {
        $userId = $request->user()?->id;

        if ($userId !== null && $this->calendar->relationLoaded('instances')) {
            return $this->calendar->instances->firstWhere('owner_id', $userId);
        }

        if ($userId !== null) {
            return $this->calendar->instances()
                ->where('owner_id', $userId)
                ->first();
        }

        return $this->calendar->ownerInstance;
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
            'startsAt' => $this->nullableIsoString($data->startsAt),
            'endsAt' => $this->nullableIsoString($data->endsAt),
            'isAllDay' => $data->isAllDay,
            'isRecurring' => $data->isRecurring,
            'timezone' => $data->timezone,
        ];
    }

    private function requiredIsoString(?CarbonInterface $date): string
    {
        return $date?->toISOString() ?? '';
    }

    private function nullableIsoString(?CarbonInterface $date): ?string
    {
        return $date?->toISOString();
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function canWrite(?DavCalendarInstance $instance): bool
    {
        return $instance !== null && in_array($instance->access, [
            DavCalendarInstance::AccessOwner,
            DavCalendarInstance::AccessReadWrite,
        ], true);
    }
}
