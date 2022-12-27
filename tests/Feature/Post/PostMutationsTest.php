<?php

namespace Tests\Feature\Post;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostMutationsTest extends TestCase
{
    protected User $user;
    protected User $secondUser;
    protected Community $community;
    protected Post $post;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => Hash::make('password')]);
        $this->secondUser = User::factory()->create(['password' => Hash::make('password')]);
        $this->community = Community::create([
            "name" => "A fancy community",
            "description" => "Fancy community"
        ]);

        $this->post = Post::create([
            'title' => "A fancy post",
            'text' => "Loren ipsum",
            'user_id' => $this->user->id,
            'community_id' => $this->community->id
        ]);
    }

    /**
     * Ensure a user can create a new post.
     *
     * @return void
     */
    public function testUserCanCreateANewPost(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation CreatePost($input: CreatePostInput!) {
                createPost(input: $input) {
                    id,
                    title,
                    text
                    user {
                      id,
                      name
                    },
                    community {
                        id,
                        name
                    }
                }
            }';

        $input = [
            "title" => "My first post",
            "text" => "Lorem ipsum dolor sit amet",
            "user" => [
                "connect" => $this->user->id
            ],
            "community" => [
                "connect" => $this->community->id
            ]
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => compact('input')
        ], $this->getHeaders($this->createToken($this->user)));

        $id = $this->getMutationId($response, 'createPost');

        $response->assertJson([
            'data' => [
                'createPost' => [
                    'id' => $id,
                    'title' => $input['title'],
                    'text' => $input['text'],
                    'user' => [
                        'id' => strval($this->user->id),
                        'name' => $this->user->name
                    ],
                    'community' => [
                        'id' => strval($this->community->id),
                        'name' => $this->community->name
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $input['title'],
            'text' => $input['text']
        ]);
    }

    /**
     * Ensure a user can update a post.
     *
     * @return void
     */
    public function testUserCanUpdateAPost(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation UpdatePost($input: UpdatePostInput!) {
                updatePost(input: $input) {
                    id,
                    title,
                    text
                    user {
                      id,
                      name
                    },
                    community {
                        id,
                        name
                    }
                }
            }';

        $input = [
            "id" => $this->post->id,
            "title" => "Updated post",
            "text" => "Updated text"
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => compact('input')
        ], $this->getHeaders($this->createToken($this->user)));

        $id = $this->getMutationId($response, 'updatePost');

        $response->assertJson([
            'data' => [
                'updatePost' => [
                    'id' => $id,
                    'title' => $input['title'],
                    'text' => $input['text'],
                    'user' => [
                        'id' => strval($this->user->id),
                        'name' => $this->user->name
                    ],
                    'community' => [
                        'id' => strval($this->community->id),
                        'name' => $this->community->name
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $input['title'],
            'text' => $input['text']
        ]);
    }

    /**
     * Ensure a user cannot update a post that belongs to another user.
     *
     * @return void
     */
    public function testUserCannotUpdateNonRelatedPost(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation UpdatePost($input: UpdatePostInput!) {
                updatePost(input: $input) {
                    id,
                    title,
                    text
                    user {
                      id,
                      name
                    },
                    community {
                        id,
                        name
                    }
                }
            }';

        $input = [
            "id" => $this->post->id,
            "title" => "Updated post",
            "text" => "Updated text",
            "user" => [
                "connect" => $this->secondUser->id
            ],
        ];

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => compact('input')
        ], $this->getHeaders($this->createToken($this->secondUser)));

        $response->assertGraphQLErrorMessage("This action is unauthorized.");

        $this->assertDatabaseMissing('posts', [
            'id' => $this->post->id,
            'title' => $input['title'],
            'text' => $input['text'],
            'user_id' => $this->secondUser->id
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'title' => $this->post->title,
            'text' => $this->post->text,
            'user_id' => $this->user->id
        ]);
    }


    /**
     * Ensure a user can delete a post.
     *
     * @return void
     */
    public function testUserCanDeletePost(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation DeletePost($id: ID!) {
                deletePost(id: $id) {
                    id,
                    title,
                    text
                    user {
                      id,
                      name
                    },
                    community {
                        id,
                        name
                    }
                }
            }';

        $id = $this->post->id;

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->user)));

        $response->assertJson([
            'data' => [
                'deletePost' => [
                    'id' => $id,
                    'title' => $this->post->title,
                    'text' => $this->post->text,
                    'user' => [
                        'id' => strval($this->user->id),
                        'name' => $this->user->name
                    ],
                    'community' => [
                        'id' => strval($this->community->id),
                        'name' => $this->community->name
                    ]
                ]
            ]
        ]);

        $this->assertSoftDeleted('posts', [
            'id' => $this->post->id,
            'title' => $this->post->title,
            'text' => $this->post->text,
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Ensure a user cannot delete a post that belongs to another user.
     *
     * @return void
     */
    public function testUserCannotDeleteNonRelatedPost(): void
    {
        $mutation =
            /** @lang GraphQL */
            'mutation DeletePost($id: ID!) {
                deletePost(id: $id) {
                    id,
                    title,
                    text
                    user {
                      id,
                      name
                    },
                    community {
                        id,
                        name
                    }
                }
            }';

        $id = $this->post->id;

        $response = $this->postGraphQL([
            'query' => $mutation,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->secondUser)));


        $response->assertGraphQLErrorMessage("This action is unauthorized.");

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'title' => $this->post->title,
            'text' => $this->post->text,
            'user_id' => $this->user->id
        ]);
    }
}
