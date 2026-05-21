<?php

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use LoggedCloud\AuthClient\Tests\Fixtures\User;

it('redirects to auth.logged.cloud for authorization with PKCE', function () {
    $location = $this->get('/auth/logged-cloud/redirect')->headers->get('Location');

    expect($location)
        ->toContain('auth.logged.cloud/oauth/authorize')
        ->toContain('code_challenge')
        ->toContain('client_id=test-client');
});

it('restarts the handshake instead of 500ing when state has drifted', function () {
    // Multiple login tabs / a cleared session cookie / a bookmarked
    // callback URL all surface as Socialite throwing InvalidStateException
    // from `->user()`. Pre-fix, the controller let it bubble · the user
    // saw a generic 500. Now we catch + redirect to /redirect so they
    // get a fresh dance with a fresh state.
    $provider = mock(\Laravel\Socialite\Two\AbstractProvider::class);
    $provider->shouldReceive('user')->once()->andThrow(new \Laravel\Socialite\Two\InvalidStateException);
    Socialite::shouldReceive('driver')->with('logged-cloud')->andReturn($provider);

    $this->get('/auth/logged-cloud/callback')
        ->assertRedirect(route('logged-cloud.redirect'));

    $this->assertGuest();
});

it('provisions and signs in the user on a successful callback', function () {
    $authUser = (new SocialiteUser)
        ->setRaw(['id' => 'auth-9', 'name' => 'Sam', 'email' => 'sam@example.com', 'role' => 'admin'])
        ->map(['id' => 'auth-9', 'name' => 'Sam', 'email' => 'sam@example.com']);

    $provider = mock(AbstractProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($authUser);
    Socialite::shouldReceive('driver')->with('logged-cloud')->andReturn($provider);

    $response = $this->get('/auth/logged-cloud/callback');

    $response->assertRedirect(config('auth-client.redirect_after_login'));
    $this->assertAuthenticated();

    expect(User::where('auth_id', 'auth-9')->value('role'))->toBe('admin');
});
