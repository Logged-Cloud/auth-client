<?php

namespace LoggedCloud\AuthClient\Concerns;

trait ResolvesUserModel
{
    /**
     * The host application's user model — the configured one, or the model
     * behind the application's default auth guard.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected function userModel(): string
    {
        if ($model = config('auth-client.user_model')) {
            return $model;
        }

        $guard = config('auth.defaults.guard', 'web');
        $provider = config("auth.guards.{$guard}.provider");

        return config("auth.providers.{$provider}.model");
    }
}
