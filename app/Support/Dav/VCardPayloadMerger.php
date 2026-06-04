<?php

namespace App\Support\Dav;

use Bambamboole\LaravelDav\Models\DavCard;
use Sabre\VObject\Document;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class VCardPayloadMerger
{
    public function merge(string $existingPayload, DavCard $card): string
    {
        $vCard = Reader::read($existingPayload);

        try {
            $this->setOrRemove($vCard, 'FN', $card->full_name);

            unset($vCard->N);
            $vCard->add('N', [
                $card->family_name ?? '',
                $card->given_name ?? '',
                $card->middle_name ?? '',
                $card->name_prefix ?? '',
                $card->name_suffix ?? '',
            ]);

            $this->setOrRemove($vCard, 'NICKNAME', $card->nickname);
            $this->setOrRemove($vCard, 'TITLE', $card->job_title);
            $this->setOrRemove($vCard, 'NOTE', $card->note);

            unset($vCard->ORG);
            if (! empty($card->organization)) {
                $vCard->add('ORG', array_values(array_filter(
                    [$card->organization, $card->department],
                    fn (?string $v): bool => filled($v),
                )));
            }

            $this->setPrimaryValue($vCard, 'EMAIL', $card->email_addresses->first()?->value, ['TYPE' => 'INTERNET']);
            $this->setPrimaryValue($vCard, 'TEL', $card->phone_numbers->first()?->value, ['TYPE' => 'CELL']);

            return $vCard->serialize();
        } finally {
            $vCard->destroy();
        }
    }

    /**
     * Overwrite the FIRST property with the given name, preserving any
     * additional properties of the same name and adding one when none exists.
     *
     * When the value is empty the existing vCard properties are left untouched:
     * an empty structured collection means "no primary value was supplied",
     * which must not clobber raw payload data owned by a DAV client.
     *
     * @param  array<string, mixed>  $defaultParameters
     */
    private function setPrimaryValue(Document $vCard, string $name, ?string $value, array $defaultParameters): void
    {
        if (empty($value)) {
            return;
        }

        $existing = $vCard->select($name);
        $first = $existing[array_key_first($existing)] ?? null;

        if ($first instanceof Property) {
            $first->setValue($value);

            return;
        }

        $vCard->add($name, $value, $defaultParameters);
    }

    private function setOrRemove(Document $vCard, string $name, ?string $value): void
    {
        unset($vCard->{$name});
        if (! empty($value)) {
            $vCard->add($name, $value);
        }
    }
}
