<?php

namespace Database\Seeders;

use App\Models\Follow;
use App\Models\User;
use App\Models\RoomGame;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FriendsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userIds = User::take(100)->pluck('id')->toArray();
        foreach ($userIds as $id) {
            Follow::create([
                'user_id' => 524,
                'followed_user_id' => $id,
                'status' => 1,
            ]);
            Follow::create([
                'user_id' => $id,
                'followed_user_id' => 524,
                'status' => 1,
            ]);
        }
    }
}
