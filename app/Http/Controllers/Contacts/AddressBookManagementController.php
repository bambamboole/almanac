<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreAddressBookRequest;
use App\Http\Requests\Contacts\UpdateAddressBookRequest;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Parsing\CollectionUri;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AddressBookManagementController extends Controller
{
    public function store(StoreAddressBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DavAddressBook::query()->create([
            'user_id' => $request->user()->id,
            'uri' => CollectionUri::fromDisplayName($data['display_name']),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'sync_token' => 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address book created.')]);

        return back();
    }

    public function update(UpdateAddressBookRequest $request, DavAddressBook $addressBook): RedirectResponse
    {
        $addressBook->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address book updated.')]);

        return back();
    }

    public function destroy(DavAddressBook $addressBook): RedirectResponse
    {
        $this->authorize('delete', $addressBook);

        $addressBook->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address book deleted.')]);

        return back();
    }
}
