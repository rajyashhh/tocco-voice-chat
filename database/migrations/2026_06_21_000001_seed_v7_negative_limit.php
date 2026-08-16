<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V7_negative_limit is now the SINGLE owner-controlled source of truth for the
 * vault negative limit (FairLuckSetting::getVaultNegativeLimit), replacing the
 * old turnover-derived limit (max(30k, 5% of 24h intake)) that let the vault
 * drain to tens of millions and latch realised RTP at 0%. The prune migration
 * (2026_06_13_000005) removed global_vault_negative_limit but never seeded this
 * key, so it could be absent until the owner first saved the panel. Seed it so
 * the single source has a durable row and the panel reflects a real value.
 */
return new class extends Migration {
    public function up(): void
    {
        $exists = DB::table('fair_luck_settings')->where('key', 'V7_negative_limit')->exists();
        if (!$exists) {
            DB::table('fair_luck_settings')->insert([
                'key'         => 'V7_negative_limit',
                'value'       => '30000',
                'description' => 'How far negative the vault may go (absolute coins). Owner-controlled from the lucky-gift settings panel.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No-op: removing the active limit key would reintroduce ambiguity.
    }
};
