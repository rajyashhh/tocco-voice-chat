<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correlate a batch intent back to the client request nonce, so S-ECO-3
 * rebuild-from-proof (LuckyEngine::rebuildFromProof) can find the settled intent
 * for a claimed-but-unanswered nonce. Nullable for old in-flight rows.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('lucky_batch_intents', function (Blueprint $table) {
            if (!Schema::hasColumn('lucky_batch_intents', 'request_nonce')) {
                $table->uuid('request_nonce')->nullable()->after('nonce');
                $table->index('request_nonce');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lucky_batch_intents', function (Blueprint $table) {
            $table->dropIndex(['request_nonce']);
            $table->dropColumn('request_nonce');
        });
    }
};
