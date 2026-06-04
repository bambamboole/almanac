<?php

namespace App\Http\Controllers\Users;

use App\Actions\Dav\ResetDavCredential;
use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('users/index', [
            'users' => new UserCollection(
                User::query()
                    ->with('role:id,name,permissions')
                    ->withCount('calendars', 'addressBooks')
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $createUser->handle(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            role: $request->validated('role'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return redirect()->route('users.index');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle(
            user: $user,
            name: $request->validated('name'),
            email: $request->validated('email'),
            role: $request->validated('role'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return redirect()->route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return redirect()->route('users.index');
    }

    public function resetCredential(User $user, ResetDavCredential $reset): RedirectResponse
    {
        $credential = $reset->handle($user, 'Reset by admin');

        Inertia::flash([
            'createdDavCredential' => [
                'username' => $credential['username'],
                'plainSecret' => $credential['plainSecret'],
            ],
            'toast' => ['type' => 'success', 'message' => __('DAV credential reset.')],
        ]);

        return redirect()->route('users.index');
    }
}
