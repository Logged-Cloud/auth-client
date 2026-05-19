<?php

use LoggedCloud\AuthClient\Tests\Fixtures\User;

function sign(string $body): string
{
    return hash_hmac('sha256', $body, 'test-webhook-secret');
}

function postWebhook($test, string $body, string $signature)
{
    return $test->call('POST', '/auth/logged-cloud/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_LOGGEDCLOUD_SIGNATURE' => $signature,
    ], $body);
}

it('applies a role change from a correctly signed webhook', function () {
    $user = User::create([
        'name' => 'Jo', 'email' => 'jo@example.com', 'password' => 'x',
        'auth_id' => 'auth-5', 'role' => 'user',
    ]);

    $body = json_encode(['event' => 'role.changed', 'auth_id' => 'auth-5', 'role' => 'admin']);

    postWebhook($this, $body, sign($body))->assertOk();

    expect($user->fresh()->role)->toBe('admin');
});

it('rejects a webhook with an invalid signature', function () {
    $user = User::create([
        'name' => 'Jo', 'email' => 'jo@example.com', 'password' => 'x',
        'auth_id' => 'auth-5', 'role' => 'user',
    ]);

    $body = json_encode(['event' => 'role.changed', 'auth_id' => 'auth-5', 'role' => 'admin']);

    postWebhook($this, $body, 'not-the-signature')->assertForbidden();

    expect($user->fresh()->role)->toBe('user');
});

it('ignores events other than role.changed', function () {
    $body = json_encode(['event' => 'something.else']);

    postWebhook($this, $body, sign($body))->assertOk();
});
