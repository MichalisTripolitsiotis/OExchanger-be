<?php

namespace Tests\Feature\Community;

use App\Models\Community;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommunityQueriesTest extends TestCase
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
     * Ensure a user can view all communities.
     *
     * @return void
     */
    public function testUserCanViewAllCommunities(): void
    {
        $viewQuery =
            /** @lang GraphQL */
            'query Community {
                communities {
                    name,
                    description
                }
            }';


        $response = $this->postGraphQL([
            'query' => $viewQuery
        ], $this->getHeaders($this->createToken($this->user)));

        $communities = Community::all(['name', 'description']);

        $response->assertJsonCount($communities->count(), 'data.communities.*');

        $response->assertJsonFragment([
            'data' => [
                'communities' => $communities->toArray()
            ]
        ]);
    }

    /**
     * Ensure a user that moderates a community can view it successfully.
     *
     * @return void
     */
    public function testUserCanViewAModeratedCommunity(): void
    {
        $viewQuery =
            /** @lang GraphQL */
            'query Community($id: ID!) {
                community(id: $id) {
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
            'query' => $viewQuery,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->user)));

        $response->assertJson([
            'data' => [
                'community' => [
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
    }

    /**
     * Ensure a user cannot view a specified community where is not a moderator.
     *
     * @return void
     */
    public function testUserCannotViewNonModeratedCommunity(): void
    {
        $viewQuery =
            /** @lang GraphQL */
            'query Community($id: ID!) {
                community(id: $id) {
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
            'query' => $viewQuery,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->secondUser)));

        $response->assertGraphQLErrorMessage("This action is unauthorized.");
    }
}
