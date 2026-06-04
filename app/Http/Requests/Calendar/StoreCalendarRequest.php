<?php

namespace App\Http\Requests\Calendar;

use Bambamboole\LaravelDav\Models\DavCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DavCalendar::class);
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
            'components' => ['required', 'array', 'min:1'],
            'components.*' => [Rule::in(['VEVENT', 'VTODO', 'VJOURNAL'])],
        ];
    }
}
