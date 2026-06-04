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
     * @return array<string, mixed>
     *
     * Transform the resource into an array.
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
            'address_books_count' => $this->whenCounted('addressBooks'),
        ];
    }

    private function toIsoString(?CarbonInterface $date): ?string
    {
        return $date?->toISOString();
    }
}
