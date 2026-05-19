<?php

use Illuminate\Support\Facades\Route;
use LoggedCloud\AuthClient\Http\Controllers\AuthClientController;

/*
| Browser-facing OAuth routes. Registered under the configured prefix
| (default: auth/logged-cloud) with the web middleware group.
*/

Route::get('redirect', [AuthClientController::class, 'redirect'])
    ->name('logged-cloud.redirect');

Route::get('callback', [AuthClientController::class, 'callback'])
    ->name('logged-cloud.callback');
