<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserCommunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Not all users should be moderators. Exclude some of them.
        $users = User::all()->random(rand(1, User::count() - 1));
        $communites = Community::all();

        $users->each(function ($user) use ($communites) {
            // Moderate 1 or maximum 2 communities
            $IDs = $communites->random(rand(1, 2))->pluck('id')->toArray();
            $user->communities()->attach($IDs);
        });
    }
}
