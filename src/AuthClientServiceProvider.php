<?php

namespace LoggedCloud\AuthClient;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as Socialite;

class AuthClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth-client.php', 'auth-client');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/auth-client.php' => config_path('auth-client.php'),
        ], 'auth-client-config');

        $this->registerSocialiteDriver();
        $this->registerRoutes();
    }

    /**
     * Register the "logged-cloud" Socialite driver — an OAuth2 client whose
     * authorize / token / userinfo URLs point at auth.logged.cloud.
     */
    protected function registerSocialiteDriver(): void
    {
        $socialite = $this->app->make(Socialite::class);

        $socialite->extend('logged-cloud', fn () => $socialite->buildProvider(LoggedCloudProvider::class, [
            'client_id' => config('auth-client.client_id'),
            'client_secret' => config('auth-client.client_secret'),
            'redirect' => config('auth-client.redirect'),
        ]));
    }

    protected function registerRoutes(): void
    {
        if (! config('auth-client.routes.enabled', true)) {
            return;
        }

        $prefix = config('auth-client.routes.prefix', 'auth/logged-cloud');

        // Browser-facing OAuth routes need the web middleware group (session).
        Route::group([
            'prefix' => $prefix,
            'middleware' => config('auth-client.routes.middleware', ['web']),
        ], fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));

        // The webhook is a signed server-to-server POST — no session, no CSRF.
        Route::group([
            'prefix' => $prefix,
        ], fn () => $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php'));
    }
}
