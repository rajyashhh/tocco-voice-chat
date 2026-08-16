<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unique viewers of the CURRENT live broadcast — session-scoped dedupe set
 * (one row per room+user per broadcast) behind rooms.live_viewers_total.
 * Rows are pruned and the counter reset on end-live, mirroring the
 * live_tap_totals lifecycle in RoomOccupancyReconciler::deactivateRoom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_viewers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->nullable();

            // Dedupe key; its room_id prefix also serves the end-live prune.
            $table->unique(['room_id', 'user_id'], 'uq_live_session_viewer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_viewers');
    }
};
