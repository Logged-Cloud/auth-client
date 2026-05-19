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
