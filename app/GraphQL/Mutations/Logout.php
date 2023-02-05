<?php

namespace App\GraphQL\Mutations;

use Illuminate\Support\Facades\Auth;

final class Logout
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        if ($user = auth('sanctum')->user()) {
            $user->tokens()->delete();

            return true;
        }

        return false;
    }
}
