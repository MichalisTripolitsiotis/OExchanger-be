<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected static $token = null;
    protected User $user;

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
    public function testUserRegistersSuccessfully(): void
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
                "password_confirmation" => "password123",
                "callbackUrl" => "https://www.example.com"
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        $response->assertJsonFragment([
            'data' => [
                'register' => [
                    'message' => "Account created successfully. An email just sent."
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
    public function testUserCannotRegisterWithMisspelledPassword(): void
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
                "password_confirmation" => "password1234",
                "callbackUrl" => "https://www.example.com"
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
    public function testUserCannotRegisteredIfAlreadyDid(): void
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
                "password_confirmation" => "password123",
                "callbackUrl" => "https://www.example.com"
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
    public function testUserVerifiesEmailSuccessfully(): void
    {
        Notification::fake();
        Event::fake([Verified::class]);

        $payload = base64_encode(json_encode([
            'id'         => $this->user->id,
            'hash'       => encrypt($this->user->getEmailForVerification()),
            'expiration' => encrypt(Carbon::now()->addMinutes(10)->toIso8601String()),
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
    public function testUserLoginSuccessfully(): void
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
    public function testUserCannotLoginWithWrongCredentials(): void
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
     * @return void
     *
     * Ensure that a user the email with the password reset token
     * can be sent successfully.
     */
    public function testForgotPasswordSuccessfully(): void
    {
        Notification::fake();

        $mutation =
            /** @lang GraphQL */
            'mutation ForgotPassword($input: ForgotPasswordInput!) {
                forgotPassword(input: $input)
            }
        ';

        $input = [
            'input' => [
                'email' => $this->user->email,
                'callbackUrl' => 'https://www.example.com'
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        Notification::assertSentTo([$this->user], ResetPassword::class);

        $response->assertJson([
            'data' => [
                'forgotPassword' => true
            ],
        ]);

        $this->assertDatabaseHas('password_resets', [
            'email' => $this->user->email,
        ]);
    }

    /**
     * @test
     *
     * Ensure that a user can change the password successfully.
     */
    public function testPasswordResetSuccessfully(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation ResetPassword($input: ResetPasswordInput!) {
                resetPassword(input: $input)
            }
        ';

        $token = Password::createToken($this->user);

        $input = [
            'input' => [
                'token' => $token,
                'email' => $this->user->email,
                'password' => 'newPassword123',
                'password_confirmation' => 'newPassword123'
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        //override the token
        $data = json_decode($response->getContent(), true);
        static::$token = $token = $data['data']['resetPassword'];
        $this->assertNotEquals(null, $token);

        $this->assertDatabaseMissing('password_resets', [
            'email' => $this->user->email,
        ]);

        $user = User::find($this->user->id);
        $this->assertTrue(Hash::check('newPassword123', $user->password));
    }

    /**
     * @test
     *
     * Ensure that user's password cannot change if the token is invalid.
     */
    public function testPasswordResetIncorrectToken(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation ResetPassword($input: ResetPasswordInput!) {
                resetPassword(input: $input)
            }
        ';

        $token = Password::createToken($this->user);

        $input = [
            'input' => [
                'token' => 'invalid_token',
                'email' => $this->user->email,
                'password' => 'newPassword12345',
                'password_confirmation' => 'newPassword12345'
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => $input
        ]);

        $response->assertJson([
            'errors' => [
                [
                    'debugMessage' => "Provided token is invalid"
                ],
            ],
        ]);

        $this->assertDatabaseHas('password_resets', [
            'email' => $this->user->email,
        ]);

        $user = User::find($this->user->id);
        $this->assertFalse(Hash::check('newPassword12345', $user->password));
    }


    /**
     * Ensure that the authenticated user can access the 'me'
     * endpoint.
     *
     * @return void
     */
    public function testAuthenticatedUserCanAccessMeEndpoint(): void
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
    public function testUnauthenticatedUserCannotAccessMeEndpoint(): void
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

    public function testUserCanLogoutSuccessfully(): void
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
