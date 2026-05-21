<?php

namespace LoggedCloud\AuthClient\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
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
     *
     * Stale `state` query parameter recovery · the user lands here after a
     * round-trip through auth.logged.cloud. The `state` we sent in the
     * /redirect step lives in the session; the one we get back lives in
     * the query string. They drift apart when:
     *   - the user opened multiple login tabs (newer state overwrote older)
     *   - the session cookie was cleared between redirect and callback
     *   - the user is following a bookmarked /auth/logged-cloud/callback URL
     *
     * Socialite raises InvalidStateException in those cases. The old
     * behaviour was a generic 500. We now catch the exception and bounce
     * the user back to /auth/logged-cloud/redirect so they get a fresh
     * dance with a fresh state · no harm done, no error page.
     */
    public function callback(ProvisionUser $provisionUser): RedirectResponse
    {
        try {
            $authUser = Socialite::driver('logged-cloud')->user();
        } catch (InvalidStateException $e) {
            Log::info('logged-cloud OAuth state drift · restarting the handshake', [
                'route' => request()->fullUrl(),
            ]);
            return redirect()->route('logged-cloud.redirect');
        }

        $user = $provisionUser($authUser);

        Auth::guard('web')->login($user, remember: true);

        return redirect()->intended(config('auth-client.redirect_after_login', '/'));
    }
}
