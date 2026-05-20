<?php

namespace LoggedCloud\AuthClient;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class LoggedCloudProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The separator for joining OAuth scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Use the PKCE extension — auth.logged.cloud's authorization-code flow
     * expects a code challenge.
     *
     * @var bool
     */
    protected $usesPKCE = true;

    /**
     * The default scopes requested from the identity provider. Overridden
     * per-app via LOGGED_CLOUD_SCOPES; this is the fallback if no config is
     * loaded (e.g. in isolated tests).
     *
     * @var array<int, string>
     */
    protected $scopes = ['name', 'email', 'role'];

    protected function baseUrl(): string
    {
        return rtrim((string) config('auth-client.base_url'), '/');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->baseUrl().'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->baseUrl().'/oauth/token';
    }

    /**
     * Fetch the user's identity from auth.logged.cloud's userinfo endpoint.
     *
     * @param  string  $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->baseUrl().'/api/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
        ]);

        return (array) json_decode((string) $response->getBody(), true);
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }
}
