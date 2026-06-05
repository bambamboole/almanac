<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('event'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.summary' => ['nullable', 'string', 'max:255'],
            'data.description' => ['nullable', 'string'],
            'data.location' => ['nullable', 'string', 'max:255'],
            'data.startsAt' => ['sometimes', 'date'],
            'data.endsAt' => ['sometimes', 'date', 'after_or_equal:data.startsAt'],
            'data.isAllDay' => ['sometimes', 'boolean'],
            'data.status' => ['nullable', 'string', 'max:64'],
            'data.url' => ['nullable', 'string', 'url', 'max:2048'],
            'expected_etag' => ['required', 'string'],
        ];
    }
}
