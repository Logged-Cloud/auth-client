<?php

namespace LoggedCloud\AuthClient\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use LoggedCloud\AuthClient\Actions\ProvisionUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class AuthClientController
{
    /**
     * Send the user to auth.logged.cloud to authorize this app.
     */
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('logged-cloud')
            ->scopes(config('auth-client.scopes', ['profile']))
            ->redirect();
    }

    /**
     * Handle the OAuth callback: provision the local user and sign them in.
     */
    public function callback(ProvisionUser $provisionUser): RedirectResponse
    {
        $authUser = Socialite::driver('logged-cloud')->user();

        $user = $provisionUser($authUser);

        Auth::guard('web')->login($user, remember: true);

        return redirect()->intended(config('auth-client.redirect_after_login', '/'));
    }
}
