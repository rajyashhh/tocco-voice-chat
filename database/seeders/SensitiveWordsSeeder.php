<?php

namespace Database\Seeders;

use App\Models\SensitiveWord;
use Illuminate\Database\Seeder;

class SensitiveWordsSeeder extends Seeder
{
    /**
     * Seed the sensitive_words table with Arabic bad words.
     */
    public function run(): void
    {
        $words = require resource_path('lang/ar/bad-word.php');

        // Remove duplicates
        $words = array_unique(array_map('trim', $words));

        $records = [];
        $now = now();

        foreach ($words as $word) {
            if (empty($word)) {
                continue;
            }

            $records[] = [
                'word'        => json_encode(['ar' => $word]),
                'replacement' => '***',
                'severity'    => 'medium',
                'action'      => 'filter',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($records, 50) as $chunk) {
            SensitiveWord::insert($chunk);
        }
    }
}
