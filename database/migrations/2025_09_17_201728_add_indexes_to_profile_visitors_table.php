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
        Schema::table('profile_visitors', function (Blueprint $table) {
            $table->index(
                ['visitor_id', 'user_id', 'created_at', 'updated_at'],
                'idx_profile_visitors_lookup'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_visitors', function (Blueprint $table) {
            Schema::table('profile_visitors', function (Blueprint $table) {
                $table->dropIndex('idx_profile_visitors_lookup');
            });
        });
    }
};
