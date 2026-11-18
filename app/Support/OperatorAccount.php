<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OperatorAccount
{
    public static function user(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'operator@northwind.example'],
            [
                'name' => 'Ticket Desk Operator',
                'password' => Hash::make(str()->random(32)),
            ]
        );
    }
}
