<?php

namespace App\Actions\Calendar\Concerns;

trait MapsEventFields
{
    /**
     * Translate the snake_case HTTP field names into the camelCase keys the
     * CalendarObjectData DTO expects. Only the provided keys are mapped, so the
     * result can be merged onto an existing DTO for partial updates.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function mapEventFields(array $fields): array
    {
        $keyMap = [
            'starts_at' => 'startsAt',
            'ends_at' => 'endsAt',
            'is_all_day' => 'isAllDay',
        ];

        $mapped = [];

        foreach ($fields as $key => $value) {
            $mapped[$keyMap[$key] ?? $key] = $value;
        }

        return $mapped;
    }
}
