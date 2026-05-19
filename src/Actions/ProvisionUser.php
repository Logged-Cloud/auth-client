<?php

namespace LoggedCloud\AuthClient\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use LoggedCloud\AuthClient\Concerns\ResolvesUserModel;

class ProvisionUser
{
    use ResolvesUserModel;

    /**
     * Host-app hook, run for a brand-new user before it is saved. Lets each
     * app set its own defaults (e.g. a starting balance).
     */
    protected static ?Closure $newUserCallback = null;

    /**
     * Register the new-user hook. Call this from a service provider.
     */
    public static function whenCreating(Closure $callback): void
    {
        static::$newUserCallback = $callback;
    }

    /**
     * Resolve a local user for the authenticated logged.cloud account:
     * match on auth_id, else link by email, else create. The local user
     * always mirrors auth.logged.cloud's identity and role.
     */
    public function __invoke(SocialiteUser $authUser): Model
    {
        $model = $this->userModel();
        $authIdColumn = config('auth-client.columns.auth_id');
        $roleColumn = config('auth-client.columns.role');

        $user = $model::query()->where($authIdColumn, $authUser->getId())->first()
            ?? $model::query()->where('email', $authUser->getEmail())->first()
            ?? new $model;

        $isNew = ! $user->exists;

        $user->{$authIdColumn} = $authUser->getId();
        $user->name = $authUser->getName() ?: ($user->name ?? '');
        $user->email = $authUser->getEmail();

        // SSO users are verified at the identity provider.
        if (empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        $raw = (array) $authUser->getRaw();
        if ($roleColumn && array_key_exists('role', $raw)) {
            $user->{$roleColumn} = $raw['role'];
        }

        if ($isNew) {
            // No local password — auth happens at auth.logged.cloud.
            $user->password = Hash::make(Str::random(40));

            if (static::$newUserCallback) {
                (static::$newUserCallback)($user, $authUser);
            }
        }

        $user->save();

        return $user;
    }
}
