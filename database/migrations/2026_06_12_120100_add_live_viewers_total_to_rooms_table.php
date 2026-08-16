<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O(1) cumulative unique-viewers counter for the CURRENT broadcast — bumped
 * in enter_room only when live_session_viewers actually inserted a new row,
 * shipped in the enter-room payload, reset to zero on end-live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('live_viewers_total')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('live_viewers_total');
        });
    }
};
