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
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
            'is_all_day' => ['sometimes', 'boolean'],
            'status' => ['nullable', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'url', 'max:2048'],
            'expected_etag' => ['required', 'string'],
        ];
    }
}
