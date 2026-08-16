<?php

namespace Database\Seeders;

use App\Models\FamilyUser;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class FamilyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {$users = User::factory(100)->create(['di' => 900000]);

        foreach ($users as $user) {
            Profile::factory()->create(['user_id' =>$user->id]);
            FamilyUser::factory()->create(['user_id' =>$user->id, 'family_id' => 325]);
        }
    }
}
