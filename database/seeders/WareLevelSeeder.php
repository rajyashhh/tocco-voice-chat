<?php

namespace Database\Seeders;


use App\Models\Ware;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class WareLevelSeeder extends Seeder

{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ware::whereIn('get_type', [4, 6])->update(['level' => null]);
    }
}
