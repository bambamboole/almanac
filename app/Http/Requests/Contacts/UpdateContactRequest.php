<?php

namespace App\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contact'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:255'],
            'given_name' => ['nullable', 'string', 'max:255'],
            'family_name' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email_addresses' => ['nullable', 'array'],
            'email_addresses.*' => ['array:label,value,types,is_preferred,group'],
            'email_addresses.*.label' => ['nullable', 'string', 'max:64'],
            'email_addresses.*.value' => ['nullable', 'email', 'max:255'],
            'email_addresses.*.types' => ['nullable', 'array'],
            'email_addresses.*.types.*' => ['string', 'max:32'],
            'email_addresses.*.is_preferred' => ['boolean'],
            'email_addresses.*.group' => ['nullable', 'string', 'max:64'],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*' => ['array:label,value,types,is_preferred,group'],
            'phone_numbers.*.label' => ['nullable', 'string', 'max:64'],
            'phone_numbers.*.value' => ['nullable', 'string', 'max:64'],
            'phone_numbers.*.types' => ['nullable', 'array'],
            'phone_numbers.*.types.*' => ['string', 'max:32'],
            'phone_numbers.*.is_preferred' => ['boolean'],
            'phone_numbers.*.group' => ['nullable', 'string', 'max:64'],
            'addresses' => ['nullable', 'array'],
            'addresses.*' => ['array:label,po_box,extended,street,city,region,postal_code,country,country_code,types,is_preferred,group'],
            'addresses.*.label' => ['nullable', 'string', 'max:64'],
            'addresses.*.po_box' => ['nullable', 'string', 'max:255'],
            'addresses.*.extended' => ['nullable', 'string', 'max:255'],
            'addresses.*.street' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:255'],
            'addresses.*.region' => ['nullable', 'string', 'max:255'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:64'],
            'addresses.*.country' => ['nullable', 'string', 'max:255'],
            'addresses.*.country_code' => ['nullable', 'string', 'max:8'],
            'addresses.*.types' => ['nullable', 'array'],
            'addresses.*.types.*' => ['string', 'max:32'],
            'addresses.*.is_preferred' => ['boolean'],
            'addresses.*.group' => ['nullable', 'string', 'max:64'],
            'expected_etag' => ['required', 'string'],
        ];
    }
}
