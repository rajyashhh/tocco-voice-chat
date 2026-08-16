<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('black_lists', function (Blueprint $table) {
            // Composite index for faster lookup
            $table->index(['user_id', 'from_uid'], 'idx_blacklist_user_from');

            // Optional: add reverse index if queries often swap the columns
            $table->index(['from_uid', 'user_id'], 'idx_blacklist_from_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('black_lists', function (Blueprint $table) {
            // Composite index for faster lookup
            $table->index(['user_id', 'from_uid'], 'idx_blacklist_user_from');

            // Optional: add reverse index if queries often swap the columns
            $table->index(['from_uid', 'user_id'], 'idx_blacklist_from_user');
        });
    }
};
