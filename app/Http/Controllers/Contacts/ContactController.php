<?php

namespace App\Http\Controllers\Contacts;

use App\Actions\Contacts\CreateContactCard;
use App\Actions\Contacts\DeleteContactCard;
use App\Actions\Contacts\UpdateContactCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\DeleteContactRequest;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Http\Requests\Contacts\UpdateContactRequest;
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
            ->orderBy('full_name')
            ->orderBy('family_name')
            ->get([
                'id',
                'dav_address_book_id',
                'full_name',
                'given_name',
                'family_name',
                'organization',
                'job_title',
                'nickname',
                'note',
                'emails',
                'phones',
                'email_addresses',
                'phone_numbers',
                'addresses',
                'etag',
                'last_modified_at',
            ])
            ->map(function (DavCard $contact): array {
                $emails = collect($contact->emails ?? [])->filter()->values();
                $phones = collect($contact->phones ?? [])->filter()->values();
                $displayName = $contact->full_name ?: ($emails->first() ?: ($phones->first() ?: 'Unnamed contact'));

                return [
                    'id' => $contact->id,
                    'address_book_id' => $contact->dav_address_book_id,
                    'address_book' => [
                        'id' => $contact->addressBook->id,
                        'name' => $contact->addressBook->display_name,
                    ],
                    'display_name' => $displayName,
                    'full_name' => $contact->full_name,
                    'given_name' => $contact->given_name,
                    'family_name' => $contact->family_name,
                    'organization' => $contact->organization,
                    'job_title' => $contact->job_title,
                    'nickname' => $contact->nickname,
                    'note' => $contact->note,
                    'etag' => $contact->etag,
                    'emails' => $emails->all(),
                    'phones' => $phones->all(),
                    'email_addresses' => $contact->email_addresses->toArray(),
                    'phone_numbers' => $contact->phone_numbers->toArray(),
                    'addresses' => $contact->addresses->toArray(),
                    'primary_email' => $emails->first(),
                    'primary_phone' => $phones->first(),
                    'updated_at' => $contact->last_modified_at->toISOString(),
                ];
            });

        return Inertia::render('contacts/index', [
            'addressBooks' => $addressBooks,
            'contacts' => $contacts,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function contactFields(array $input): array
    {
        $email = $input['email'] ?? null;
        $phone = $input['phone'] ?? null;
        unset($input['email'], $input['phone']);

        if (array_key_exists('email_addresses', $input)) {
            $input['email_addresses'] = $this->emailAddresses($input['email_addresses']);
            $input['emails'] = collect($input['email_addresses'])->pluck('value')->all();
        } elseif (filled($email)) {
            $input['emails'] = [$email];
            $input['email_addresses'] = [['label' => 'work', 'value' => $email, 'types' => ['INTERNET', 'WORK']]];
        }

        if (array_key_exists('phone_numbers', $input)) {
            $input['phone_numbers'] = $this->phoneNumbers($input['phone_numbers']);
            $input['phones'] = collect($input['phone_numbers'])->pluck('value')->all();
        } elseif (filled($phone)) {
            $input['phones'] = [$phone];
            $input['phone_numbers'] = [['label' => 'mobile', 'value' => $phone, 'types' => ['CELL'], 'is_preferred' => true]];
        }

        if (array_key_exists('addresses', $input)) {
            $input['addresses'] = $this->addresses($input['addresses']);
        }

        return $input;
    }

    /**
     * @return array<int, array{label: ?string, value: string, types: array<int, string>, is_preferred: bool, group: ?string}>
     */
    private function emailAddresses(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['value'] ?? null))
            ->map(fn (array $row): array => [
                'label' => $this->nullableString($row['label'] ?? null),
                'value' => (string) $row['value'],
                'types' => $this->stringList($row['types'] ?? []),
                'is_preferred' => (bool) ($row['is_preferred'] ?? false),
                'group' => $this->nullableString($row['group'] ?? null),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: ?string, value: string, types: array<int, string>, is_preferred: bool, group: ?string}>
     */
    private function phoneNumbers(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['value'] ?? null))
            ->map(fn (array $row): array => [
                'label' => $this->nullableString($row['label'] ?? null),
                'value' => (string) $row['value'],
                'types' => $this->stringList($row['types'] ?? []),
                'is_preferred' => (bool) ($row['is_preferred'] ?? false),
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
                'po_box' => $this->nullableString($row['po_box'] ?? null),
                'extended' => $this->nullableString($row['extended'] ?? null),
                'street' => $this->nullableString($row['street'] ?? null),
                'city' => $this->nullableString($row['city'] ?? null),
                'region' => $this->nullableString($row['region'] ?? null),
                'postal_code' => $this->nullableString($row['postal_code'] ?? null),
                'country' => $this->nullableString($row['country'] ?? null),
                'country_code' => $this->nullableString($row['country_code'] ?? null),
                'types' => $this->stringList($row['types'] ?? []),
                'is_preferred' => (bool) ($row['is_preferred'] ?? false),
                'group' => $this->nullableString($row['group'] ?? null),
            ])
            ->values()
            ->all();
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
