<?php

namespace App\Http\Requests\Calendar;

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
                Rule::exists('dav_calendars', 'id')->where('user_id', $this->user()->id),
            ],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'is_all_day' => ['sometimes', 'boolean'],
            'status' => ['nullable', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'url', 'max:2048'],
        ];
    }
}
