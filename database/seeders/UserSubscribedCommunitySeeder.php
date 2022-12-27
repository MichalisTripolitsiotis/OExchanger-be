<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSubscribedCommunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Not all users should be moderators. Exclude some of them.
        $users = User::whereDoesntHave('moderatedCommunities')->get();
        $communites = Community::all();

        $users->each(function ($user) use ($communites) {
            // Join 1 or maximum 3 communities
            $IDs = $communites->random(rand(1, 3))->pluck('id')->toArray();
            $user->subscribedCommunities()->attach($IDs);
        });
    }
}
