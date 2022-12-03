<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;

final class VerifyEmail
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $decodedToken = json_decode(base64_decode($args['token']));
        $email = decrypt($decodedToken->hash);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        Auth::setUser($user);

        return true;
    }
}
