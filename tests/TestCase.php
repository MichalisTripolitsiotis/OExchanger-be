<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use MakesGraphQLRequests;
    use RefreshDatabase;

    /**
     * @param string $token
     *
     * @return array|null
     */
    protected function getHeaders($token): ?array
    {
        static $headers = null;


        $headers = ['Authorization' => 'Bearer ' . $token];

        return $headers;
    }

    /**
     * Create and return authenticated token
     *
     * @param  User  $user
     * @return string
     */
    public function createToken(User $user): string
    {
        return $user->createToken('oexchanger')->plainTextToken;
    }
}
