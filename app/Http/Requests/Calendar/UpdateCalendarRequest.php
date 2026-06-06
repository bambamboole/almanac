<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('calendarInstance'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:9'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'components' => ['sometimes', 'array', 'min:1'],
            'components.*' => [Rule::in(['VEVENT', 'VTODO', 'VJOURNAL'])],
        ];
    }
}
