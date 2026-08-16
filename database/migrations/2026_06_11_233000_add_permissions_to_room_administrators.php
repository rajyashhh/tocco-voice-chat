<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Granular admin permissions (owner spec 2026-06-11): the room/live owner
 * picks which powers each admin gets. NULL = ALL permissions (backward
 * compatible with every existing admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_administrators', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('room_administrators', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
