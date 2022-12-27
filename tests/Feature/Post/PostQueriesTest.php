<?php

namespace Tests\Feature\Post;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostQueriesTest extends TestCase
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
     * Ensure a user can view all posts.
     *
     * @return void
     */
    public function testUserCanViewAllPosts(): void
    {
        $viewQuery =
            /** @lang GraphQL */
            'query Post {
                posts {
                    title,
                    text
                }
            }';


        $response = $this->postGraphQL([
            'query' => $viewQuery
        ], $this->getHeaders($this->createToken($this->user)));

        $posts = Post::all(['title', 'text']);

        $response->assertJsonCount($posts->count(), 'data.posts.*');

        $response->assertJsonFragment([
            'data' => [
                'posts' => $posts->toArray()
            ]
        ]);
    }

    /**
     * Ensure a user cannot view an owned post.
     *
     * @return void
     */
    public function testUserCannotViewOwnPost(): void
    {
        $viewQuery =
            /** @lang GraphQL */
            'query Post($id: ID!) {
                post(id: $id) {
                    id,
                    title,
                    text
                    user {
                        id,
                        name
                    }
                }
            }';

        $id = $this->post->id;

        $response = $this->postGraphQL([
            'query' => $viewQuery,
            'variables' => compact('id')
        ], $this->getHeaders($this->createToken($this->user)));

        $response->assertJson([
            'data' => [
                'post' => [
                    'id' => $this->post->id,
                    'title' => $this->post->title,
                    'text' => $this->post->text,
                    'user' => [
                        'id' => strval($this->user->id),
                        'name' => $this->user->name
                    ]
                ]
            ]
        ]);
    }
}
