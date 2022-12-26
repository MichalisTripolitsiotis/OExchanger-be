<?php

namespace Tests\Feature\Community;

use App\Models\Community;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommunityMutationsTest extends TestCase
{
    protected User $user;
    protected User $secondUser;
    protected Community $community;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => Hash::make('password')]);
        $this->secondUser = User::factory()->create(['password' => Hash::make('password')]);
        $this->community = Community::create([
            "name" => "Greece",
            "description" => "Discuss about Greece"
        ]);
        $this->user->moderatedCommunities()->attach($this->community->id);
    }

    /**
     * Ensure a user can create a new community.
     *
     * @return void
     */
    public function testUserCanCreateANewCommunity(): void
    {
        $createMutation =
            /** @lang GraphQL */
            'mutation CreateCommunity($input: CreateCommunityInput!) {
                createCommunity(input: $input) {
                    id,
                    name,
                    description
                    moderators {
                        id,
                        name
                    }
                }
            }';

        $input = [
            "name" => "Motorbikes",
            "description" => "Discuss about bikes",
            "moderators" => [
                "sync" => [$this->user->id]
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $createMutation,
            'variables' => compact('input')
        ], $this->getHeaders($this->createToken($this->user)));

        $id = $this->getMutationId($response, 'createCommunity');

        $response->assertJson([
            'data' => [
                'createCommunity' => [
                    'id' => $id,
                    'name' => $input['name'],
                    'description' => $input['description'],
                    'moderators' => [
                        [
                            'id' => strval($this->user->id),
                            'name' => $this->user->name
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('communities', [
            'id' => $id,
            'name' => $input['name'],
            'description' => $input['description']
        ]);


        $this->assertDatabaseHas('moderated_communities', [
            'user_id' => $this->user->id,
            'community_id' => $id
        ]);
    }

    /**
     * Ensure a moderator user can update a community.
     *
     * @return void
     */
    public function testModeratorUserUpdateCommunity(): void
    {
        $updateMutation =
            /** @lang GraphQL */
            'mutation UpdateCommunity($input: UpdateCommunityInput!) {
                updateCommunity(input: $input) {
                    id,
                    name,
                    description
                    moderators {
                        id,
                        name
                    }
                }
            }';

        $input = [
            "id" => $this->community->id,
            "name" => "Germany",
            "description" => "Discuss about Germany",
            "moderators" => [
                "sync" => [$this->user->id]
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $updateMutation,
            'variables' => compact('input')
        ], $this->getHeaders($this->createToken($this->user)));

        $response->assertJson([
            'data' => [
                'updateCommunity' => [
                    'id' => $this->community->id,
                    'name' => $input['name'],
                    'description' => $input['description'],
                    'moderators' => [
                        [
                            'id' => strval($this->user->id),
                            'name' => $this->user->name
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('communities', [
            'id' => $this->community->id,
            'name' => $input['name'],
            'description' => $input['description']
        ]);


        $this->assertDatabaseHas('moderated_communities', [
            'user_id' => $this->user->id,
            'community_id' => $this->community->id
        ]);
    }

    /**
     * Ensure a user cannot update a community that is not the moderator.
     *
     * @return void
     */
    public function testUserCannotUpdateNonModeratedCommunity(): void
    {
        $updateMutation =
            /** @lang GraphQL */
            'mutation UpdateCommunity($input: UpdateCommunityInput!) {
                updateCommunity(input: $input) {
                    id,
                    name,
                    description
                    moderators {
                        id,
                        name
                    }
                }
            }';

        $input = [
            "id" => $this->community->id,
            "name" => "Germany",
            "description" => "Discuss about Germany",
            "moderators" => [
                "sync" => [$this->secondUser->id]
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $updateMutation,
            'variables' => compact('input')
        ], $this->getHeaders($this->createToken($this->secondUser)));

        $response->assertGraphQLErrorMessage("This action is unauthorized.");

        $this->assertDatabaseMissing('communities', [
            'id' => $this->community->id,
            'name' => $input['name'],
            'description' => $input['description']
        ]);


        $this->assertDatabaseMissing('moderated_communities', [
            'user_id' => $this->secondUser->id,
            'community_id' => $this->community->id
        ]);
    }

    /**
     * Ensure a user can delete a specified community.
     *
     * @return void
     */
    public function testModeratorUserCanDeleteCommunity(): void
    {
        $deleteMutation =
            /** @lang GraphQL */
            'mutation DeleteCommunity($id: ID!) {
                deleteCommunity(id: $id) {
                    id,
                    name,
                    description
                    moderators {
                        id,
                        name
                    }
                }
            }';

        $id = $this->community->id;

        $response = $this->postGraphQL([
            'query' => $deleteMutation,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->user)));

        $response->assertJson([
            'data' => [
                'deleteCommunity' => [
                    'id' => $this->community->id,
                    'name' => $this->community->name,
                    'description' => $this->community->description,
                    'moderators' => [
                        [
                            'id' => strval($this->user->id),
                            'name' => $this->user->name
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertSoftDeleted('communities', [
            'id' => $this->community->id,
            'name' => $this->community->name,
            'description' => $this->community->description
        ]);


        $this->assertDatabaseMissing('moderated_communities', [
            'user_id' => $this->secondUser->id,
            'community_id' => $this->community->id
        ]);
    }


    /**
     * Ensure a user cannot delete a specified community where is not a moderator.
     *
     * @return void
     */
    public function testUserCannotDeleteNonModeratedCommunity(): void
    {
        $deleteMutation =
            /** @lang GraphQL */
            'mutation DeleteCommunity($id: ID!) {
                deleteCommunity(id: $id) {
                    id,
                    name,
                    description
                    moderators {
                        id,
                        name
                    }
                }
            }';

        $id = $this->community->id;

        $response = $this->postGraphQL([
            'query' => $deleteMutation,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->secondUser)));

        $response->assertGraphQLErrorMessage("This action is unauthorized.");

        $this->assertDatabaseHas('communities', [
            'id' => $this->community->id,
            'name' => $this->community->name,
            'description' => $this->community->description
        ]);


        $this->assertDatabaseHas('moderated_communities', [
            'user_id' => $this->user->id,
            'community_id' => $this->community->id
        ]);
    }
}
