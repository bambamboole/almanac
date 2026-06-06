<?php

namespace App\Http\Requests\Calendar;

use App\Support\CalendarAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'calendar_id' => [
                'required',
                Rule::exists('dav_calendar_instances', 'dav_calendar_id')
                    ->where('owner_id', $this->user()->id)
                    ->whereIn('access', CalendarAccess::writeAccessLevels()),
            ],
            'data' => ['required', 'array'],
            'data.summary' => ['required', 'string', 'max:255'],
            'data.description' => ['nullable', 'string'],
            'data.location' => ['nullable', 'string', 'max:255'],
            'data.startsAt' => ['required', 'date'],
            'data.endsAt' => ['required', 'date', 'after_or_equal:data.startsAt'],
            'data.isAllDay' => ['sometimes', 'boolean'],
            'data.status' => ['nullable', 'string', 'max:64'],
            'data.url' => ['nullable', 'string', 'url', 'max:2048'],
        ];
    }
}
