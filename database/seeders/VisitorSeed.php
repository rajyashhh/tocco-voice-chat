<?php

namespace Database\Seeders;

use App\Models\Cp;
use App\Models\Gift;
use App\Models\User;
use App\Models\GiftLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class VisitorSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $users = User::take(20)->get();
        $visito = User::findOrFail(774);
        foreach ($users as $user) {
            $data = [
                'user_id' => $user->id,
                'visitor_id' => $visito->id,
                'created_at' => now(),
                'updated_at' => now(),

            ];
            DB::table('profile_visitors')->insert($data);
        }
    }
}
