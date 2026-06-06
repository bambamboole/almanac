<?php

namespace App\Http\Resources;

use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DavCalendarInstance
 */
class CalendarResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: int, display_name: string, description: string|null, color: string|null, timezone: string|null, components: array<int, string>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->dav_calendar_id,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'color' => $this->color,
            'timezone' => $this->timezone,
            'components' => $this->components($this->calendar->components),
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
}
