<?php

namespace Database\Seeders;

use App\helper\UserFollowHelper;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserFollowSeeder extends Seeder
{
  
    public function run(): void
    {
        $this->command->info('Updating user follow counts...');

        User::chunk(200, function ($users) {
            foreach ($users as $user) {
                UserFollowHelper::updateCounts($user);
            }
        });

        $this->command->info('All users follow counts updated successfully!');
    }
}
