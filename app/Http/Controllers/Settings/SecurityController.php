<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Http\Resources\PasskeyCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        $canManagePasskeys = Features::canManagePasskeys();
        $passkeys = $canManagePasskeys
            ? $request->user()
                ->passkeys()
                ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                ->latest()
                ->get()
            : new Collection;
        $twoFactorEnabled = $this->boolean(false);
        $requiresConfirmation = $this->boolean(false);

        if ($canManageTwoFactor) {
            $request->ensureStateIsValid();

            $twoFactorEnabled = $this->boolean($request->user()->hasEnabledTwoFactorAuthentication());
            $requiresConfirmation = $this->boolean(Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'));
        }

        return Inertia::render('settings/security', [
            'canManageTwoFactor' => $canManageTwoFactor,
            'canManagePasskeys' => (bool) $canManagePasskeys,
            'passkeys' => new PasskeyCollection($passkeys),
            'passwordRules' => (string) Password::defaults()->toPasswordRulesString(),
            'twoFactorEnabled' => $twoFactorEnabled,
            'requiresConfirmation' => $requiresConfirmation,
        ]);
    }

    private function boolean(mixed $value): bool
    {
        return (bool) $value;
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
