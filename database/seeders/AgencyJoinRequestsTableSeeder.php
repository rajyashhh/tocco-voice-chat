<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgencyJoinRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($user_id = 1800; $user_id <= 1900; $user_id++) {
            DB::table('agency_join_requests')->insert([
                'user_id' => $user_id,
                'agency_id' => 9,
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }}
}
