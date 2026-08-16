<?php

namespace Database\Seeders;

use Encore\Admin\Auth\Database\Menu;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $urls = [
            'percentage-target'
        ];


        foreach ($urls as $url) {
            $name =  str_replace('-', ' ', $url);
            if (Menu::where('title' , $name)->exists()) continue;
            $admin_menu =
                [

                    "parent_id"  => 0,
                    "order"      => 0,
                    "title"      => $name,
                    "icon"       => "fa-bar-chart",
                    "uri"        => $url,
                    "permission" => NULL,
                ];
            Menu::create($admin_menu);
        }
    }
}
