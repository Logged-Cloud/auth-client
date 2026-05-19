<?php

use Laravel\Socialite\Two\User as SocialiteUser;
use LoggedCloud\AuthClient\Actions\ProvisionUser;
use LoggedCloud\AuthClient\Tests\Fixtures\User;

function authUser(array $overrides = []): SocialiteUser
{
    $data = array_merge([
        'id' => 'auth-1',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'user',
    ], $overrides);

    return (new SocialiteUser)
        ->setRaw($data)
        ->map(['id' => $data['id'], 'name' => $data['name'], 'email' => $data['email']]);
}

afterEach(fn () => ProvisionUser::whenCreating(fn () => null));

it('creates a new local user mirroring the logged.cloud account', function () {
    $user = (new ProvisionUser)(authUser(['role' => 'admin']));

    expect(User::count())->toBe(1)
        ->and($user->exists)->toBeTrue()
        ->and($user->auth_id)->toBe('auth-1')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->role)->toBe('admin')
        ->and($user->email_verified_at)->not->toBeNull();
});

it('links an existing user by email and backfills auth_id', function () {
    $existing = User::create([
        'name' => 'Old Name', 'email' => 'jane@example.com', 'password' => 'x', 'role' => 'user',
    ]);

    $user = (new ProvisionUser)(authUser(['role' => 'admin']));

    expect(User::count())->toBe(1)
        ->and($user->id)->toBe($existing->id)
        ->and($user->auth_id)->toBe('auth-1')
        ->and($user->role)->toBe('admin');
});

it('finds an existing user by auth_id and refreshes its identity', function () {
    $existing = User::create([
        'name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'x',
        'auth_id' => 'auth-1', 'role' => 'user',
    ]);

    $user = (new ProvisionUser)(authUser(['email' => 'jane-new@example.com', 'role' => 'admin']));

    expect(User::count())->toBe(1)
        ->and($user->id)->toBe($existing->id)
        ->and($user->email)->toBe('jane-new@example.com')
        ->and($user->role)->toBe('admin');
});

it('runs the host app new-user hook', function () {
    ProvisionUser::whenCreating(function (User $user) {
        $user->role = 'member';
    });

    $user = (new ProvisionUser)(authUser());

    expect($user->role)->toBe('member');
});
