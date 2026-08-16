<?php

namespace Database\Seeders;


use App\Models\User;
use App\Models\Ware;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class WarePaddingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Ware::where('type', 5)->update([
            'top' => 20,
            'left' => 15,
            'right' => 15,
            'bottom' => 15,
        ]);
    }
}
