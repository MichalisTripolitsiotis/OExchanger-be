<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\GraphQLException;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;

final class VerifyEmail
{
    /**
     * @param  null  $_
     * @param  array{token: string}  $args
     */
    public function __invoke($_, array $args)
    {
        $decodedToken = json_decode(base64_decode($args['code']));
        $expiration = decrypt($decodedToken->expiration);
        $email = decrypt($decodedToken->hash);

        if (Carbon::parse($expiration) < now()) {
            throw new GraphQLException('Token expired. Try to register again later.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new GraphQLException('User not found.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        Auth::setUser($user);

        return true;
    }
}
