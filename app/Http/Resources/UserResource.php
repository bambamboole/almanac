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
            'calendars_count' => $this->whenCounted('calendars'),
            'calendar_instances_count' => $this->whenCounted('calendarInstances'),
            'address_books_count' => $this->whenCounted('addressBooks'),
            'calendar_instances' => $this->whenLoaded(
                'calendarInstances',
                fn () => CalendarInstanceResource::collection($this->calendarInstances),
            ),
            'address_books' => $this->whenLoaded(
                'addressBooks',
                fn () => ContactAddressBookResource::collection($this->addressBooks),
            ),
        ];
    }

    private function toIsoString(?CarbonInterface $date): ?string
    {
        return $date?->toISOString();
    }
}
