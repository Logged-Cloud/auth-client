# logged-cloud/auth-client

OAuth2 client for the **logged.cloud** family identity provider
([auth.logged.cloud](https://auth.logged.cloud)). Drop it into any family app
to get a "Continue with logged.cloud" login, seamless user provisioning, and
instant role propagation.

It is the client half of the family SSO: auth.logged.cloud is the OAuth2
server and the single source of truth for a user's identity and permission
level; this package consumes it.

## Install

```bash
composer require logged-cloud/auth-client
php artisan vendor:publish --tag=auth-client-config
```

Register the app on auth.logged.cloud to get its credentials:

```bash
# on the auth.logged.cloud server
php artisan register:client "My App" "https://my-app.test/auth/logged-cloud/callback"
```

Then set the app's `.env`:

```dotenv
LOGGED_CLOUD_URL=https://auth.logged.cloud
LOGGED_CLOUD_CLIENT_ID=...
LOGGED_CLOUD_CLIENT_SECRET=...
LOGGED_CLOUD_REDIRECT=https://my-app.test/auth/logged-cloud/callback
LOGGED_CLOUD_WEBHOOK_SECRET=...
```

The host app's `users` table needs an `auth_id` column (string, nullable,
unique) linking a local user to its logged.cloud account, and — if it tracks
permissions — a `role` column that mirrors auth.logged.cloud.

## Usage

The package registers three routes under `auth/logged-cloud`:

| Route | Purpose |
|-------|---------|
| `GET /auth/logged-cloud/redirect` | Start the OAuth flow |
| `GET /auth/logged-cloud/callback` | Provision the user and sign them in |
| `POST /auth/logged-cloud/webhook` | Receive signed events (e.g. `role.changed`) |

Add a button to your login view:

```blade
<a href="{{ route('logged-cloud.redirect') }}">Continue with logged.cloud</a>
```

## Provisioning

On callback, `ProvisionUser` resolves the local user: it matches on `auth_id`,
else links an existing row by email (backfilling `auth_id`), else creates one.
The local user always mirrors auth.logged.cloud's name, email and role.

Set host-app defaults for brand-new users from a service provider:

```php
use LoggedCloud\AuthClient\Actions\ProvisionUser;

ProvisionUser::whenCreating(function ($user, $authUser) {
    $user->credits = 100;
});
```

## Role propagation

`role` is synced on every login. For instant propagation, auth.logged.cloud
also pushes an HMAC-signed `role.changed` webhook — the package verifies the
signature against `LOGGED_CLOUD_WEBHOOK_SECRET` and updates the local user.

## Testing

```bash
composer install
composer test
```
