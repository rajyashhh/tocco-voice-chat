<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;


class AddUserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'utd',
            'phone' => '+201000100010',
            'password' => '111',
            'can_play' => 3,
        ]);
    }
}
