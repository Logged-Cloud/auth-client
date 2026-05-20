# logged-cloud/auth-client

[![tests](https://github.com/Logged-Cloud/auth-client/actions/workflows/tests.yml/badge.svg)](https://github.com/Logged-Cloud/auth-client/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/logged-cloud/auth-client.svg)](https://packagist.org/packages/logged-cloud/auth-client)
[![License](https://img.shields.io/packagist/l/logged-cloud/auth-client.svg)](LICENSE)

A drop-in **"Continue with logged.cloud"** button for any Laravel app. Hands
identity off to [auth.logged.cloud](https://auth.logged.cloud), the family's
OAuth2 server, and signs the returning user into your app's `web` guard with
exactly the user fields you asked for, no more.

```
┌────────────┐        OAuth2 + PKCE        ┌────────────────────────┐
│  Your app  │  ◄────────────────────────► │   auth.logged.cloud    │
│            │      role.changed webhook    │ identity + permissions │
└────────────┘  ◄──────────────────────────└────────────────────────┘
```

- **Self-serve registration.** Sign up at
  [auth.logged.cloud/developer](https://auth.logged.cloud/developer), brand
  your login screen, tick the data fields you need, and copy the issued
  `.env` block straight into your app.
- **Fine-grained scopes.** Ask for `name`, `email`, `role`, any subset, or
  nothing beyond the user identifier. Userinfo gates each field.
- **One-line provisioning.** A callback action finds-or-links-or-creates the
  local user. Brand-new users get whatever defaults your app wants.
- **Instant role propagation.** auth.logged.cloud pushes a signed
  `role.changed` webhook the moment an admin changes someone's permission
  level — your local mirror updates without waiting for the next login.
- **Sits next to Breeze.** SSO is additive: your existing email/password
  login keeps working unchanged.

---

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- `laravel/socialite` (pulled in as a dependency)
- A `users` table with a nullable, unique `auth_id` string column. If your
  app tracks permissions, a `role` column too.

---

## 1. Register your app

Sign in at **[auth.logged.cloud/developer](https://auth.logged.cloud/developer)**
and click **Register an app**. Fill in:

| Field | Notes |
|-------|-------|
| **App name** | What users see on the consent screen. |
| **Website** | The public URL of your app. |
| **OAuth redirect URL** | `https://YOUR-APP.example/auth/logged-cloud/callback` |
| **Logo URL** *(optional)* | Displayed on the consent screen. |
| **Brand colours** | Used to skin the logged.cloud login screen when users arrive from your app. |
| **User data your app receives** | The user identifier is always shared. Tick **Name**, **Email** and/or **Role** depending on what your app actually needs. |

Submit, then wait for an admin to approve the app. Once approved, your
`/developer` page surfaces a copy-pasteable `.env` block:

```dotenv
LOGGED_CLOUD_URL=https://auth.logged.cloud
LOGGED_CLOUD_CLIENT_ID=01HZ...
LOGGED_CLOUD_CLIENT_SECRET=secret-issued-once-grab-it-now
LOGGED_CLOUD_REDIRECT=https://your-app.example/auth/logged-cloud/callback
LOGGED_CLOUD_SCOPES=name,email,role
LOGGED_CLOUD_WEBHOOK_SECRET=hmac-secret-for-role-changed
```

> The client secret is shown to the owner only. Treat it like a password.

---

## 2. Install the package

```bash
composer require logged-cloud/auth-client
php artisan vendor:publish --tag=auth-client-config
```

Add the env block from the developer portal to your `.env`, then add the
schema columns to `users`:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('auth_id')->nullable()->unique()->after('id');
    $table->string('role')->nullable()->after('email'); // skip if you don't track roles
});
```

---

## 3. Add the login button

The package registers three routes under `auth/logged-cloud`:

| Method | Path | Purpose |
|--------|------|---------|
| `GET`  | `/auth/logged-cloud/redirect`  | Kick off the OAuth flow |
| `GET`  | `/auth/logged-cloud/callback`  | Provision + sign in the user |
| `POST` | `/auth/logged-cloud/webhook`   | Receive signed events (`role.changed`) |

Drop a button into your login view:

```blade
<a href="{{ route('logged-cloud.redirect') }}"
   class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white">
    Continue with logged.cloud
</a>
```

That's it. Click the button, complete the flow on auth.logged.cloud, and
you're logged into your app's `web` guard.

---

## How provisioning works

On a successful callback, `LoggedCloud\AuthClient\Actions\ProvisionUser`
resolves the local user in this order:

1. **Match on `auth_id`** — already-linked user, refresh their identity.
2. **Match on `email`** — an existing local account; backfill `auth_id` and
   verify the email.
3. **Create** a brand-new local user mirroring `name`, `email` and `role`.

`role` is overwritten from auth.logged.cloud on every login, because
auth.logged.cloud is the single source of truth for permissions across the
family.

### Defaults for brand-new SSO users

Anything your app needs to seed on a *new* user — starter credits, default
notification settings, a profile row in another table — lives in a
`ProvisionUser::whenCreating` hook. Register it from a service provider:

```php
use LoggedCloud\AuthClient\Actions\ProvisionUser;

public function boot(): void
{
    ProvisionUser::whenCreating(function ($user, $authUser) {
        $user->credits = 100;
        $user->settings()->create(['theme' => 'dark']);
    });
}
```

The hook only fires when a brand-new local user is created. Existing users
linking by email pass straight through.

---

## Role propagation

`role` syncs on every login. For *instant* propagation — an admin demotes a
user, the change should take effect everywhere immediately —
auth.logged.cloud also pushes an HMAC-signed `role.changed` webhook to the
URL it derives from your redirect host:

```
POST https://your-app.example/auth/logged-cloud/webhook
X-LoggedCloud-Signature: sha256=...
{ "event": "role.changed", "auth_id": "01HZ...", "role": "admin" }
```

The package verifies the signature against `LOGGED_CLOUD_WEBHOOK_SECRET`
and updates the local user. Invalid signatures are rejected. If the secret
is unset, every webhook is rejected.

---

## Configuration

Publish the config (step 2) and edit `config/auth-client.php` to override
defaults. The most useful keys:

| Key | Default | Notes |
|-----|---------|-------|
| `base_url` | `https://auth.logged.cloud` | The IdP root. |
| `scopes` | `LOGGED_CLOUD_SCOPES` env, comma-separated | Must match (or be a subset of) the scopes ticked on the developer portal. |
| `columns.auth_id` | `auth_id` | Local column linking to logged.cloud. |
| `columns.role` | `role` | Set to `null` if your app does not track roles. |
| `user_model` | `null` (default web guard) | Override if you use a non-standard user model. |
| `routes.prefix` | `auth/logged-cloud` | The mount point. |
| `routes.middleware` | `['web']` | The middleware stack. |
| `redirect_after_login` | `/dashboard` | Where SSO drops the user after sign-in. |

---

## Testing

```bash
composer install
composer test
```

The package ships with a Pest suite covering the auth flow, all three
provisioning paths, and the webhook receiver (signed, tampered, and
unrecognised event). CI runs on PHP 8.2 / 8.3 / 8.4 against lowest and
stable Composer constraints.

---

## License

MIT. See [LICENSE](LICENSE).
