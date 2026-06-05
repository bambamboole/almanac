<?php

namespace App\Http\Requests\Contacts;

trait ContactDataRules
{
    /**
     * Validation rules for the shared ContactData payload (everything except
     * `data.formattedName`, whose required-ness differs between create/update).
     *
     * @return array<string, mixed>
     */
    protected function contactDataRules(): array
    {
        return [
            'data.givenName' => ['nullable', 'string', 'max:255'],
            'data.familyName' => ['nullable', 'string', 'max:255'],
            'data.organization' => ['nullable', 'string', 'max:255'],
            'data.jobTitle' => ['nullable', 'string', 'max:255'],
            'data.nickname' => ['nullable', 'string', 'max:255'],
            'data.note' => ['nullable', 'string'],

            'data.emailAddresses' => ['nullable', 'array'],
            'data.emailAddresses.*.label' => ['nullable', 'string', 'max:64'],
            'data.emailAddresses.*.value' => ['nullable', 'email', 'max:255'],
            'data.emailAddresses.*.types' => ['nullable', 'array'],
            'data.emailAddresses.*.types.*' => ['string', 'max:32'],
            'data.emailAddresses.*.isPreferred' => ['boolean'],
            'data.emailAddresses.*.group' => ['nullable', 'string', 'max:64'],

            'data.phoneNumbers' => ['nullable', 'array'],
            'data.phoneNumbers.*.label' => ['nullable', 'string', 'max:64'],
            'data.phoneNumbers.*.value' => ['nullable', 'string', 'max:64'],
            'data.phoneNumbers.*.types' => ['nullable', 'array'],
            'data.phoneNumbers.*.types.*' => ['string', 'max:32'],
            'data.phoneNumbers.*.isPreferred' => ['boolean'],
            'data.phoneNumbers.*.group' => ['nullable', 'string', 'max:64'],

            'data.addresses' => ['nullable', 'array'],
            'data.addresses.*.label' => ['nullable', 'string', 'max:64'],
            'data.addresses.*.poBox' => ['nullable', 'string', 'max:255'],
            'data.addresses.*.extended' => ['nullable', 'string', 'max:255'],
            'data.addresses.*.street' => ['nullable', 'string', 'max:255'],
            'data.addresses.*.city' => ['nullable', 'string', 'max:255'],
            'data.addresses.*.region' => ['nullable', 'string', 'max:255'],
            'data.addresses.*.postalCode' => ['nullable', 'string', 'max:64'],
            'data.addresses.*.country' => ['nullable', 'string', 'max:255'],
            'data.addresses.*.countryCode' => ['nullable', 'string', 'max:8'],
            'data.addresses.*.types' => ['nullable', 'array'],
            'data.addresses.*.types.*' => ['string', 'max:32'],
            'data.addresses.*.isPreferred' => ['boolean'],
            'data.addresses.*.group' => ['nullable', 'string', 'max:64'],
        ];
    }
}
