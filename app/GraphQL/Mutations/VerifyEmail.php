<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Carbon\Carbon;
use Exception;
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
        $expiration = decrypt($decodedToken->expiration);
        $email = decrypt($decodedToken->hash);

        if (Carbon::parse($expiration) < now()) {
            throw new Exception('Token expired.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new Exception('User not found.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        Auth::setUser($user);

        return true;
    }
}
