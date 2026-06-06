<?php

namespace App\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    use ContactDataRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contact'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.formattedName' => ['nullable', 'string', 'max:255'],
            ...$this->contactDataRules(),
            'expected_etag' => ['required', 'string'],
        ];
    }
}
