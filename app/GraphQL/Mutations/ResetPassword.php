<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final class ResetPassword
{
    /**
     * @param  null  $_
     * @param  array{token: string, email: string, password: string}  $args
     */
    public function __invoke($_, array $args)
    {
        $token = $args['token'];
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
            abort(403, 'User not found');
        }

        if ($status === Password::INVALID_TOKEN) {
            abort(401, 'Provided token is invalid');
        }

        if ($status === Password::PASSWORD_RESET) {
            return $user->createToken('oexchange')->plainTextToken;
        }
    }
}
