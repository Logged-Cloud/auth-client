<?php

namespace LoggedCloud\AuthClient\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
