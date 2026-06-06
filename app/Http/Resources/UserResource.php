<?php

namespace App\Http\Resources;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     email_verified_at: string|null,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     role: mixed,
     *     calendars_count?: int,
     *     calendar_instances_count?: int,
     *     address_books_count?: int,
     *     calendar_instances?: mixed,
     *     address_books?: mixed
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->toIsoString($this->email_verified_at),
            'created_at' => $this->toIsoString($this->created_at),
            'updated_at' => $this->toIsoString($this->updated_at),
            'role' => RoleResource::make($this->whenLoaded('role')),
            'calendars_count' => $this->when(
                array_key_exists('calendars_count', $this->resource->getAttributes()),
                fn (): int => (int) $this->calendars_count,
            ),
            'calendar_instances_count' => $this->when(
                array_key_exists('calendar_instances_count', $this->resource->getAttributes()),
                fn (): int => (int) $this->calendar_instances_count,
            ),
            'address_books_count' => $this->when(
                array_key_exists('address_books_count', $this->resource->getAttributes()),
                fn (): int => (int) $this->address_books_count,
            ),
            'calendar_instances' => $this->whenLoaded(
                'davCalendarInstances',
                fn () => CalendarInstanceResource::collection($this->davCalendarInstances),
            ),
            'address_books' => $this->whenLoaded(
                'davAddressBooks',
                fn () => ContactAddressBookResource::collection($this->davAddressBooks),
            ),
        ];
    }

    private function toIsoString(?CarbonInterface $date): ?string
    {
        return $date?->toISOString();
    }
}
