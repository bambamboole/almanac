<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ContactExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return $this->responseFor($request->user()->addressBooks()->with('cards')->get()->all(), 'contacts.vcf');
    }

    public function show(Request $request, DavAddressBook $addressBook): Response
    {
        $this->authorize('view', $addressBook);

        return $this->responseFor([$addressBook->load('cards')], Str::slug($addressBook->display_name).'.vcf');
    }

    public function card(Request $request, DavCard $contact): Response
    {
        $this->authorize('view', $contact);

        return response(trim($contact->card_data), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filenameFor($contact).'"',
        ]);
    }

    /**
     * @param  array<int, DavAddressBook>  $addressBooks
     */
    private function responseFor(array $addressBooks, string $filename): Response
    {
        $cards = [];

        foreach ($addressBooks as $addressBook) {
            foreach ($addressBook->cards as $card) {
                /** @var DavCard $card */
                $cards[] = trim($card->card_data);
            }
        }

        $body = implode("\r\n", $cards);

        return response($body, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function filenameFor(DavCard $contact): string
    {
        $data = $contact->data;
        $name = filled($data->formattedName) ? $data->formattedName : ($data->uid ?? 'contact');

        return Str::slug($name).'.vcf';
    }
}
