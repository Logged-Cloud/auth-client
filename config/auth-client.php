<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identity provider
    |--------------------------------------------------------------------------
    |
    | auth.logged.cloud is the family's OAuth2 server. The credentials below
    | come from registering this app there with `php artisan register:client`.
    |
    */

    'base_url' => env('LOGGED_CLOUD_URL', 'https://auth.logged.cloud'),

    'client_id' => env('LOGGED_CLOUD_CLIENT_ID'),

    'client_secret' => env('LOGGED_CLOUD_CLIENT_SECRET'),

    'redirect' => env('LOGGED_CLOUD_REDIRECT'),

    'scopes' => ['profile'],

    /*
    |--------------------------------------------------------------------------
    | Webhook secret
    |--------------------------------------------------------------------------
    |
    | Shared secret used to verify the HMAC signature on webhooks pushed from
    | auth.logged.cloud (e.g. role.changed). Leave null to reject all webhooks.
    |
    */

    'webhook_secret' => env('LOGGED_CLOUD_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Local user model
    |--------------------------------------------------------------------------
    |
    | The host app's user model. Null falls back to the model behind the
    | application's default auth guard.
    |
    */

    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    |
    | auth_id links a local user to its auth.logged.cloud account. role mirrors
    | the centralised permission level — auth.logged.cloud owns it; set role to
    | null if the host app does not track permissions.
    |
    */

    'columns' => [
        'auth_id' => 'auth_id',
        'role' => 'role',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'auth/logged-cloud',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect after login
    |--------------------------------------------------------------------------
    */

    'redirect_after_login' => '/dashboard',

];
