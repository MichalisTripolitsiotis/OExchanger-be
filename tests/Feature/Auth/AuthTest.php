<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
     * Ensure a user can register successfully
     *
     * @return void
     */
    public function test_user_registers_successfully(): void
    {
        Notification::fake();

        $mutation =
            /** @lang GraphQL */
            'mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    message
                }
            }
        ';

        $input = [
            'input' => [
                "name" => "John Doe",
                "email" => "jdoe@gmail.com",
                "password" => "password123",
                "password_confirmation" => "password123"
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        $response->assertJsonFragment([
            'data' => [
                'register' => [
                    'message' => "Account created successfully. An email sent to your account."
                ],
            ]
        ]);

        $user = User::where(['name' => "John Doe"])->first();

        $this->assertDatabaseHas('users', [
            "name" => $user->name,
            "email" => $user->email
        ]);

        Notification::assertSentTo(
            [$user],
            VerifyEmail::class
        );
    }

    /**
     * Ensure a user cannot register if the passwords don't match.
     *
     * @return void
     */
    public function test_user_cannot_register_with_misspelled_password(): void
    {
        Notification::fake();

        $mutation =
            /** @lang GraphQL */
            'mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    message
                }
            }
        ';

        $input = [
            'input' => [
                "name" => "Peter Doe",
                "email" => "pdoe@gmail.com",
                "password" => "password123",
                "password_confirmation" => "password1234"
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        $response->assertGraphQLValidationError("input.password", "The input.password confirmation does not match.");

        $this->assertDatabaseMissing('users', [
            "name" => 'Peter Doe',
            "email" => "pdoe@gmail.com"
        ]);

        Notification::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_user_cannot_registered_if_already_did(): void
    {
        Notification::fake();

        $mutation =
            /** @lang GraphQL */
            'mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    message
                }
            }
        ';

        $input = [
            'input' => [
                "name" => $this->user->name,
                "email" => $this->user->email,
                "password" => "password123",
                "password_confirmation" => "password123"
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        $response->assertGraphQLValidationError("input.email", "The input.email has already been taken.");

        Notification::assertNothingSent();
    }

    /**
     * Mock the email that is sent to the user. Pass the token
     * into the mutation to ensure that the user can be verified.
     *
     * @return void
     */
    public function test_user_verifies_email_successfully(): void
    {
        Notification::fake();
        Event::fake([Verified::class]);

        $payload = base64_encode(json_encode([
            'hash'  => encrypt($this->user->getEmailForVerification()),
        ]));

        $input = [
            'input' => [
                "token" => $payload
            ]
        ];

        $mutation =
            /** @lang GraphQL */
            'mutation VerifyEmail($input: VerifyEmailInput!) {
                verifyEmail(input: $input)
            }
        ';

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        $response->assertJsonFragment([
            'data' => [
                'verifyEmail' => true
            ]
        ]);

        $this->assertNotNull($this->user->email_verified_at);

        Event::assertDispatched(Verified::class);
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
