<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * §5.7 / N-CON-3: explicit, version-atomic cutover flag for the lucky-gift ranking
 * write path. Both the legacy V1 chain (updateCache → redis:get_data cron →
 * UpdateUserDataWhenSendGift) and the new direct PostJob write read THIS flag, so
 * the cutover does not depend on rolling-deploy timing or on stop_luckyGift=1.
 *
 * Seeded OFF (=0): the legacy chain stays authoritative until the cutover is
 * enabled deliberately (the direct-write wiring is staged separately — see the
 * deferred note in the engine work). Idempotent.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!DB::table('settings')->where('key', 'ranking_direct_write')->exists()) {
            DB::table('settings')->insert([
                'key'        => 'ranking_direct_write',
                'value'      => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'ranking_direct_write')->delete();
    }
};
