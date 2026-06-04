<?php

namespace App\Http\Requests\Contacts;

use Bambamboole\LaravelDav\Models\DavAddressBook;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DavAddressBook::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
