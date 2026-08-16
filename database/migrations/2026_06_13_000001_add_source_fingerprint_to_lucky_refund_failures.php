<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S-CON-2 / 7.1: the permanent fallback drain inserts one row per fallback item
 * keyed by a content fingerprint. UNIQUE(source_fingerprint) makes INSERT IGNORE
 * idempotent so a re-run never double-credits the same recovered compensation.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('lucky_refund_failures', function (Blueprint $table) {
            if (!Schema::hasColumn('lucky_refund_failures', 'source_fingerprint')) {
                $table->string('source_fingerprint', 64)->nullable()->after('reason');
                $table->unique('source_fingerprint');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lucky_refund_failures', function (Blueprint $table) {
            $table->dropUnique(['source_fingerprint']);
            $table->dropColumn('source_fingerprint');
        });
    }
};
