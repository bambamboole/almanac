<?php

namespace App\Http\Controllers\Contacts;

use App\Actions\Contacts\CreateContactCard;
use App\Actions\Contacts\DeleteContactCard;
use App\Actions\Contacts\UpdateContactCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\DeleteContactRequest;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Http\Requests\Contacts\UpdateContactRequest;
use App\Models\Contact;
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
        $book = DavAddressBook::query()
            ->where('owner_id', $request->user()->id)
            ->findOrFail($request->integer('address_book_id'));

        $action->handle($book, $request->validated('data'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return back();
    }

    public function update(UpdateContactRequest $request, DavCard $contact, UpdateContactCard $action): RedirectResponse
    {
        $action->handle($contact, $request->validated('data'), $request->string('expected_etag')->value());

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
            ->where('owner_id', $request->user()->id)
            ->withCount('cards')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'description']);

        $contacts = Contact::query()
            ->with(['addressBook:id,display_name'])
            ->whereHas('addressBook', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->get();

        return Inertia::render('contacts/index', [
            'addressBooks' => $addressBooks,
            'contacts' => $contacts,
        ]);
    }
}
