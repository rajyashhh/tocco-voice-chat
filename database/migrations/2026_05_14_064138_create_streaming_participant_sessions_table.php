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
        Schema::create('streaming_participant_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_session_id')->constrained('streaming_room_sessions')->cascadeOnDelete();

            $table->string('participant_identity')->index();
            $table->string('participant_name')->nullable();

            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->timestamps();

            $table->index(['participant_identity', 'joined_at'], 'sp_sessions_participant_joined_idx');
            $table->index(['room_session_id', 'joined_at'], 'sp_sessions_room_joined_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streaming_participant_sessions');
    }
};
