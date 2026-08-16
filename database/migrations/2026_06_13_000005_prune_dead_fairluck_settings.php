<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * §5.5: remove dead V7 setting keys whose behaviour is now a code constant or a
 * single derived source of truth — pruned atomically WITH the code that stopped
 * reading them:
 *   V7_batch_mode          — there is one unified path now (no serial/batch toggle).
 *   V7_redis_authoritative — Redis is always authoritative on the unified path.
 *   global_vault_negative_limit — replaced by the derived fairluck:derived limit
 *                                 with V7_negative_limit as the cold-start floor.
 * down() is intentionally a no-op (these keys are dead; re-seeding them would
 * reintroduce ambiguity).
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('fair_luck_settings')->whereIn('key', [
            'V7_batch_mode',
            'V7_redis_authoritative',
            'global_vault_negative_limit',
        ])->delete();
    }

    public function down(): void
    {
        // No-op: dead keys are not restored.
    }
};
