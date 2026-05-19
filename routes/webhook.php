<?php

use Illuminate\Support\Facades\Route;
use LoggedCloud\AuthClient\Http\Controllers\WebhookController;

/*
| The webhook endpoint. A server-to-server POST from auth.logged.cloud, so it
| runs outside the web middleware group — no session, no CSRF token. The
| controller verifies an HMAC signature instead.
*/

Route::post('webhook', WebhookController::class)
    ->name('logged-cloud.webhook');
