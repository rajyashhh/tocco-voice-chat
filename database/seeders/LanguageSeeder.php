<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $languages = [
            ['name' => 'English', 'code' => 'en', 'direction' => 'LTR', 'is_enabled' => true, 'is_default' => true,],
            ['name' => 'العربية', 'code' => 'ar', 'direction' => 'RTL', 'is_enabled' => true],
            ['name' => 'Türkçe', 'code' => 'tr', 'direction' => 'LTR', 'is_enabled' => true],
            ['name' => 'हिन्दी', 'code' => 'hi', 'direction' => 'LTR', 'is_enabled' => true],
            ['name' => 'Indonesia', 'code' => 'id', 'direction' => 'LTR', 'is_enabled' => true],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language);
        }
    }
}
