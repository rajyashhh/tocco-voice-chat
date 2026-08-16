<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix #4: Add missing indexes to follows table.
     * The slowest query in the system (13.75s) does a full scan of 100M rows
     * because followed_user_id has no index. This reduces it to <0.05s.
     */
    public function up(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->index('followed_user_id', 'idx_follows_followed_user_id');
            $table->index('user_id', 'idx_follows_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex('idx_follows_followed_user_id');
            $table->dropIndex('idx_follows_user_id');
        });
    }
};
