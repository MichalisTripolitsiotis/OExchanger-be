<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected static $token = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => Hash::make('password')]);
    }

    /**
     * Ensure that the user can be authenticated with
     * the correct credentials.
     *
     * @return void
     */
    public function test_user_login_successfully(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation Login($email: String! $password: String!) {
                login(email: $email password: $password)
            }
        ';

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => [
                'email' => $this->user->email,
                'password' => 'password'
            ]
        ]);

        $data = json_decode($response->getContent(), true);
        static::$token = $token = $data['data']['login'];
        $this->assertNotEquals(null, $token);
    }

    /**
     * Ensure that the user cannot be authenticated with
     * wrong credentials.
     *
     * @return void
     */
    public function test_user_cannot_login_with_wrong_credentials(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation Login($email: String! $password: String!) {
                login(email: $email password: $password)
            }
        ';

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => [
                'email' => $this->user->email,
                'password' => 'wrongPassword'
            ]
        ]);

        $response->assertGraphQLErrorMessage('The credentials are incorrect.');
    }

    /**
     * Ensure that the authenticated user can access the 'me'
     * endpoint.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create();

        $query =
            /** @lang GraphQL */
            'query Me {
                me {
                    id
                    name
                    email
                }
            }';


        $response = $this->postGraphQL(
            ['query' => $query],
            $this->getHeaders($this->createToken($user))
        );

        $response->assertJson([
            'data' => [
                'me' => [
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    /**
     * Ensure that the unauthenticated user cannot access the 'me'
     * endpoint.
     *
     * @return void
     */
    public function test_unauthenticated_user_can_access_me_endpoint(): void
    {
        $query =
            /** @lang GraphQL */
            'query Me {
                me {
                    id
                    name
                    email
                }
            }';

        $response = $this->postGraphQL(
            ['query' => $query],
            $this->getHeaders('invalid_bearer')
        );

        $response->assertJson([
            'data' => [
                'me' => null,
            ],
        ]);
    }

    public function test_user_can_logout_successfully(): void
    {
        $user = User::factory()->create();

        $mutation =
            /** @lang GraphQL */
            'mutation Logout {
                logout
            }
        ';

        $response = $this->postGraphQL(
            ['query' => $mutation],
            $this->getHeaders($this->createToken($user))
        );


        $response->assertJsonFragment([
            'data' => [
                'logout' => true,
            ]
        ]);
    }
}
