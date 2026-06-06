<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreAddressBookRequest;
use App\Http\Requests\Contacts\UpdateAddressBookRequest;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AddressBookManagementController extends Controller
{
    public function store(StoreAddressBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->createDavAddressBook([
            'uri' => Str::slug($data['display_name']) ?: (string) Str::uuid(),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'sync_token' => 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address book created.')]);

        return back();
    }

    public function update(UpdateAddressBookRequest $request, DavAddressBook $addressBook): RedirectResponse
    {
        $addressBook->updateDavProperties($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address book updated.')]);

        return back();
    }

    public function destroy(DavAddressBook $addressBook): RedirectResponse
    {
        $this->authorize('delete', $addressBook);

        $addressBook->deleteDavAddressBook();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address book deleted.')]);

        return back();
    }
}
