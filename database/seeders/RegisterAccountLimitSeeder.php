<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegisterAccountLimitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if the config already exists
        $exists = DB::table('configs')->where('name', 'register_account')->exists();
        
        if (!$exists) {
            DB::table('configs')->insert([
                'name' => 'register_account',
                'value' => '3',
                'desc' => 'حد الحسابات المسموح بتسجيلها من نفس الجهاز',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Update if it already exists
            DB::table('configs')
                ->where('name', 'register_account')
                ->update([
                    'value' => '3',
                    'desc' => 'حد الحسابات المسموح بتسجيلها من نفس الجهاز',
                    'updated_at' => now(),
                ]);
        }
    }
}
