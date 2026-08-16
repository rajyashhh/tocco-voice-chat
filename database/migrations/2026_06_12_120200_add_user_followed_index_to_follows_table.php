<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * enter_room now answers "does the requesting user follow the host?" on the
 * hot entry path. The single-column idx_follows_user_id still scans all of a
 * heavy user's follow rows; this composite makes it an index point lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->index(['user_id', 'followed_user_id'], 'idx_follows_user_followed');
        });
    }

    public function down(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex('idx_follows_user_followed');
        });
    }
};
