<?php

namespace App\Http\Controllers\Contacts;

use App\Actions\Contacts\CreateContactCard;
use App\Actions\Contacts\DeleteContactCard;
use App\Actions\Contacts\UpdateContactCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\DeleteContactRequest;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Http\Requests\Contacts\UpdateContactRequest;
use Bambamboole\LaravelDav\Dto\Contact\ContactPostalAddress;
use Bambamboole\LaravelDav\Dto\Contact\LabeledContactValue;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request, CreateContactCard $action): RedirectResponse
    {
        $book = DavAddressBook::query()->findOrFail($request->integer('address_book_id'));

        $action->handle($book, $this->contactFields($request->safe()->except('address_book_id')));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return back();
    }

    public function update(UpdateContactRequest $request, DavCard $contact, UpdateContactCard $action): RedirectResponse
    {
        $fields = $this->contactFields($request->safe()->except('expected_etag'));

        $action->handle($contact, $fields, $request->string('expected_etag')->value());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return back();
    }

    public function destroy(DeleteContactRequest $request, DavCard $contact, DeleteContactCard $action): RedirectResponse
    {
        $action->handle($contact, $request->string('expected_etag')->value());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact deleted.')]);

        return back();
    }

    public function __invoke(Request $request): Response
    {
        $addressBooks = DavAddressBook::query()
            ->whereBelongsTo($request->user())
            ->withCount('cards')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'description'])
            ->map(fn (DavAddressBook $addressBook): array => [
                'id' => $addressBook->id,
                'name' => $addressBook->display_name,
                'description' => $addressBook->description,
                'contacts_count' => $addressBook->cards_count,
            ]);

        $contacts = DavCard::query()
            ->with(['addressBook:id,display_name,user_id'])
            ->whereHas('addressBook', fn ($query) => $query->whereBelongsTo($request->user()))
            ->get()
            ->map(function (DavCard $contact): array {
                $data = $contact->data;
                $emails = collect($data->emailAddresses)->map(fn (LabeledContactValue $email): string => $email->value)->filter()->values();
                $phones = collect($data->phoneNumbers)->map(fn (LabeledContactValue $phone): string => $phone->value)->filter()->values();
                $displayName = $data->formattedName ?: ($emails->first() ?: ($phones->first() ?: 'Unnamed contact'));

                return [
                    'id' => $contact->id,
                    'address_book_id' => $contact->dav_address_book_id,
                    'address_book' => [
                        'id' => $contact->addressBook->id,
                        'name' => $contact->addressBook->display_name,
                    ],
                    'display_name' => $displayName,
                    'full_name' => $data->formattedName,
                    'given_name' => $data->givenName,
                    'family_name' => $data->familyName,
                    'organization' => $data->organization,
                    'job_title' => $data->jobTitle,
                    'nickname' => $data->nickname,
                    'note' => $data->note,
                    'etag' => $contact->etag,
                    'emails' => $emails->all(),
                    'phones' => $phones->all(),
                    'email_addresses' => array_map($this->labeledValueToArray(...), $data->emailAddresses),
                    'phone_numbers' => array_map($this->labeledValueToArray(...), $data->phoneNumbers),
                    'addresses' => array_map($this->addressToArray(...), $data->addresses),
                    'primary_email' => $emails->first(),
                    'primary_phone' => $phones->first(),
                    'updated_at' => $contact->last_modified_at->toISOString(),
                ];
            })
            ->sortBy(fn (array $contact): string => mb_strtolower((string) ($contact['full_name'] ?: $contact['family_name'] ?: $contact['display_name'])))
            ->values();

        return Inertia::render('contacts/index', [
            'addressBooks' => $addressBooks,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Map a validated request into the camelCase shape ContactData expects.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function contactFields(array $input): array
    {
        $keyMap = [
            'full_name' => 'formattedName',
            'given_name' => 'givenName',
            'family_name' => 'familyName',
            'job_title' => 'jobTitle',
        ];

        $email = $input['email'] ?? null;
        $phone = $input['phone'] ?? null;
        unset($input['email'], $input['phone']);

        $fields = [];

        foreach ($input as $key => $value) {
            $fields[$keyMap[$key] ?? $key] = $value;
        }

        if (array_key_exists('email_addresses', $fields)) {
            $fields['emailAddresses'] = $this->labeledValues($fields['email_addresses']);
        } elseif (filled($email)) {
            $fields['emailAddresses'] = [['label' => 'work', 'value' => $email, 'types' => ['INTERNET', 'WORK']]];
        }

        if (array_key_exists('phone_numbers', $fields)) {
            $fields['phoneNumbers'] = $this->labeledValues($fields['phone_numbers']);
        } elseif (filled($phone)) {
            $fields['phoneNumbers'] = [['label' => 'mobile', 'value' => $phone, 'types' => ['CELL'], 'isPreferred' => true]];
        }

        if (array_key_exists('addresses', $fields)) {
            $fields['addresses'] = $this->addresses($fields['addresses']);
        }

        unset($fields['email_addresses'], $fields['phone_numbers']);

        return $fields;
    }

    /**
     * @return array<int, array{label: ?string, value: string, types: array<int, string>, isPreferred: bool, group: ?string}>
     */
    private function labeledValues(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['value'] ?? null))
            ->map(fn (array $row): array => [
                'label' => $this->nullableString($row['label'] ?? null),
                'value' => (string) $row['value'],
                'types' => $this->stringList($row['types'] ?? []),
                'isPreferred' => (bool) ($row['is_preferred'] ?? false),
                'group' => $this->nullableString($row['group'] ?? null),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function addresses(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $row): bool => is_array($row) && collect([
                $row['po_box'] ?? null,
                $row['extended'] ?? null,
                $row['street'] ?? null,
                $row['city'] ?? null,
                $row['region'] ?? null,
                $row['postal_code'] ?? null,
                $row['country'] ?? null,
                $row['country_code'] ?? null,
            ])->contains(fn (mixed $value): bool => filled($value)))
            ->map(fn (array $row): array => [
                'label' => $this->nullableString($row['label'] ?? null),
                'poBox' => $this->nullableString($row['po_box'] ?? null),
                'extended' => $this->nullableString($row['extended'] ?? null),
                'street' => $this->nullableString($row['street'] ?? null),
                'city' => $this->nullableString($row['city'] ?? null),
                'region' => $this->nullableString($row['region'] ?? null),
                'postalCode' => $this->nullableString($row['postal_code'] ?? null),
                'country' => $this->nullableString($row['country'] ?? null),
                'countryCode' => $this->nullableString($row['country_code'] ?? null),
                'types' => $this->stringList($row['types'] ?? []),
                'isPreferred' => (bool) ($row['is_preferred'] ?? false),
                'group' => $this->nullableString($row['group'] ?? null),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{label: ?string, value: string, types: array<int, string>, is_preferred: bool, group: ?string}
     */
    private function labeledValueToArray(LabeledContactValue $value): array
    {
        return [
            'label' => $value->label,
            'value' => $value->value,
            'types' => $value->types,
            'is_preferred' => $value->isPreferred,
            'group' => $value->group,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function addressToArray(ContactPostalAddress $address): array
    {
        return [
            'label' => $address->label,
            'po_box' => $address->poBox,
            'extended' => $address->extended,
            'street' => $address->street,
            'city' => $address->city,
            'region' => $address->region,
            'postal_code' => $address->postalCode,
            'country' => $address->country,
            'country_code' => $address->countryCode,
            'types' => $address->types,
            'is_preferred' => $address->isPreferred,
            'group' => $address->group,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->filter(fn (mixed $item): bool => is_string($item) || is_numeric($item))
            ->map(fn (mixed $item): string => (string) $item)
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }
}
