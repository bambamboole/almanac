<?php

namespace App\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    use ContactDataRules;

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
            'address_book_id' => [
                'required',
                Rule::exists('dav_address_books', 'id')->where('user_id', $this->user()->id),
            ],
            'data' => ['required', 'array'],
            'data.formattedName' => ['required', 'string', 'max:255'],
            ...$this->contactDataRules(),
        ];
    }
}
