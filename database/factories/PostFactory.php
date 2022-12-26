<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $user = User::has('subscribedCommunities')->inRandomOrder()->first();
        $community = $user->subscribedCommunities()->inRandomOrder()->first();

        return [
            'title' => fake()->words(rand(1, 5), true),
            'text' => fake()->text(),
            'user_id' => $user->id,
            'community_id' => $community->id
        ];
    }
}
