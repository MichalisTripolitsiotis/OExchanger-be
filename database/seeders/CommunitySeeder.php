<?php

namespace Database\Seeders;

use App\Models\Community;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $communities = [
            [
                'name' => 'Politics',
                'description' => 'To discuss politics'
            ],
            [
                'name' => 'News and Events',
                'description' => 'To discuss news and world events'
            ],
            [
                'name' => 'Food and Cooking',
                'description' => 'To discuss cooking and food'
            ],
            [
                'name' => 'Animals and Nature',
                'description' => 'To discuss politics'
            ],
            [
                'name' => 'Petrolheads',
                'description' => 'To discuss about cars'
            ],
            [
                'name' => 'Traveling',
                'description' => 'To discuss about the world'
            ]
        ];

        foreach ($communities as $community) {
            Community::create($community);
        }
    }
}
