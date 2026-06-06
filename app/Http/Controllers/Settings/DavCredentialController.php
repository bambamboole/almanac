<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Dav\CreateDavCredential;
use App\Actions\Dav\RevokeDavCredential;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CreateDavCredentialRequest;
use App\Http\Resources\DavCredentialCollection;
use Bambamboole\LaravelDav\Models\DavCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DavCredentialController extends Controller
{
    /**
     * Show the user's DAV credential settings page.
     */
    public function index(Request $request): Response
    {
        $credentials = DavCredential::query()
            ->where('owner_id', $request->user()->id)
            ->select(['id', 'name', 'username', 'created_at', 'last_used_at'])
            ->latest()
            ->get();

        return Inertia::render('settings/dav', [
            'credentials' => new DavCredentialCollection($credentials),
            'davUrl' => (string) url('/dav/'),
        ]);
    }

    /**
     * Create a new DAV credential.
     */
    public function store(CreateDavCredentialRequest $request, CreateDavCredential $create): RedirectResponse
    {
        $createdCredential = $create->handle($request->user(), $request->validated('name'));

        Inertia::flash([
            'createdDavCredential' => [
                'username' => $createdCredential['username'],
                'plainSecret' => $createdCredential['plainSecret'],
            ],
            'toast' => ['type' => 'success', 'message' => __('DAV credential created.')],
        ]);

        return redirect()->route('settings.dav.edit');
    }

    /**
     * Revoke an existing DAV credential.
     */
    public function destroy(Request $request, DavCredential $credential, RevokeDavCredential $revoke): RedirectResponse
    {
        abort_unless($credential->owner_id === $request->user()->id, 404);

        $revoke->handle($credential);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('DAV credential revoked.')]);

        return redirect()->route('settings.dav.edit');
    }
}
