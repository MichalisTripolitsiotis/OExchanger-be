<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\GraphQLException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final class ResetPassword
{
    /**
     * @param  null  $_
     * @param  array{code: string, email: string, password: string}  $args
     */
    public function __invoke($_, array $args)
    {
        $token = $args['code'];
        $email = $args['email'];
        $password = $args['password'];

        $user = User::where('email', $email)->first();

        $status = Password::reset([
            'email' => $email,
            'token' => $token,
            'password' => $password
        ], function ($user, $password) {
            $user->password = $password;
            $user->password = Hash::make($password);

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::INVALID_USER) {
            throw new GraphQLException('User not found.');
        }

        if ($status === Password::INVALID_TOKEN) {
            throw new GraphQLException('Token is invalid.');
        }

        if ($status === Password::PASSWORD_RESET) {
            return true;
        }
    }
}
