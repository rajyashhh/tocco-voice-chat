<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * §4.4 / §4.1: seed the beginner-protection panel rows (all OFF / conservative by
 * default — re-enable is an owner decision). RTP_boost = 0.92 (frequent-win, not a
 * subsidised jackpot — B-ECO-2). beginner_max_age_days = 10. lifetime_spent gate
 * dropped per the owner decision (2026-06-13): eligibility is (first paid charge)
 * AND (account age < max_age_days) only. Idempotent insert (skip existing keys).
 */
return new class extends Migration {
    public function up(): void
    {
        $rows = [
            ['key' => 'beginner_protection_enabled', 'value' => '0',    'description' => 'Master toggle for beginner protection (off by default).'],
            ['key' => 'beginner_budget_coins',       'value' => '0',    'description' => 'Per-eligible-account boosted budget (coins). 0 = no budget.'],
            ['key' => 'beginner_max_age_days',       'value' => '10',   'description' => 'Account must be younger than this to qualify.'],
            ['key' => 'beginner_global_daily_cap',   'value' => '0',    'description' => 'Global daily ceiling on total coins paid as beginner support. 0 = disabled.'],
            ['key' => 'RTP_boost',                   'value' => '0.92', 'description' => 'EV anchor of the beginner boosted table (no tail above x100).'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('fair_luck_settings')->where('key', $row['key'])->exists();
            if (!$exists) {
                DB::table('fair_luck_settings')->insert([
                    'key'         => $row['key'],
                    'value'       => $row['value'],
                    'description' => $row['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('fair_luck_settings')->whereIn('key', [
            'beginner_protection_enabled',
            'beginner_budget_coins',
            'beginner_max_age_days',
            'beginner_global_daily_cap',
            'RTP_boost',
        ])->delete();
    }
};
