<?php

namespace Database\Seeders;

use App\Models\DevicesTokenHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevicesTokenHistories extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotNull('device_token')->get();

        foreach ($users as $user) {
            $record = DevicesTokenHistory::where('device_token', $user->device_token)->first();
            if ($record) {
                $record->increment('count');
            } else {
                DevicesTokenHistory::create(['device_token' => $user->device_token, 'count' => 1]);
            }
        }
    }
}
