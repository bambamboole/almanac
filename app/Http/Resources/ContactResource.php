<?php

namespace App\Http\Resources;

use App\Models\Contact;
use Bambamboole\LaravelDav\Dto\Contact\ContactDate;
use Bambamboole\LaravelDav\Dto\Contact\ContactEmailAddress;
use Bambamboole\LaravelDav\Dto\Contact\ContactPhoneNumber;
use Bambamboole\LaravelDav\Dto\Contact\ContactPostalAddress;
use Bambamboole\LaravelDav\Dto\ContactData;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contact
 */
class ContactResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: int,
     *     dav_address_book_id: int,
     *     uri: string,
     *     etag: string,
     *     last_modified_at: string,
     *     data: array{
     *         uid: string|null,
     *         formattedName: string|null,
     *         givenName: string|null,
     *         familyName: string|null,
     *         organization: string|null,
     *         contactType: string,
     *         birthday: array{label: string|null, year: int|null, month: int|null, day: int|null, calendar: string|null, rawValue: string|null, group: string|null}|null,
     *         emailAddresses: array<int, array{label: string|null, value: string, types: array<int, string>, isPreferred: bool, group: string|null}>,
     *         phoneNumbers: array<int, array{label: string|null, value: string, types: array<int, string>, isPreferred: bool, group: string|null}>,
     *         addresses: array<int, array{label: string|null, poBox: string|null, extended: string|null, street: string|null, city: string|null, region: string|null, postalCode: string|null, country: string|null, countryCode: string|null, types: array<int, string>, isPreferred: bool, group: string|null}>,
     *         namePrefix: string|null,
     *         middleName: string|null,
     *         nameSuffix: string|null,
     *         nickname: string|null,
     *         jobTitle: string|null,
     *         department: string|null,
     *         note: string|null
     *     },
     *     address_book: array{id: int, display_name: string}
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dav_address_book_id' => $this->dav_address_book_id,
            'uri' => $this->uri,
            'etag' => $this->etag,
            'last_modified_at' => $this->toIsoString($this->last_modified_at),
            'data' => $this->contactData($this->data),
            'address_book' => [
                'id' => $this->addressBook->id,
                'display_name' => $this->addressBook->display_name,
            ],
        ];
    }

    /**
     * @return array{
     *     uid: string|null,
     *     formattedName: string|null,
     *     givenName: string|null,
     *     familyName: string|null,
     *     organization: string|null,
     *     contactType: string,
     *     birthday: array{label: string|null, year: int|null, month: int|null, day: int|null, calendar: string|null, rawValue: string|null, group: string|null}|null,
     *     emailAddresses: array<int, array{label: string|null, value: string, types: array<int, string>, isPreferred: bool, group: string|null}>,
     *     phoneNumbers: array<int, array{label: string|null, value: string, types: array<int, string>, isPreferred: bool, group: string|null}>,
     *     addresses: array<int, array{label: string|null, poBox: string|null, extended: string|null, street: string|null, city: string|null, region: string|null, postalCode: string|null, country: string|null, countryCode: string|null, types: array<int, string>, isPreferred: bool, group: string|null}>,
     *     namePrefix: string|null,
     *     middleName: string|null,
     *     nameSuffix: string|null,
     *     nickname: string|null,
     *     jobTitle: string|null,
     *     department: string|null,
     *     note: string|null
     * }
     */
    private function contactData(ContactData $data): array
    {
        return [
            'uid' => $data->uid,
            'formattedName' => $data->formattedName,
            'givenName' => $data->givenName,
            'familyName' => $data->familyName,
            'organization' => $data->organization,
            'contactType' => $data->contactType,
            'birthday' => $this->contactDate($data->birthday),
            'emailAddresses' => $this->labeledValues($data->emailAddresses),
            'phoneNumbers' => $this->labeledValues($data->phoneNumbers),
            'addresses' => $this->postalAddresses($data->addresses),
            'namePrefix' => $data->namePrefix,
            'middleName' => $data->middleName,
            'nameSuffix' => $data->nameSuffix,
            'nickname' => $data->nickname,
            'jobTitle' => $data->jobTitle,
            'department' => $data->department,
            'note' => $data->note,
        ];
    }

    /**
     * @return array{label: string|null, year: int|null, month: int|null, day: int|null, calendar: string|null, rawValue: string|null, group: string|null}|null
     */
    private function contactDate(?ContactDate $date): ?array
    {
        return $date?->toArray();
    }

    /**
     * @param  array<int, ContactEmailAddress|ContactPhoneNumber>  $values
     * @return array<int, array{label: string|null, value: string, types: array<int, string>, isPreferred: bool, group: string|null}>
     */
    private function labeledValues(array $values): array
    {
        return array_map(
            fn (ContactEmailAddress|ContactPhoneNumber $value): array => $value->toArray(),
            $values,
        );
    }

    /**
     * @param  array<int, ContactPostalAddress>  $addresses
     * @return array<int, array{label: string|null, poBox: string|null, extended: string|null, street: string|null, city: string|null, region: string|null, postalCode: string|null, country: string|null, countryCode: string|null, types: array<int, string>, isPreferred: bool, group: string|null}>
     */
    private function postalAddresses(array $addresses): array
    {
        return array_map(
            fn (ContactPostalAddress $address): array => $address->toArray(),
            $addresses,
        );
    }

    private function toIsoString(CarbonInterface $date): string
    {
        return $date->toISOString();
    }
}
