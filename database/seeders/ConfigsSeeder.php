<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $configs = array(
            array('name' => 'login_from_only_one_device','value' => 'yes','desc' => '','created_at' => '2022-12-18 15:54:52','updated_at' => '2022-12-18 15:57:13'),
            array('name' => 'platform_share','value' => '10','desc' => '','created_at' => '2022-12-18 15:55:07','updated_at' => '2022-12-18 15:57:38'),
            array('name' => 'one_usd_value_in_coins','value' => '10','desc' => '','created_at' => '2022-12-18 15:55:39','updated_at' => '2022-12-18 15:58:11'),
        );
        DB::table ('configs')->insert ($configs);
    }
}