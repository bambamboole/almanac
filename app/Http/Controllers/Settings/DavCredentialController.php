<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Dav\CreateDavCredential;
use App\Actions\Dav\RevokeDavCredential;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CreateDavCredentialRequest;
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
        return Inertia::render('settings/dav', [
            'credentials' => DavCredential::query()
                ->whereBelongsTo($request->user())
                ->select(['id', 'name', 'username', 'created_at', 'last_used_at'])
                ->latest()
                ->get()
                ->map(fn (DavCredential $credential): array => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'username' => $credential->username,
                    'created_at_diff' => $credential->created_at->diffForHumans(),
                    'last_used_at_diff' => $credential->last_used_at?->diffForHumans(),
                ])
                ->values()
                ->all(),
            'davUrl' => url('/dav/'),
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
        abort_unless($credential->user_id === $request->user()->id, 404);

        $revoke->handle($credential);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('DAV credential revoked.')]);

        return redirect()->route('settings.dav.edit');
    }
}
