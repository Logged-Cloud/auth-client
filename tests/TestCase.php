<?php

namespace LoggedCloud\AuthClient\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\SocialiteServiceProvider;
use LoggedCloud\AuthClient\AuthClientServiceProvider;
use LoggedCloud\AuthClient\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SocialiteServiceProvider::class,
            AuthClientServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('auth-client.base_url', 'https://auth.logged.cloud');
        $app['config']->set('auth-client.client_id', 'test-client');
        $app['config']->set('auth-client.client_secret', 'test-secret');
        $app['config']->set('auth-client.redirect', 'https://app.test/auth/logged-cloud/callback');
        $app['config']->set('auth-client.webhook_secret', 'test-webhook-secret');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('auth_id')->nullable()->unique();
            $table->string('role')->default('user');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
