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
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->uuid('room_boom_uuid')->nullable()->index();
            $table->tinyInteger('room_boom_level')->nullable();
            $table->boolean('start_boom_ranking')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropColumn('room_boom_uuid');
            $table->dropColumn('room_boom_level');
            $table->dropColumn('start_boom_ranking');
        });
    }
};
