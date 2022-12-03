<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

final class Register
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        // Just a second check to ensure!
        if ($args['password'] != $args['password_confirmation']) {
            return [
                'message' => 'Passwords do not match.'
            ];
        }

        $input = collect($args)->except('password_confirmation')->toArray();
        $input['password'] = Hash::make($input['password']);

        $createUser = User::create($input);

        event(new Registered($createUser));

        return [
            'message' => 'Account created successfully. An email sent to your account.',
        ];
    }
}
